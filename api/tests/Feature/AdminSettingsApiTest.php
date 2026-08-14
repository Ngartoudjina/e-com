<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\PromoCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminSettingsApiTest extends TestCase
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

    // ---------------------------------------------------------------- accès

    public function test_les_reglages_admin_sont_reserves(): void
    {
        $this->getJson('/api/admin/settings')->assertStatus(401);
        $this->getJson('/api/admin/promos')->assertStatus(401);

        Sanctum::actingAs($this->utilisateur());
        $this->getJson('/api/admin/settings')->assertStatus(403);
        $this->putJson('/api/admin/settings', ['freeShippingThreshold' => 0])->assertStatus(403);
        $this->postJson('/api/admin/promos', ['code' => 'PIRATE'])->assertStatus(403);
    }

    // ---------------------------------------------------------------- réglages

    public function test_les_reglages_partent_de_la_configuration(): void
    {
        Sanctum::actingAs($this->utilisateur(true));

        $reponse = $this->getJson('/api/admin/settings')->assertOk();

        $this->assertEquals(config('boutique.livraison.franco'), $reponse->json('settings.freeShippingThreshold'));
        $this->assertEquals(config('boutique.tva'), $reponse->json('settings.vatRate'));
    }

    public function test_modification_et_effet_sur_le_site(): void
    {
        Sanctum::actingAs($this->utilisateur(true));

        $this->putJson('/api/admin/settings', [
            'freeShippingThreshold' => 300,
            'shippingStandard' => 9.9,
            'announcements' => ['Livraison offerte dès 300 €'],
        ])->assertOk();

        // La modification doit être visible du site public, sans redéploiement.
        $public = $this->getJson('/api/settings')->assertOk();
        $this->assertEquals(300, $public->json('freeShippingThreshold'));
        $this->assertEquals(9.9, $public->json('shippingMethods.0.price'));
        $this->assertSame(['Livraison offerte dès 300 €'], $public->json('announcements'));
    }

    /**
     * Le point qui compte : un seuil modifié doit changer ce qui est
     * réellement facturé, pas seulement ce qui est affiché.
     */
    public function test_le_seuil_modifie_change_la_facturation(): void
    {
        $produit = Product::create([
            'name' => 'Pull', 'price' => 200.0, 'description' => 'x',
            'category' => 'Mailles', 'stock' => 10,
        ]);

        $commande = [
            'items' => [['productId' => $produit->getKey(), 'quantity' => 1]],
            'email' => 'client@exemple.fr',
            'shippingMethod' => 'standard',
        ];

        // Seuil par défaut à 150 : 200 € passe en franco.
        $avant = $this->postJson('/api/orders', $commande)->assertStatus(201);
        $this->assertEquals(0.0, $avant->json('order.shipping'));

        Sanctum::actingAs($this->utilisateur(true));
        $this->putJson('/api/admin/settings', ['freeShippingThreshold' => 500, 'shippingStandard' => 7.5])->assertOk();

        // Seuil relevé à 500 : la même commande paie désormais le port.
        $apres = $this->postJson('/api/orders', $commande)->assertStatus(201);
        $this->assertEquals(7.5, $apres->json('order.shipping'));
        $this->assertEquals(207.5, $apres->json('order.total'));
    }

    public function test_reinitialisation_d_un_reglage(): void
    {
        Sanctum::actingAs($this->utilisateur(true));

        $this->putJson('/api/admin/settings', ['freeShippingThreshold' => 999])->assertOk();
        $this->deleteJson('/api/admin/settings/freeShippingThreshold')->assertOk();

        $reponse = $this->getJson('/api/admin/settings')->assertOk();
        $this->assertEquals(config('boutique.livraison.franco'), $reponse->json('settings.freeShippingThreshold'));
    }

    public function test_validation_des_reglages(): void
    {
        Sanctum::actingAs($this->utilisateur(true));

        $this->putJson('/api/admin/settings', ['freeShippingThreshold' => -10])->assertStatus(422);
        // La TVA est une fraction : 20 serait 2000 %.
        $this->putJson('/api/admin/settings', ['vatRate' => 20])->assertStatus(422);
        $this->putJson('/api/admin/settings', ['returnDays' => 9999])->assertStatus(422);
    }

    public function test_un_reglage_inconnu_est_ignore(): void
    {
        Sanctum::actingAs($this->utilisateur(true));

        $this->putJson('/api/admin/settings', ['nimporteQuoi' => 42])->assertOk();
        $this->assertDatabaseMissing('settings', ['key' => 'nimporteQuoi']);
    }

    // ---------------------------------------------------------------- codes promo

    public function test_cycle_de_vie_d_un_code(): void
    {
        Sanctum::actingAs($this->utilisateur(true));

        $cree = $this->postJson('/api/admin/promos', [
            'code' => 'hiver25', 'type' => 'percent', 'value' => 25, 'active' => true,
        ])->assertStatus(201);

        // Le code est normalisé en capitales.
        $cree->assertJsonPath('promo.code', 'HIVER25');
        $id = $cree->json('promo.id');

        $this->putJson("/api/admin/promos/{$id}", ['value' => 30])->assertOk()
            ->assertJsonPath('promo.value', 30);

        $this->getJson('/api/admin/promos')->assertOk()->assertJsonPath('count', 1);

        $this->deleteJson("/api/admin/promos/{$id}")->assertOk();
        $this->assertSame(0, PromoCode::count());
    }

    public function test_code_en_double_refuse(): void
    {
        Sanctum::actingAs($this->utilisateur(true));

        $this->postJson('/api/admin/promos', ['code' => 'UNIQUE', 'value' => 10])->assertStatus(201);

        // La casse ne crée pas un second code, et le refus est en français.
        $this->postJson('/api/admin/promos', ['code' => 'unique', 'value' => 10])
            ->assertStatus(422)
            ->assertJsonPath('errors.code.0', 'Ce code existe déjà.');

        // Les espaces de saisie ne doivent pas non plus ouvrir une brèche.
        $this->postJson('/api/admin/promos', ['code' => '  Unique  ', 'value' => 10])->assertStatus(422);

        $this->assertSame(1, PromoCode::count());
    }

    /** Un code déjà utilisé est désactivé, pas effacé : des commandes le citent. */
    public function test_un_code_utilise_est_desactive_et_non_supprime(): void
    {
        $promo = PromoCode::create(['code' => 'SERVI', 'type' => 'percent', 'value' => 10, 'used_count' => 3]);

        Sanctum::actingAs($this->utilisateur(true));
        $this->deleteJson("/api/admin/promos/{$promo->getKey()}")->assertOk();

        $this->assertSame(1, PromoCode::count());
        $this->assertFalse(PromoCode::first()->active);
    }

    public function test_validation_du_code(): void
    {
        Sanctum::actingAs($this->utilisateur(true));

        $this->postJson('/api/admin/promos', [])->assertStatus(422);
        // Espaces et accents interdits : le code est saisi à la main.
        $this->postJson('/api/admin/promos', ['code' => 'code avec espaces'])->assertStatus(422);
        $this->postJson('/api/admin/promos', ['code' => 'OK', 'type' => 'inconnu'])->assertStatus(422);
    }

    /** Un code créé depuis l'administration doit s'appliquer à une commande. */
    public function test_un_code_cree_fonctionne_immediatement(): void
    {
        $produit = Product::create([
            'name' => 'Veste', 'price' => 400.0, 'description' => 'x',
            'category' => 'Vestes', 'stock' => 5,
        ]);

        Sanctum::actingAs($this->utilisateur(true));
        $this->postJson('/api/admin/promos', [
            'code' => 'NOUVEAU10', 'type' => 'percent', 'value' => 10, 'active' => true,
        ])->assertStatus(201);

        $reponse = $this->postJson('/api/orders', [
            'items' => [['productId' => $produit->getKey(), 'quantity' => 1]],
            'email' => 'client@exemple.fr',
            'promoCode' => 'nouveau10',
        ])->assertStatus(201);

        $this->assertEquals(40.0, $reponse->json('order.discount'));
        $this->assertEquals(360.0, $reponse->json('order.total'));
    }
}
