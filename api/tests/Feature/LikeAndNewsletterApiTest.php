<?php

namespace Tests\Feature;

use App\Models\Like;
use App\Models\Product;
use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LikeAndNewsletterApiTest extends TestCase
{
    use RefreshDatabase;

    private function utilisateur(): User
    {
        return User::create([
            'uid' => 'uid-'.uniqid(),
            'email' => uniqid().'@test.local',
            'name' => 'Client',
            'password' => 'MotDePasse!2026',
        ]);
    }

    private function produit(): Product
    {
        return Product::create([
            'name' => 'Casque',
            'price' => 25000.0,
            'description' => 'Un casque',
            'category' => 'Audio',
        ]);
    }

    // ---- Favoris ----

    public function test_les_favoris_exigent_une_authentification(): void
    {
        $this->getJson('/api/likes')->assertStatus(401);
        $this->postJson('/api/likes/toggle', ['productId' => 'x'])->assertStatus(401);
    }

    public function test_le_toggle_ajoute_puis_retire(): void
    {
        $u = $this->utilisateur();
        $p = $this->produit();
        Sanctum::actingAs($u);

        $this->postJson('/api/likes/toggle', ['productId' => $p->getKey()])
            ->assertStatus(201)
            ->assertJsonPath('liked', true)
            ->assertJsonPath('message', 'Ajouté aux favoris');

        $this->assertDatabaseHas('likes', ['uid' => $u->getKey(), 'product_id' => $p->getKey()]);

        $this->postJson('/api/likes/toggle', ['productId' => $p->getKey()])
            ->assertOk()
            ->assertJsonPath('liked', false)
            ->assertJsonPath('message', 'Retiré des favoris');

        $this->assertDatabaseMissing('likes', ['uid' => $u->getKey(), 'product_id' => $p->getKey()]);
    }

    /** L'identifiant composé « uid:productId » garantit l'absence de doublon. */
    public function test_pas_de_doublon_de_favori(): void
    {
        $u = $this->utilisateur();
        $p = $this->produit();
        Sanctum::actingAs($u);

        $this->postJson('/api/likes/toggle', ['productId' => $p->getKey()])->assertStatus(201);
        $this->postJson('/api/likes/toggle', ['productId' => $p->getKey()])->assertOk();
        $this->postJson('/api/likes/toggle', ['productId' => $p->getKey()])->assertStatus(201);

        $this->assertSame(1, Like::where('uid', $u->getKey())->count());
    }

    public function test_productid_manquant_rejete(): void
    {
        Sanctum::actingAs($this->utilisateur());

        $this->postJson('/api/likes/toggle', [])
            ->assertStatus(400)
            ->assertJsonPath('error', 'productId requis');
    }

    public function test_produit_inexistant_rejete(): void
    {
        Sanctum::actingAs($this->utilisateur());

        $this->postJson('/api/likes/toggle', ['productId' => 'fantome'])->assertStatus(404);
    }

    public function test_liste_des_favoris_avec_produit_imbrique(): void
    {
        $u = $this->utilisateur();
        $p = $this->produit();
        Sanctum::actingAs($u);

        $this->postJson('/api/likes/toggle', ['productId' => $p->getKey()])->assertStatus(201);

        $reponse = $this->getJson('/api/likes')->assertOk();

        $reponse->assertJsonPath('success', true);
        $reponse->assertJsonPath('likedProductIds.0', $p->getKey());
        $reponse->assertJsonPath('likes.0.productId', $p->getKey());
        // Le produit imbriqué doit lui aussi être en camelCase.
        $this->assertArrayHasKey('mediaUrl', $reponse->json('likes.0.product'));
    }

    public function test_un_utilisateur_ne_voit_pas_les_favoris_d_un_autre(): void
    {
        $a = $this->utilisateur();
        $b = $this->utilisateur();
        $p = $this->produit();

        Sanctum::actingAs($a);
        $this->postJson('/api/likes/toggle', ['productId' => $p->getKey()])->assertStatus(201);

        Sanctum::actingAs($b);
        $this->assertCount(0, $this->getJson('/api/likes')->assertOk()->json('likes'));
    }

    // ---- Newsletter ----

    public function test_inscription_newsletter(): void
    {
        $this->postJson('/api/subscribe', ['email' => 'client@exemple.fr'])
            ->assertStatus(201)
            ->assertJsonPath('message', 'Inscription réussie à la newsletter');

        $this->assertDatabaseHas('subscribers', ['email' => 'client@exemple.fr', 'opt_out' => false]);
    }

    public function test_email_invalide_rejete(): void
    {
        $this->postJson('/api/subscribe', ['email' => 'pas-un-email'])
            ->assertStatus(400)
            ->assertJsonPath('error', 'Email invalide');

        $this->postJson('/api/subscribe', [])->assertStatus(400);
    }

    public function test_seconde_inscription_signalee(): void
    {
        Subscriber::create(['email' => 'client@exemple.fr', 'subscribed_at' => now(), 'opt_out' => false]);

        $this->postJson('/api/subscribe', ['email' => 'client@exemple.fr'])
            ->assertOk()
            ->assertJsonPath('message', 'Vous êtes déjà abonné');

        $this->assertSame(1, Subscriber::count());
    }

    public function test_desabonnement_puis_reabonnement(): void
    {
        $this->postJson('/api/subscribe', ['email' => 'client@exemple.fr'])->assertStatus(201);
        $dateOrigine = Subscriber::find('client@exemple.fr')->subscribed_at;

        $this->postJson('/api/unsubscribe', ['email' => 'client@exemple.fr'])->assertOk();
        $this->assertTrue(Subscriber::find('client@exemple.fr')->opt_out);

        $this->postJson('/api/subscribe', ['email' => 'client@exemple.fr'])->assertOk();

        $abonne = Subscriber::find('client@exemple.fr');
        $this->assertFalse($abonne->opt_out);
        // La date d'origine ne doit pas être écrasée par le réabonnement.
        $this->assertEquals($dateOrigine->timestamp, $abonne->subscribed_at->timestamp);
    }

    /** Le désabonnement ne doit pas révéler si l'adresse est connue. */
    public function test_desabonnement_d_une_adresse_inconnue_reste_neutre(): void
    {
        $this->postJson('/api/unsubscribe', ['email' => 'inconnu@exemple.fr'])
            ->assertOk()
            ->assertJsonPath('message', 'Désabonnement pris en compte');
    }

    public function test_email_requis_au_desabonnement(): void
    {
        $this->postJson('/api/unsubscribe', [])
            ->assertStatus(400)
            ->assertJsonPath('error', 'Email requis');
    }
}
