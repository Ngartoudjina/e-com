<?php

namespace Tests\Feature;

use App\Mail\ResetPasswordMail;
use App\Mail\VerifyEmailMail;
use App\Models\PendingUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    private const INSCRIPTION = [
        'email' => 'client@exemple.fr',
        'password' => 'MotDePasse!2026',
        'name' => 'Awa Diallo',
        'firstName' => 'Awa',
        'lastName' => 'Diallo',
        'phone' => '+229 59 33 44 83',
        'address' => 'Cotonou',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    private function utilisateurVerifie(array $attributs = []): User
    {
        $u = User::create(array_merge([
            'uid' => (string) Str::uuid(),
            'email' => 'connu@exemple.fr',
            'name' => 'Client',
            'password' => 'MotDePasse!2026',
            'email_verified' => true,
        ], $attributs));

        return $u->fresh();
    }

    // ---------------------------------------------------------------- inscription

    public function test_inscription_cree_une_demande_en_attente_et_envoie_un_email(): void
    {
        $this->postJson('/api/auth/register', self::INSCRIPTION)
            ->assertOk()
            ->assertJsonStructure(['message', 'uid']);

        $this->assertDatabaseHas('pending_users', ['email' => 'client@exemple.fr']);
        // L'utilisateur ne doit pas exister avant vérification.
        $this->assertDatabaseMissing('users', ['email' => 'client@exemple.fr']);

        Mail::assertSent(VerifyEmailMail::class);
    }

    public function test_le_jeton_de_verification_n_est_pas_stocke_en_clair(): void
    {
        $this->postJson('/api/auth/register', self::INSCRIPTION)->assertOk();

        $jetonEnvoye = null;
        Mail::assertSent(VerifyEmailMail::class, function ($mail) use (&$jetonEnvoye) {
            $jetonEnvoye = $mail->jeton;

            return true;
        });

        $stocke = PendingUser::where('email', 'client@exemple.fr')->value('verification_token');

        $this->assertNotSame($jetonEnvoye, $stocke);
        $this->assertSame(hash('sha256', $jetonEnvoye), $stocke);
    }

    public function test_champs_obligatoires_et_mot_de_passe_court(): void
    {
        $this->postJson('/api/auth/register', [])->assertStatus(422);

        $this->postJson('/api/auth/register', array_merge(self::INSCRIPTION, ['password' => '123']))
            ->assertStatus(422);
    }

    public function test_email_deja_utilise_refuse(): void
    {
        $this->utilisateurVerifie(['email' => 'client@exemple.fr']);

        $this->postJson('/api/auth/register', self::INSCRIPTION)
            ->assertStatus(400)
            ->assertJsonPath('error', 'Cet email est déjà utilisé');
    }

    public function test_une_seconde_inscription_remplace_la_precedente(): void
    {
        $this->postJson('/api/auth/register', self::INSCRIPTION)->assertOk();
        $this->postJson('/api/auth/register', self::INSCRIPTION)->assertOk();

        $this->assertSame(1, PendingUser::where('email', 'client@exemple.fr')->count());
    }

    // ---------------------------------------------------------------- vérification

    public function test_verification_promeut_en_utilisateur_et_delivre_un_jeton(): void
    {
        $this->postJson('/api/auth/register', self::INSCRIPTION)->assertOk();

        $jeton = null;
        Mail::assertSent(VerifyEmailMail::class, function ($mail) use (&$jeton) {
            $jeton = $mail->jeton;

            return true;
        });

        $reponse = $this->postJson('/api/auth/verify-email', ['token' => $jeton])->assertOk();

        $reponse->assertJsonPath('user.email', 'client@exemple.fr');
        $reponse->assertJsonPath('user.emailVerified', true);
        $this->assertNotEmpty($reponse->json('token'));

        $this->assertDatabaseHas('users', ['email' => 'client@exemple.fr']);
        // L'inscription en attente est consommée.
        $this->assertDatabaseMissing('pending_users', ['email' => 'client@exemple.fr']);
    }

    /** Le mot de passe déjà haché ne doit pas être haché une seconde fois. */
    public function test_le_mot_de_passe_survit_a_la_promotion(): void
    {
        $this->postJson('/api/auth/register', self::INSCRIPTION)->assertOk();
        $jeton = null;
        Mail::assertSent(VerifyEmailMail::class, function ($mail) use (&$jeton) {
            $jeton = $mail->jeton;

            return true;
        });
        $this->postJson('/api/auth/verify-email', ['token' => $jeton])->assertOk();

        $this->postJson('/api/auth/login', [
            'email' => 'client@exemple.fr',
            'password' => 'MotDePasse!2026',
        ])->assertOk();
    }

    public function test_jeton_de_verification_invalide_ou_expire(): void
    {
        $this->postJson('/api/auth/verify-email', ['token' => 'inexistant'])
            ->assertStatus(400)
            ->assertJsonPath('error', 'Lien de vérification invalide');

        $jeton = Str::random(64);
        PendingUser::create([
            'uid' => (string) Str::uuid(),
            'email' => 'expire@exemple.fr',
            'name' => 'X',
            'hashed_password' => Hash::make('MotDePasse!2026'),
            'verification_token' => hash('sha256', $jeton),
            'verification_token_expiry' => now()->subMinute(),
        ]);

        $this->postJson('/api/auth/verify-email', ['token' => $jeton])
            ->assertStatus(400)
            ->assertJsonPath('error', 'Lien de vérification expiré');
    }

    /** Réponse neutre : ne pas révéler quelles adresses ont une inscription en cours. */
    public function test_renvoi_de_verification_reste_neutre(): void
    {
        $this->postJson('/api/auth/resend-verification', ['email' => 'inconnu@exemple.fr'])->assertOk();
        Mail::assertNothingSent();

        $this->postJson('/api/auth/register', self::INSCRIPTION)->assertOk();
        $this->postJson('/api/auth/resend-verification', ['email' => 'client@exemple.fr'])->assertOk();

        Mail::assertSent(VerifyEmailMail::class, 2);
    }

    // ---------------------------------------------------------------- connexion

    public function test_connexion_delivre_un_jeton_sanctum(): void
    {
        $this->utilisateurVerifie();

        $reponse = $this->postJson('/api/auth/login', [
            'email' => 'connu@exemple.fr',
            'password' => 'MotDePasse!2026',
        ])->assertOk();

        $this->assertNotEmpty($reponse->json('token'));
        $reponse->assertJsonPath('user.email', 'connu@exemple.fr');
        // Aucune donnée sensible ne doit fuiter dans la réponse.
        $this->assertArrayNotHasKey('password', $reponse->json('user'));
    }

    public function test_mauvais_identifiants_donnent_le_meme_message(): void
    {
        $this->utilisateurVerifie();

        $inconnu = $this->postJson('/api/auth/login', [
            'email' => 'personne@exemple.fr', 'password' => 'MotDePasse!2026',
        ])->assertStatus(401);

        $mauvais = $this->postJson('/api/auth/login', [
            'email' => 'connu@exemple.fr', 'password' => 'faux',
        ])->assertStatus(401);

        // Message identique : sinon l'endpoint permet d'énumérer les comptes.
        $this->assertSame($inconnu->json('error'), $mauvais->json('error'));
    }

    public function test_email_non_verifie_refuse(): void
    {
        $this->utilisateurVerifie(['email' => 'attente@exemple.fr', 'email_verified' => false]);

        $this->postJson('/api/auth/login', [
            'email' => 'attente@exemple.fr', 'password' => 'MotDePasse!2026',
        ])->assertStatus(403);
    }

    /** Un compte Google n'a pas de mot de passe : il ne doit pas être contournable. */
    public function test_compte_sans_mot_de_passe_refuse(): void
    {
        User::create([
            'uid' => (string) Str::uuid(),
            'email' => 'google@exemple.fr',
            'name' => 'Google',
            'provider' => 'google',
            'email_verified' => true,
        ]);

        $this->postJson('/api/auth/login', ['email' => 'google@exemple.fr', 'password' => ''])
            ->assertStatus(422);

        $this->postJson('/api/auth/login', ['email' => 'google@exemple.fr', 'password' => 'nimporte'])
            ->assertStatus(401);
    }

    // ---------------------------------------------------------------- session

    public function test_me_et_logout(): void
    {
        $u = $this->utilisateurVerifie();
        $jeton = $this->postJson('/api/auth/login', [
            'email' => 'connu@exemple.fr', 'password' => 'MotDePasse!2026',
        ])->json('token');

        $entetes = ['Authorization' => 'Bearer '.$jeton];

        $this->getJson('/api/auth/me', $entetes)->assertOk()->assertJsonPath('user.uid', $u->getKey());

        $this->assertSame(1, $u->tokens()->count());

        $this->postJson('/api/auth/logout', [], $entetes)->assertOk();

        // La révocation est bien effective en base.
        $this->assertSame(0, $u->tokens()->count());

        // Le garde d'authentification mémorise l'utilisateur résolu pour la durée
        // du cycle applicatif ; en test, les requêtes successives partagent cette
        // instance. On l'oublie explicitement, sinon on validerait le cache plutôt
        // que la révocation.
        $this->app['auth']->forgetGuards();

        $this->getJson('/api/auth/me', $entetes)->assertStatus(401);
    }

    public function test_me_exige_une_authentification(): void
    {
        $this->getJson('/api/auth/me')->assertStatus(401);
    }

    // ---------------------------------------------------------------- mot de passe

    public function test_demande_de_reinitialisation_reste_neutre(): void
    {
        $this->utilisateurVerifie();

        $connu = $this->postJson('/api/auth/reset-password', ['email' => 'connu@exemple.fr'])->assertOk();
        $inconnu = $this->postJson('/api/auth/reset-password', ['email' => 'personne@exemple.fr'])->assertOk();

        // Le backend Node répondait « Aucun utilisateur trouvé avec cet email ».
        $this->assertSame($connu->json('message'), $inconnu->json('message'));

        Mail::assertSent(ResetPasswordMail::class, 1);
    }

    public function test_reinitialisation_complete(): void
    {
        $u = $this->utilisateurVerifie();
        $this->postJson('/api/auth/reset-password', ['email' => 'connu@exemple.fr'])->assertOk();

        $jeton = null;
        Mail::assertSent(ResetPasswordMail::class, function ($mail) use (&$jeton) {
            $jeton = $mail->jeton;

            return true;
        });

        // Le jeton est stocké haché, jamais en clair.
        $this->assertSame(hash('sha256', $jeton), $u->fresh()->reset_token);

        $this->postJson('/api/auth/confirm-reset-password', [
            'token' => $jeton,
            'newPassword' => 'NouveauMotDePasse!2026',
        ])->assertOk();

        $this->postJson('/api/auth/login', [
            'email' => 'connu@exemple.fr', 'password' => 'NouveauMotDePasse!2026',
        ])->assertOk();

        $this->postJson('/api/auth/login', [
            'email' => 'connu@exemple.fr', 'password' => 'MotDePasse!2026',
        ])->assertStatus(401);
    }

    /** Changer de mot de passe doit invalider les sessions ouvertes. */
    public function test_la_reinitialisation_revoque_les_jetons_existants(): void
    {
        $this->utilisateurVerifie();
        $jetonSession = $this->postJson('/api/auth/login', [
            'email' => 'connu@exemple.fr', 'password' => 'MotDePasse!2026',
        ])->json('token');

        $this->postJson('/api/auth/reset-password', ['email' => 'connu@exemple.fr'])->assertOk();
        $jetonMail = null;
        Mail::assertSent(ResetPasswordMail::class, function ($mail) use (&$jetonMail) {
            $jetonMail = $mail->jeton;

            return true;
        });

        $this->postJson('/api/auth/confirm-reset-password', [
            'token' => $jetonMail, 'newPassword' => 'NouveauMotDePasse!2026',
        ])->assertOk();

        $this->getJson('/api/auth/me', ['Authorization' => 'Bearer '.$jetonSession])
            ->assertStatus(401);
    }

    public function test_jeton_de_reinitialisation_expire_refuse(): void
    {
        $u = $this->utilisateurVerifie();
        $jeton = Str::random(64);
        $u->forceFill([
            'reset_token' => hash('sha256', $jeton),
            'reset_token_expiry' => now()->subMinute(),
        ])->save();

        $this->postJson('/api/auth/confirm-reset-password', [
            'token' => $jeton, 'newPassword' => 'NouveauMotDePasse!2026',
        ])->assertStatus(400)->assertJsonPath('error', 'Token invalide ou expiré');
    }

    /** Le jeton doit être à usage unique. */
    public function test_le_jeton_de_reinitialisation_ne_sert_qu_une_fois(): void
    {
        $this->utilisateurVerifie();
        $this->postJson('/api/auth/reset-password', ['email' => 'connu@exemple.fr'])->assertOk();
        $jeton = null;
        Mail::assertSent(ResetPasswordMail::class, function ($mail) use (&$jeton) {
            $jeton = $mail->jeton;

            return true;
        });

        $this->postJson('/api/auth/confirm-reset-password', [
            'token' => $jeton, 'newPassword' => 'Premier!2026',
        ])->assertOk();

        $this->postJson('/api/auth/confirm-reset-password', [
            'token' => $jeton, 'newPassword' => 'Second!2026',
        ])->assertStatus(400);
    }
}
