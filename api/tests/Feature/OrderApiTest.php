<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\PromoCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderApiTest extends TestCase
{
    use RefreshDatabase;

    private function produit(array $attributs = []): Product
    {
        return Product::create(array_merge([
            'name' => 'Manteau Ardenne',
            'price' => 490.0,
            'description' => 'Laine bouillie',
            'category' => 'Manteaux',
            'stock' => 10,
        ], $attributs));
    }

    private function utilisateur(bool $admin = false): User
    {
        return User::create([
            'uid' => (string) Str::uuid(),
            'email' => uniqid().'@test.local',
            'name' => $admin ? 'Admin' : 'Client',
            'password' => 'MotDePasse!2026',
            'email_verified' => true,
            'is_admin' => $admin,
        ]);
    }

    private function commande(Product $produit, array $extra = []): array
    {
        return array_merge([
            'items' => [['productId' => $produit->getKey(), 'quantity' => 1, 'size' => 'S', 'color' => 'Anthracite']],
            'email' => 'client@exemple.fr',
            'name' => 'Marie Laurent',
            'address' => '18 rue des Archives',
            'postalCode' => '75004',
            'city' => 'Paris',
            'shippingMethod' => 'standard',
        ], $extra);
    }

    // ---------------------------------------------------------------- montants

    /**
     * Le point le plus important : un prix envoyé par le client est ignoré.
     * Sans cela, n'importe qui commande à n'importe quel prix.
     */
    public function test_les_prix_viennent_de_la_base_pas_de_la_requete(): void
    {
        $produit = $this->produit(['price' => 490.0]);

        $charge = $this->commande($produit);
        // Tentative de dicter le prix.
        $charge['items'][0]['price'] = 0.01;
        $charge['items'][0]['unitPrice'] = 0.01;
        $charge['subtotal'] = 0.01;
        $charge['total'] = 0.01;

        $reponse = $this->postJson('/api/orders', $charge)->assertStatus(201);

        // JSON n'a qu'un type numérique : la comparaison doit être souple.
        $this->assertEquals(490.0, $reponse->json('order.subtotal'));
        $this->assertEquals(490.0, $reponse->json('order.total'));
        $this->assertEquals(490.0, $reponse->json('order.items.0.unitPrice'));
    }

    public function test_franco_applique_au_dela_du_seuil(): void
    {
        $produit = $this->produit(['price' => 490.0]);

        $reponse = $this->postJson('/api/orders', $this->commande($produit))->assertStatus(201);
        $this->assertEquals(0.0, $reponse->json('order.shipping'));
    }

    public function test_frais_de_port_sous_le_seuil(): void
    {
        $produit = $this->produit(['price' => 40.0]);

        $reponse = $this->postJson('/api/orders', $this->commande($produit))->assertStatus(201);

        $this->assertGreaterThan(0, $reponse->json('order.shipping'));
        $this->assertEquals(
            40.0 + $reponse->json('order.shipping'),
            $reponse->json('order.total')
        );
    }

    public function test_les_lignes_figent_le_produit(): void
    {
        $produit = $this->produit(['name' => 'Nom initial', 'price' => 200.0]);

        $this->postJson('/api/orders', $this->commande($produit))->assertStatus(201);

        // Le produit change après coup : la commande ne doit pas bouger.
        $produit->forceFill(['name' => 'Nom modifié', 'price' => 999.0])->save();

        $ligne = Order::with('items')->first()->items->first();
        $this->assertSame('Nom initial', $ligne->name);
        $this->assertEquals(200.0, $ligne->unit_price);
    }

    // ---------------------------------------------------------------- stock

    public function test_le_stock_est_decremente(): void
    {
        $produit = $this->produit(['stock' => 10]);

        $charge = $this->commande($produit);
        $charge['items'][0]['quantity'] = 3;

        $this->postJson('/api/orders', $charge)->assertStatus(201);

        $this->assertSame(7, $produit->fresh()->stock);
        $this->assertSame(3, $produit->fresh()->sold_count);
    }

    public function test_stock_insuffisant_refuse(): void
    {
        $produit = $this->produit(['stock' => 2]);

        $charge = $this->commande($produit);
        $charge['items'][0]['quantity'] = 5;

        $this->postJson('/api/orders', $charge)->assertStatus(422);

        // Rien ne doit avoir été écrit.
        $this->assertSame(2, $produit->fresh()->stock);
        $this->assertSame(0, Order::count());
    }

    /** Deux lignes de la même variante comptent pour leur somme. */
    public function test_le_stock_agrege_les_lignes_identiques(): void
    {
        $produit = $this->produit(['stock' => 3]);

        $charge = $this->commande($produit);
        $charge['items'] = [
            ['productId' => $produit->getKey(), 'quantity' => 2, 'size' => 'S'],
            ['productId' => $produit->getKey(), 'quantity' => 2, 'size' => 'S'],
        ];

        $this->postJson('/api/orders', $charge)->assertStatus(422);
        $this->assertSame(3, $produit->fresh()->stock);
    }

    public function test_produit_inexistant_refuse(): void
    {
        $charge = $this->commande($this->produit());
        $charge['items'][0]['productId'] = 'fantome';

        $this->postJson('/api/orders', $charge)->assertStatus(422);
    }

    // ---------------------------------------------------------------- promotions

    public function test_code_promo_valide_reduit_le_total(): void
    {
        $produit = $this->produit(['price' => 500.0]);
        PromoCode::create(['code' => 'archives20', 'type' => 'percent', 'value' => 20, 'active' => true]);

        $reponse = $this->postJson('/api/orders', $this->commande($produit, ['promoCode' => 'ArChIvEs20']))
            ->assertStatus(201);

        // La casse ne doit pas compter.
        $this->assertEquals(100.0, $reponse->json('order.discount'));
        $this->assertEquals(400.0, $reponse->json('order.total'));
        $reponse->assertJsonPath('order.promoCode', 'ARCHIVES20');
    }

    public function test_code_promo_inconnu_sans_effet(): void
    {
        $produit = $this->produit(['price' => 500.0]);

        $reponse = $this->postJson('/api/orders', $this->commande($produit, ['promoCode' => 'INVENTE']))
            ->assertStatus(201);
        $this->assertEquals(0.0, $reponse->json('order.discount'));
        $this->assertEquals(500.0, $reponse->json('order.total'));
    }

    public function test_code_promo_expire_sans_effet(): void
    {
        $produit = $this->produit(['price' => 500.0]);
        PromoCode::create([
            'code' => 'PERIME', 'type' => 'percent', 'value' => 50,
            'active' => true, 'expires_at' => now()->subDay(),
        ]);

        $reponse = $this->postJson('/api/orders', $this->commande($produit, ['promoCode' => 'PERIME']))
            ->assertStatus(201);
        $this->assertEquals(0.0, $reponse->json('order.discount'));
    }

    /** Une remise ne peut pas rendre le total négatif. */
    public function test_la_remise_est_plafonnee_au_sous_total(): void
    {
        $produit = $this->produit(['price' => 50.0]);
        PromoCode::create(['code' => 'GROS', 'type' => 'amount', 'value' => 1000, 'active' => true]);

        $reponse = $this->postJson('/api/orders', $this->commande($produit, ['promoCode' => 'GROS']))
            ->assertStatus(201);

        $this->assertEquals(50.0, $reponse->json('order.discount'));
        $this->assertGreaterThanOrEqual(0, $reponse->json('order.total'));
    }

    public function test_verification_de_code_depuis_le_panier(): void
    {
        PromoCode::create(['code' => 'ARCHIVES20', 'type' => 'percent', 'value' => 20, 'active' => true, 'label' => 'ARCHIVES20 appliqué — 20 %']);

        $reponse = $this->postJson('/api/promo/check', ['code' => 'archives20', 'subtotal' => 300])
            ->assertOk()
            ->assertJsonPath('valide', true);
        $this->assertEquals(60.0, $reponse->json('remise'));

        $this->postJson('/api/promo/check', ['code' => 'INVENTE', 'subtotal' => 300])
            ->assertStatus(422)
            ->assertJsonPath('valide', false);
    }

    // ---------------------------------------------------------------- accès

    public function test_commande_possible_sans_compte(): void
    {
        $produit = $this->produit();

        $this->postJson('/api/orders', $this->commande($produit))->assertStatus(201);

        $this->assertNull(Order::first()->uid);
    }

    public function test_commande_connectee_rattachee_au_compte(): void
    {
        $produit = $this->produit();
        Sanctum::actingAs($u = $this->utilisateur());

        $this->postJson('/api/orders', $this->commande($produit))->assertStatus(201);

        $this->assertSame($u->getKey(), Order::first()->uid);
    }

    /**
     * Le rattachement doit fonctionner avec un vrai jeton porteur, pas
     * seulement avec Sanctum::actingAs.
     *
     * La route de création est publique — la commande sans compte doit rester
     * possible — et sur une route publique le garde Sanctum n'est pas appliqué
     * d'office. `actingAs` masquait ce cas : les commandes des clients
     * connectés étaient enregistrées comme des commandes invitées.
     */
    public function test_rattachement_avec_un_jeton_porteur_reel(): void
    {
        $produit = $this->produit();
        $utilisateur = $this->utilisateur();
        $jeton = $utilisateur->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$jeton)
            ->postJson('/api/orders', $this->commande($produit))
            ->assertStatus(201);

        $this->assertSame($utilisateur->getKey(), Order::first()->uid);
    }

    public function test_un_client_ne_voit_que_ses_commandes(): void
    {
        $produit = $this->produit(['stock' => 50]);

        Sanctum::actingAs($a = $this->utilisateur());
        $this->postJson('/api/orders', $this->commande($produit))->assertStatus(201);

        Sanctum::actingAs($this->utilisateur());
        $this->getJson('/api/orders')->assertOk()->assertJsonPath('count', 0);
    }

    /** Une référence qui ne nous appartient pas répond comme inexistante. */
    public function test_la_commande_d_autrui_est_indiscernable_d_une_inexistante(): void
    {
        $produit = $this->produit(['stock' => 50]);

        Sanctum::actingAs($this->utilisateur());
        $reference = $this->postJson('/api/orders', $this->commande($produit))
            ->assertStatus(201)->json('order.reference');

        Sanctum::actingAs($this->utilisateur());
        $autrui = $this->getJson("/api/orders/{$reference}")->assertStatus(404);
        $inexistante = $this->getJson('/api/orders/GS-00000')->assertStatus(404);

        $this->assertSame($inexistante->json('error'), $autrui->json('error'));
    }

    public function test_un_admin_voit_toutes_les_commandes(): void
    {
        $produit = $this->produit(['stock' => 50]);

        Sanctum::actingAs($this->utilisateur());
        $reference = $this->postJson('/api/orders', $this->commande($produit))
            ->assertStatus(201)->json('order.reference');

        Sanctum::actingAs($this->utilisateur(true));
        $this->getJson("/api/orders/{$reference}")->assertOk();
        $this->getJson('/api/admin/orders')->assertOk()->assertJsonPath('count', 1);
    }

    public function test_les_commandes_admin_sont_reservees(): void
    {
        Sanctum::actingAs($this->utilisateur());
        $this->getJson('/api/admin/orders')->assertStatus(403);
        $this->patchJson('/api/admin/orders/GS-00000/status', ['status' => 'paid'])->assertStatus(403);
    }

    // ---------------------------------------------------------------- statuts

    public function test_progression_des_statuts(): void
    {
        $produit = $this->produit(['stock' => 50]);
        $reference = $this->postJson('/api/orders', $this->commande($produit))
            ->assertStatus(201)->json('order.reference');

        Sanctum::actingAs($this->utilisateur(true));

        foreach (['paid', 'preparing', 'shipped', 'delivered'] as $statut) {
            $this->patchJson("/api/admin/orders/{$reference}/status", ['status' => $statut])
                ->assertOk()
                ->assertJsonPath('order.status', $statut);
        }

        $commande = Order::where('reference', $reference)->first();
        $this->assertNotNull($commande->shipped_at);
        $this->assertNotNull($commande->delivered_at);
    }

    /** On n'expédie pas une commande annulée, et on ne revient pas en arrière. */
    public function test_transition_impossible_refusee(): void
    {
        $produit = $this->produit(['stock' => 50]);
        $reference = $this->postJson('/api/orders', $this->commande($produit))
            ->assertStatus(201)->json('order.reference');

        Sanctum::actingAs($this->utilisateur(true));

        // pending -> delivered : saut interdit.
        $this->patchJson("/api/admin/orders/{$reference}/status", ['status' => 'delivered'])
            ->assertStatus(422);

        $this->patchJson("/api/admin/orders/{$reference}/status", ['status' => 'cancelled'])->assertOk();

        // Une commande annulée ne repart pas.
        $this->patchJson("/api/admin/orders/{$reference}/status", ['status' => 'paid'])
            ->assertStatus(422);
    }

    public function test_statut_inconnu_refuse(): void
    {
        $produit = $this->produit(['stock' => 50]);
        $reference = $this->postJson('/api/orders', $this->commande($produit))
            ->assertStatus(201)->json('order.reference');

        Sanctum::actingAs($this->utilisateur(true));
        $this->patchJson("/api/admin/orders/{$reference}/status", ['status' => 'nimporte'])
            ->assertStatus(422);
    }

    // ---------------------------------------------------------------- réglages

    public function test_les_reglages_sont_publics_et_complets(): void
    {
        $this->getJson('/api/settings')
            ->assertOk()
            ->assertJsonStructure([
                'currency',
                'freeShippingThreshold',
                'shippingMethods' => [['key', 'label', 'detail', 'price']],
                'vatRate',
                'announcements',
                'returnDays',
            ]);
    }

    public function test_validation_de_la_commande(): void
    {
        $this->postJson('/api/orders', [])->assertStatus(422);
        $this->postJson('/api/orders', ['items' => [], 'email' => 'a@b.fr'])->assertStatus(422);
        $this->postJson('/api/orders', [
            'items' => [['productId' => 'x', 'quantity' => 0]],
            'email' => 'pas-un-email',
        ])->assertStatus(422);
    }
}
