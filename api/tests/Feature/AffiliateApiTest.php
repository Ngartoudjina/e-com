<?php

namespace Tests\Feature;

use App\Models\Affiliate;
use App\Models\AffiliateRequest;
use App\Models\Referral;
use App\Models\User;
use App\Services\MediaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

class AffiliateApiTest extends TestCase
{
    use RefreshDatabase;

    private const MOTIVATION = 'Je souhaite promouvoir vos produits auprès de ma communauté, qui compte plusieurs milliers de personnes intéressées.';

    protected function setUp(): void
    {
        parent::setUp();

        // Cloudinary n'est pas sollicité depuis les tests.
        $this->mock(MediaService::class, function ($mock) {
            $mock->shouldReceive('envoyerImage')->andReturn([
                'url' => 'https://exemple.test/piece.webp',
                'publicId' => 'affiliates/piece',
            ]);
            $mock->shouldReceive('supprimer')->andReturn(true);
            $mock->shouldReceive('estConfigure')->andReturn(true);
        });
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

    private function piece(): UploadedFile
    {
        return UploadedFile::fake()->image('carte.jpg', 600, 400);
    }

    // ---------------------------------------------------------------- demande

    public function test_la_demande_exige_une_authentification(): void
    {
        $this->postJson('/api/affiliate/request', ['reason' => self::MOTIVATION])->assertStatus(401);
        $this->getJson('/api/affiliate/status')->assertStatus(401);
        $this->getJson('/api/affiliate/affiliate-stats')->assertStatus(401);
    }

    public function test_soumission_d_une_demande(): void
    {
        Sanctum::actingAs($u = $this->utilisateur());

        $this->post('/api/affiliate/request', [
            'reason' => self::MOTIVATION,
            'identityCard' => $this->piece(),
        ])->assertStatus(201)->assertJsonPath('request.status', 'pending');

        $this->assertDatabaseHas('affiliate_requests', ['uid' => $u->getKey(), 'status' => 'pending']);
    }

    /** La règle des 50 caractères du formulaire doit être rejouée côté serveur. */
    public function test_motivation_trop_courte_refusee(): void
    {
        Sanctum::actingAs($this->utilisateur());

        $this->post('/api/affiliate/request', [
            'reason' => 'Trop court',
            'identityCard' => $this->piece(),
        ])->assertStatus(422);
    }

    public function test_piece_d_identite_obligatoire(): void
    {
        Sanctum::actingAs($this->utilisateur());

        $this->post('/api/affiliate/request', ['reason' => self::MOTIVATION])->assertStatus(422);
    }

    public function test_pas_deux_demandes_en_attente(): void
    {
        Sanctum::actingAs($this->utilisateur());

        $this->post('/api/affiliate/request', [
            'reason' => self::MOTIVATION, 'identityCard' => $this->piece(),
        ])->assertStatus(201);

        $this->post('/api/affiliate/request', [
            'reason' => self::MOTIVATION, 'identityCard' => $this->piece(),
        ])->assertStatus(400)->assertJsonPath('error', 'Une demande est déjà en attente');
    }

    // ---------------------------------------------------------------- statut

    public function test_statut_sans_demande(): void
    {
        Sanctum::actingAs($this->utilisateur());

        $this->getJson('/api/affiliate/status')->assertOk()
            ->assertJsonPath('isAffiliate', false)
            ->assertJsonPath('hasPendingRequest', false);
    }

    public function test_statut_avec_demande_en_attente(): void
    {
        Sanctum::actingAs($u = $this->utilisateur());
        AffiliateRequest::create([
            'uid' => $u->getKey(), 'reason' => self::MOTIVATION,
            'identity_card_url' => 'https://e.test/p.webp', 'status' => 'pending',
        ]);

        $this->getJson('/api/affiliate/status')->assertOk()
            ->assertJsonPath('requestStatus', 'pending')
            ->assertJsonPath('hasPendingRequest', true);
    }

    /** La forme doit correspondre exactement à ce que type AffiliateView.vue. */
    public function test_statut_d_un_affilie_approuve(): void
    {
        Sanctum::actingAs($u = $this->utilisateur());
        Affiliate::create([
            'uid' => $u->getKey(), 'affiliate_code' => 'AFF-123456',
            'referral_link' => 'https://front.test/?ref=AFF-123456',
            'commission_rate' => 0.05, 'is_active' => true,
        ]);

        $reponse = $this->getJson('/api/affiliate/status')->assertOk();

        $reponse->assertJsonPath('isAffiliate', true);
        $donnees = $reponse->json('affiliateData');

        foreach (['uid', 'affiliateCode', 'referralLink', 'commissionRate', 'totalEarnings', 'totalReferrals', 'referralCount', 'isActive', 'createdAt'] as $cle) {
            $this->assertArrayHasKey($cle, $donnees, "clé manquante : $cle");
        }
    }

    // ---------------------------------------------------------------- validation admin

    public function test_seul_un_admin_traite_les_demandes(): void
    {
        Sanctum::actingAs($this->utilisateur());

        $this->getJson('/api/affiliate/requests/pending')->assertStatus(403);
        $this->postJson('/api/affiliate/x/approve')->assertStatus(403);
        $this->postJson('/api/affiliate/x/reject')->assertStatus(403);
        $this->deleteJson('/api/affiliate/x')->assertStatus(403);
    }

    public function test_approbation_cree_le_compte_affilie(): void
    {
        $membre = $this->utilisateur();
        $demande = AffiliateRequest::create([
            'uid' => $membre->getKey(), 'reason' => self::MOTIVATION,
            'identity_card_url' => 'https://e.test/p.webp', 'status' => 'pending',
        ]);

        Sanctum::actingAs($this->utilisateur(true));

        $reponse = $this->postJson('/api/affiliate/'.$demande->getKey().'/approve')->assertOk();

        $code = $reponse->json('affiliate.affiliateCode');
        $this->assertMatchesRegularExpression('/^AFF-\d{6}$/', $code);
        // Le lien de parrainage doit porter le code.
        $this->assertStringContainsString($code, $reponse->json('affiliate.referralLink'));

        $this->assertDatabaseHas('affiliates', ['uid' => $membre->getKey()]);
        $this->assertDatabaseHas('affiliate_requests', ['id' => $demande->getKey(), 'status' => 'approved']);
        // Le drapeau doit suivre sur la fiche utilisateur.
        $this->assertTrue($membre->fresh()->is_affiliate);
    }

    public function test_une_demande_deja_traitee_n_est_pas_reapprouvee(): void
    {
        $demande = AffiliateRequest::create([
            'uid' => $this->utilisateur()->getKey(), 'reason' => self::MOTIVATION,
            'identity_card_url' => 'https://e.test/p.webp', 'status' => 'approved',
        ]);

        Sanctum::actingAs($this->utilisateur(true));

        $this->postJson('/api/affiliate/'.$demande->getKey().'/approve')->assertStatus(400);
    }

    public function test_rejet_et_suppression(): void
    {
        $demande = AffiliateRequest::create([
            'uid' => $this->utilisateur()->getKey(), 'reason' => self::MOTIVATION,
            'identity_card_url' => 'https://e.test/p.webp', 'status' => 'pending',
        ]);

        Sanctum::actingAs($this->utilisateur(true));

        $this->postJson('/api/affiliate/'.$demande->getKey().'/reject')->assertOk()
            ->assertJsonPath('request.status', 'rejected');

        $this->deleteJson('/api/affiliate/'.$demande->getKey())->assertOk();
        $this->assertDatabaseMissing('affiliate_requests', ['id' => $demande->getKey()]);
    }

    public function test_demande_inconnue(): void
    {
        Sanctum::actingAs($this->utilisateur(true));

        $this->postJson('/api/affiliate/fantome/approve')->assertStatus(404);
        $this->postJson('/api/affiliate/fantome/reject')->assertStatus(404);
        $this->deleteJson('/api/affiliate/fantome')->assertStatus(404);
    }

    public function test_liste_par_onglet(): void
    {
        $u = $this->utilisateur();
        foreach (['pending', 'approved', 'rejected'] as $statut) {
            AffiliateRequest::create([
                'uid' => $u->getKey(), 'reason' => self::MOTIVATION,
                'identity_card_url' => 'https://e.test/p.webp', 'status' => $statut,
            ]);
        }

        Sanctum::actingAs($this->utilisateur(true));

        $this->getJson('/api/affiliate/requests/approved')->assertOk()->assertJsonPath('count', 1);
        // Un onglet inconnu retombe sur « pending », comme côté Node.
        $this->getJson('/api/affiliate/requests/nimportequoi')->assertOk()->assertJsonPath('count', 1);
    }

    // ---------------------------------------------------------------- suivi de clic

    public function test_suivi_de_clic_public(): void
    {
        $affilie = Affiliate::create([
            'uid' => $this->utilisateur()->getKey(), 'affiliate_code' => 'AFF-654321',
            'commission_rate' => 0.05, 'is_active' => true,
        ]);

        $this->postJson('/api/track-click', ['ref' => 'AFF-654321'])
            ->assertOk()->assertJsonPath('tracked', true);

        $this->assertSame(1, Referral::where('affiliate_id', $affilie->getKey())->count());
        $this->assertSame(1, $affilie->fresh()->referral_count);
    }

    /** Un code inconnu répond comme un code valide : pas d'oracle. */
    public function test_code_inconnu_reste_indiscernable(): void
    {
        $this->postJson('/api/track-click', ['ref' => 'AFF-000000'])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame(0, Referral::count());
    }

    public function test_un_visiteur_identifie_ne_compte_qu_une_fois(): void
    {
        $affilie = Affiliate::create([
            'uid' => $this->utilisateur()->getKey(), 'affiliate_code' => 'AFF-777777',
            'commission_rate' => 0.05, 'is_active' => true,
        ]);

        Sanctum::actingAs($this->utilisateur());

        $this->postJson('/api/track-click', ['ref' => 'AFF-777777'])->assertOk();
        $this->postJson('/api/track-click', ['ref' => 'AFF-777777'])->assertOk();

        $this->assertSame(1, Referral::where('affiliate_id', $affilie->getKey())->count());
        $this->assertSame(1, $affilie->fresh()->referral_count);
    }

    public function test_ref_obligatoire(): void
    {
        $this->postJson('/api/track-click', [])->assertStatus(422);
    }

    // ---------------------------------------------------------------- statistiques

    public function test_stats_reservees_aux_affilies(): void
    {
        Sanctum::actingAs($this->utilisateur());

        $this->getJson('/api/affiliate/affiliate-stats')->assertStatus(403);
    }

    public function test_calcul_des_gains(): void
    {
        Sanctum::actingAs($u = $this->utilisateur());
        $affilie = Affiliate::create([
            'uid' => $u->getKey(), 'affiliate_code' => 'AFF-888888',
            'commission_rate' => 0.05, 'is_active' => true,
        ]);

        Referral::create([
            'affiliate_id' => $affilie->getKey(), 'referred_user_id' => 'visiteur-1',
            'affiliate_code' => 'AFF-888888', 'status' => 'converted',
            'orders' => [['total' => 10000], ['amount' => 5000]],
        ]);

        $reponse = $this->getJson('/api/affiliate/affiliate-stats')->assertOk();

        $reponse->assertJsonPath('stats.totalOrders', 2);

        // JSON n'a qu'un type numérique : la comparaison doit être souple.
        $this->assertEquals(15000.0, $reponse->json('stats.totalRevenue'));
        // 5 % de 15 000
        $this->assertEquals(750.0, $reponse->json('stats.totalEarnings'));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
