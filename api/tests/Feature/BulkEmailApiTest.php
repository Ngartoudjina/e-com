<?php

namespace Tests\Feature;

use App\Mail\BulkEmailMail;
use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BulkEmailApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
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

    private function abonne(string $email, bool $optOut = false): Subscriber
    {
        return Subscriber::create([
            'email' => $email,
            'subscribed_at' => now(),
            'opt_out' => $optOut,
        ]);
    }

    // ---------------------------------------------------------------- accès

    public function test_envoi_reserve_aux_admins(): void
    {
        $this->postJson('/api/send-bulk-email', ['subject' => 'S', 'message' => 'M'])
            ->assertStatus(401);

        Sanctum::actingAs($this->utilisateur());
        $this->postJson('/api/send-bulk-email', ['subject' => 'S', 'message' => 'M'])
            ->assertStatus(403);
    }

    // ---------------------------------------------------------------- envoi

    public function test_mise_en_file_pour_chaque_abonne(): void
    {
        Sanctum::actingAs($this->utilisateur(true));
        $this->abonne('a@exemple.fr');
        $this->abonne('b@exemple.fr');

        $this->postJson('/api/send-bulk-email', [
            'subject' => 'Nouveautés',
            'message' => "Découvrez notre sélection.\nBonne visite !",
        ])->assertOk()->assertJsonPath('queued', 2);

        Mail::assertQueued(BulkEmailMail::class, 2);
    }

    /** Un désabonné ne doit jamais recevoir de diffusion. */
    public function test_les_desabonnes_sont_exclus(): void
    {
        Sanctum::actingAs($this->utilisateur(true));
        $this->abonne('actif@exemple.fr');
        $this->abonne('parti@exemple.fr', optOut: true);

        $this->postJson('/api/send-bulk-email', ['subject' => 'S', 'message' => 'M'])
            ->assertOk()
            ->assertJsonPath('queued', 1);

        Mail::assertQueued(BulkEmailMail::class, 1);
        Mail::assertQueued(BulkEmailMail::class, fn ($mail) => $mail->hasTo('actif@exemple.fr'));
        Mail::assertNotQueued(BulkEmailMail::class, fn ($mail) => $mail->hasTo('parti@exemple.fr'));
    }

    public function test_aucun_abonne(): void
    {
        Sanctum::actingAs($this->utilisateur(true));

        $this->postJson('/api/send-bulk-email', ['subject' => 'S', 'message' => 'M'])
            ->assertStatus(400)
            ->assertJsonPath('error', 'Aucun abonné disponible');

        Mail::assertNothingQueued();
    }

    /** Seuls les opt-out existent : la liste est vide, rien ne part. */
    public function test_liste_entierement_desabonnee(): void
    {
        Sanctum::actingAs($this->utilisateur(true));
        $this->abonne('parti@exemple.fr', optOut: true);

        $this->postJson('/api/send-bulk-email', ['subject' => 'S', 'message' => 'M'])
            ->assertStatus(400);

        Mail::assertNothingQueued();
    }

    public function test_validation(): void
    {
        Sanctum::actingAs($this->utilisateur(true));
        $this->abonne('a@exemple.fr');

        $this->postJson('/api/send-bulk-email', [])->assertStatus(422);
        $this->postJson('/api/send-bulk-email', ['subject' => 'S'])->assertStatus(422);
        $this->postJson('/api/send-bulk-email', ['subject' => 'S', 'message' => 'M', 'imageUrl' => 'pas-une-url'])
            ->assertStatus(422);

        Mail::assertNothingQueued();
    }

    /** Chaque message est adressé nominativement : pas de liste en copie. */
    public function test_chaque_destinataire_recoit_son_propre_message(): void
    {
        Sanctum::actingAs($this->utilisateur(true));
        $this->abonne('a@exemple.fr');
        $this->abonne('b@exemple.fr');

        $this->postJson('/api/send-bulk-email', ['subject' => 'S', 'message' => 'M'])->assertOk();

        Mail::assertQueued(BulkEmailMail::class, function ($mail) {
            // Un seul destinataire par message : une adresse ne doit pas être
            // exposée aux autres abonnés.
            return count($mail->to) === 1;
        });
    }

    // ---------------------------------------------------------------- désabonnement en un clic

    public function test_le_lien_signe_desabonne(): void
    {
        $this->abonne('a@exemple.fr');

        $lien = URL::signedRoute('newsletter.unsubscribe', ['email' => 'a@exemple.fr']);

        $this->get($lien)->assertRedirect();

        $this->assertTrue(Subscriber::find('a@exemple.fr')->opt_out);
    }

    /** Sans signature valide, on doit pouvoir désabonner personne. */
    public function test_lien_non_signe_refuse(): void
    {
        $this->abonne('a@exemple.fr');

        $this->get('/api/unsubscribe?email=a@exemple.fr')->assertStatus(403);

        $this->assertFalse(Subscriber::find('a@exemple.fr')->opt_out);
    }

    public function test_signature_alteree_refusee(): void
    {
        $this->abonne('a@exemple.fr');
        $this->abonne('victime@exemple.fr');

        // On détourne un lien légitime vers une autre adresse.
        $lien = URL::signedRoute('newsletter.unsubscribe', ['email' => 'a@exemple.fr']);
        $detourne = str_replace('a%40exemple.fr', 'victime%40exemple.fr', $lien);

        $this->get($detourne)->assertStatus(403);

        $this->assertFalse(Subscriber::find('victime@exemple.fr')->opt_out);
    }

    /** Le message doit porter le lien de désabonnement. */
    public function test_le_message_contient_un_lien_de_desabonnement(): void
    {
        $mail = new BulkEmailMail('Sujet', 'Corps', 'a@exemple.fr');

        $lien = $mail->content()->with['lienDesabonnement'];

        $this->assertStringContainsString('/api/unsubscribe', $lien);
        $this->assertStringContainsString('signature=', $lien);
    }
}
