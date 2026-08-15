<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminApiTest extends TestCase
{
    use RefreshDatabase;

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

    private function produit(array $attributs = []): Product
    {
        return Product::create(array_merge([
            'name' => 'Casque',
            'price' => 25000.0,
            'description' => 'Un casque',
            'category' => 'Audio',
            'stock' => 4,
        ], $attributs));
    }

    // ---------------------------------------------------------------- accès

    /** Toutes les routes d'administration doivent être fermées aux anonymes. */
    public function test_les_routes_admin_exigent_une_authentification(): void
    {
        $this->getJson('/api/admin/products')->assertStatus(401);
        $this->getJson('/api/admin/users')->assertStatus(401);
        $this->getJson('/api/admin/analytics')->assertStatus(401);
        $this->postJson('/api/upload')->assertStatus(401);
        $this->getJson('/api/affiliate/requests/pending')->assertStatus(401);
    }

    /** Un compte authentifié mais non administrateur doit être refusé. */
    public function test_un_simple_membre_ne_passe_pas(): void
    {
        Sanctum::actingAs($this->utilisateur());

        $this->getJson('/api/admin/products')->assertStatus(403)
            ->assertJsonPath('error', 'Accès admin requis');
        $this->getJson('/api/admin/users')->assertStatus(403);
        $this->getJson('/api/admin/analytics')->assertStatus(403);
        $this->postJson('/api/admin/products', ['name' => 'X', 'price' => 1])->assertStatus(403);
        $this->deleteJson('/api/admin/products/'.$this->produit()->getKey())->assertStatus(403);
    }

    /** Le drapeau admin est relu en base : une révocation prend effet aussitôt. */
    public function test_la_revocation_du_role_prend_effet_immediatement(): void
    {
        $admin = $this->utilisateur(true);
        Sanctum::actingAs($admin);

        $this->getJson('/api/admin/products')->assertOk();

        $admin->forceFill(['is_admin' => false])->save();

        $this->getJson('/api/admin/products')->assertStatus(403);
    }

    // ---------------------------------------------------------------- produits

    public function test_creation_de_produit(): void
    {
        Sanctum::actingAs($admin = $this->utilisateur(true));

        $reponse = $this->postJson('/api/admin/products', [
            'name' => 'Enceinte',
            'price' => 45000,
            'description' => 'Bluetooth',
            'category' => 'Audio',
            'stock' => 7,
            'mediaUrl' => 'https://exemple.test/e.jpg',
        ])->assertStatus(201);

        $reponse->assertJsonPath('product.name', 'Enceinte');
        $reponse->assertJsonPath('product.mediaUrl', 'https://exemple.test/e.jpg');
        $this->assertNotEmpty($reponse->json('productId'));

        // L'auteur est enregistré mais jamais exposé.
        $this->assertDatabaseHas('products', ['name' => 'Enceinte', 'created_by' => $admin->getKey()]);
        $this->assertArrayNotHasKey('createdBy', $reponse->json('product'));
    }

    public function test_creation_sans_media_refusee(): void
    {
        Sanctum::actingAs($this->utilisateur(true));

        $this->postJson('/api/admin/products', ['name' => 'X', 'price' => 10])
            ->assertStatus(400)
            ->assertJsonPath('error', 'Nom, prix et image sont requis');
    }

    public function test_validation_de_la_creation(): void
    {
        Sanctum::actingAs($this->utilisateur(true));

        $this->postJson('/api/admin/products', [])->assertStatus(422);
        $this->postJson('/api/admin/products', ['name' => 'X', 'price' => -5, 'mediaUrl' => 'https://e.test/a.jpg'])
            ->assertStatus(422);
        $this->postJson('/api/admin/products', ['name' => 'X', 'price' => 10, 'rating' => 9, 'mediaUrl' => 'https://e.test/a.jpg'])
            ->assertStatus(422);
    }

    public function test_mise_a_jour_partielle(): void
    {
        Sanctum::actingAs($this->utilisateur(true));
        $p = $this->produit(['media_url' => 'https://exemple.test/o.jpg']);

        $reponse = $this->putJson('/api/admin/products/'.$p->getKey(), ['price' => 30000])
            ->assertOk()
            // Les champs non transmis ne doivent pas être écrasés.
            ->assertJsonPath('product.name', 'Casque')
            ->assertJsonPath('product.mediaUrl', 'https://exemple.test/o.jpg');

        // JSON n'a qu'un type numérique : 30000.0 s'y sérialise en 30000.
        $this->assertEquals(30000.0, $reponse->json('product.price'));
    }

    public function test_mise_a_jour_produit_inconnu(): void
    {
        Sanctum::actingAs($this->utilisateur(true));

        $this->putJson('/api/admin/products/fantome', ['price' => 1])->assertStatus(404);
        $this->deleteJson('/api/admin/products/fantome')->assertStatus(404);
    }

    public function test_suppression_de_produit(): void
    {
        Sanctum::actingAs($this->utilisateur(true));
        $p = $this->produit();

        $this->deleteJson('/api/admin/products/'.$p->getKey())->assertOk();

        $this->assertDatabaseMissing('products', ['id' => $p->getKey()]);
    }

    public function test_liste_admin_en_camel_case(): void
    {
        Sanctum::actingAs($this->utilisateur(true));
        $this->produit(['media_url' => 'https://exemple.test/a.jpg']);

        $produit = $this->getJson('/api/admin/products')->assertOk()->json('products.0');

        $this->assertArrayHasKey('mediaUrl', $produit);
        $this->assertArrayNotHasKey('media_url', $produit);
    }

    // ---------------------------------------------------------------- utilisateurs

    public function test_liste_des_utilisateurs_sans_donnees_sensibles(): void
    {
        Sanctum::actingAs($this->utilisateur(true));

        $utilisateur = $this->getJson('/api/admin/users')->assertOk()->json('users.0');

        $this->assertArrayHasKey('uid', $utilisateur);
        $this->assertArrayHasKey('isAdmin', $utilisateur);
        $this->assertArrayNotHasKey('password', $utilisateur);
        $this->assertArrayNotHasKey('reset_token', $utilisateur);
        $this->assertArrayNotHasKey('resetToken', $utilisateur);
    }

    public function test_promotion_et_retrogradation(): void
    {
        Sanctum::actingAs($this->utilisateur(true));
        $membre = $this->utilisateur();

        $this->patchJson("/api/admin/users/{$membre->getKey()}/role", ['isAdmin' => true])
            ->assertOk()
            ->assertJsonPath('user.isAdmin', true);

        $this->patchJson("/api/admin/users/{$membre->getKey()}/role", ['isAdmin' => false])
            ->assertOk()
            ->assertJsonPath('user.isAdmin', false);
    }

    /** Sans ce garde-fou, le dernier admin peut se verrouiller dehors. */
    public function test_un_admin_ne_peut_pas_se_retirer_ses_propres_droits(): void
    {
        $admin = $this->utilisateur(true);
        Sanctum::actingAs($admin);

        $this->patchJson("/api/admin/users/{$admin->getKey()}/role", ['isAdmin' => false])
            ->assertStatus(400);

        $this->assertTrue($admin->fresh()->is_admin);
    }

    public function test_role_utilisateur_inconnu(): void
    {
        Sanctum::actingAs($this->utilisateur(true));

        $this->patchJson('/api/admin/users/fantome/role', ['isAdmin' => true])->assertStatus(404);
    }

    // ---------------------------------------------------------------- statistiques

    public function test_analytics(): void
    {
        Sanctum::actingAs($this->utilisateur(true));
        $this->produit(['stock' => 3, 'category' => 'Audio']);
        $this->produit(['stock' => 0, 'category' => 'Vidéo']);

        $reponse = $this->getJson('/api/admin/analytics')->assertOk();

        $reponse->assertJsonPath('analytics.totalProducts', 2);
        $reponse->assertJsonPath('analytics.totalStock', 3);
        $reponse->assertJsonPath('analytics.outOfStock', 1);
        $reponse->assertJsonPath('analytics.admins', 1);
    }

    /**
     * Les chiffres de vente doivent venir des commandes.
     *
     * L'écran affichait un revenu écrit en dur ; le test fixe le contrat
     * inverse : ce qui est montré se déduit de la base.
     */
    public function test_les_chiffres_de_vente_viennent_des_commandes(): void
    {
        $produit = $this->produit(['stock' => 50, 'price' => 100.0]);

        $passer = function (string $statut, float $total) use ($produit) {
            $commande = Order::create([
                'reference' => Order::genererReference(),
                'email' => 'client@exemple.fr',
                'status' => $statut,
                'subtotal' => $total, 'discount' => 0, 'shipping' => 0, 'total' => $total,
                'currency' => 'EUR', 'placed_at' => now(),
            ]);
            $commande->items()->create([
                'product_id' => $produit->getKey(), 'name' => $produit->name,
                'unit_price' => $total, 'quantity' => 1, 'line_total' => $total,
            ]);

            return $commande;
        };

        $passer('delivered', 200.0);
        $passer('paid', 100.0);
        $passer('pending', 999.0);     // pas encore encaissée
        $passer('cancelled', 500.0);   // hors chiffre d'affaires

        Sanctum::actingAs($this->utilisateur(true));
        $reponse = $this->getJson('/api/admin/analytics')->assertOk();

        // 200 + 100 : ni l'attente ni l'annulation ne gonflent le revenu.
        $this->assertEquals(300.0, $reponse->json('ventes.revenu'));
        $this->assertSame(2, $reponse->json('ventes.commandes'));
        $this->assertEquals(150.0, $reponse->json('ventes.panierMoyen'));
        $this->assertSame(1, $reponse->json('ventes.enAttente'));
        $this->assertSame(1, $reponse->json('ventes.annulees'));

        // Douze mois pleins, même sans vente : une série trouée fausse la courbe.
        $serie = $reponse->json('ventes.parMois');
        $this->assertCount(12, $serie);
        $this->assertEquals(300.0, end($serie)['total']);
    }

    /** La liste des clients porte des cumuls calculés, non décoratifs. */
    public function test_la_liste_des_clients_porte_commandes_et_depenses(): void
    {
        $client = $this->utilisateur();
        $produit = $this->produit(['stock' => 10, 'price' => 50.0]);

        foreach ([['delivered', 120.0], ['paid', 80.0], ['cancelled', 999.0]] as [$statut, $total]) {
            $commande = Order::create([
                'reference' => Order::genererReference(),
                'uid' => $client->getKey(),
                'email' => $client->email,
                'status' => $statut,
                'subtotal' => $total, 'discount' => 0, 'shipping' => 0, 'total' => $total,
                'currency' => 'EUR', 'placed_at' => now(),
            ]);
            $commande->items()->create([
                'product_id' => $produit->getKey(), 'name' => $produit->name,
                'unit_price' => $total, 'quantity' => 1, 'line_total' => $total,
            ]);
        }

        Sanctum::actingAs($this->utilisateur(true));
        $reponse = $this->getJson('/api/admin/users')->assertOk();

        $ligne = collect($reponse->json('users'))->firstWhere('uid', $client->getKey());

        // L'annulation ne compte ni en commandes ni en dépense.
        $this->assertSame(2, $ligne['ordersCount']);
        $this->assertEquals(200.0, $ligne['totalSpent']);

        // Un client sans commande affiche zéro, pas une absence de clé.
        $autre = collect($reponse->json('users'))->firstWhere('email', '!=', $client->email);
        $this->assertSame(0, $autre['ordersCount']);
        $this->assertEquals(0, $autre['totalSpent']);
    }

    /** Sans aucune commande, l'écran ne doit pas diviser par zéro. */
    public function test_les_chiffres_de_vente_sans_commande(): void
    {
        Sanctum::actingAs($this->utilisateur(true));

        $reponse = $this->getJson('/api/admin/analytics')->assertOk();

        $this->assertEquals(0, $reponse->json('ventes.revenu'));
        $this->assertEquals(0, $reponse->json('ventes.panierMoyen'));
        $this->assertCount(12, $reponse->json('ventes.parMois'));
        $this->assertSame([], $reponse->json('ventes.meilleuresVentes'));
    }
}
