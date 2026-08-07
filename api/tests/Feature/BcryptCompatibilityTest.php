<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Garantit que les comptes créés par l'ancien backend Node restent utilisables.
 *
 * bcryptjs écrit ses hachages avec le préfixe `$2b$`, que le BcryptHasher de Laravel
 * refuse (« This password does not use the Bcrypt algorithm »). La migration
 * `normalize_bcrypt_hash_prefixes` les réaligne sur `$2y$`.
 */
class BcryptCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    /** Hachage réellement produit par bcryptjs pour le mot de passe ci-dessous. */
    private const HASH_NODE = '$2b$10$Et.yfUHyDHabcs7FuQ6dO.A2BUaFVLVVjqHllxd6gUV23552eHJu.';

    private const MOT_DE_PASSE = 'MotDePasse!2026';

    public function test_le_hachage_bcryptjs_est_rejete_tel_quel_par_laravel(): void
    {
        $this->expectException(\RuntimeException::class);

        Hash::check(self::MOT_DE_PASSE, self::HASH_NODE);
    }

    public function test_le_prefixe_normalise_permet_la_connexion(): void
    {
        $migre = '$2y$'.substr(self::HASH_NODE, 4);

        $this->assertTrue(Hash::check(self::MOT_DE_PASSE, $migre));
        $this->assertFalse(Hash::check('mauvais mot de passe', $migre));
    }

    public function test_la_migration_convertit_les_comptes_existants(): void
    {
        DB::table('users')->insert([
            'uid' => 'uid-issu-de-node',
            'email' => 'ancien@goldshop.test',
            'password' => self::HASH_NODE,
        ]);

        DB::table('pending_users')->insert([
            'uid' => 'uid-en-attente',
            'email' => 'attente@goldshop.test',
            'hashed_password' => self::HASH_NODE,
        ]);

        $this->executerMigrationDeNormalisation();

        $motDePasse = DB::table('users')->where('uid', 'uid-issu-de-node')->value('password');
        $enAttente = DB::table('pending_users')->where('uid', 'uid-en-attente')->value('hashed_password');

        // Le sel et le condensat sont intacts : seul le préfixe a changé.
        $this->assertSame('$2y$', substr($motDePasse, 0, 4));
        $this->assertSame(substr(self::HASH_NODE, 4), substr($motDePasse, 4));

        $this->assertTrue(Hash::check(self::MOT_DE_PASSE, $motDePasse));
        $this->assertTrue(Hash::check(self::MOT_DE_PASSE, $enAttente));
        $this->assertFalse(Hash::check('mauvais mot de passe', $motDePasse));
    }

    public function test_la_migration_ne_touche_pas_aux_hachages_deja_conformes(): void
    {
        $natif = Hash::make(self::MOT_DE_PASSE);

        DB::table('users')->insert([
            'uid' => 'uid-natif',
            'email' => 'natif@goldshop.test',
            'password' => $natif,
        ]);

        $this->executerMigrationDeNormalisation();

        $this->assertSame($natif, DB::table('users')->where('uid', 'uid-natif')->value('password'));
    }

    private function executerMigrationDeNormalisation(): void
    {
        $chemin = database_path('migrations/2026_08_06_210500_normalize_bcrypt_hash_prefixes.php');

        $this->assertFileExists($chemin);

        (require $chemin)->up();
    }
}
