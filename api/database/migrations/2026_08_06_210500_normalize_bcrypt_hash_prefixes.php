<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Aligne les hachages produits par bcryptjs sur la convention attendue par PHP.
 *
 * bcryptjs écrit ses hachages avec le préfixe `$2b$`. L'algorithme est identique à
 * celui de `$2y$` utilisé par PHP — seule la convention de préfixe diffère — et
 * `password_verify()` valide bien les deux.
 *
 * En revanche `password_get_info()` renvoie `algoName: "unknown"` pour `$2b$`, et le
 * BcryptHasher de Laravel refuse alors le hachage avec :
 *     RuntimeException: This password does not use the Bcrypt algorithm.
 *
 * Sans cette normalisation, plus aucun compte créé par le backend Node ne peut se
 * connecter. Comportement vérifié sur Laravel 13.24 / PHP 8.4.
 *
 * La réécriture ne touche que les 4 premiers caractères : le sel et le condensat sont
 * conservés, les mots de passe des utilisateurs restent valables.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->swapPrefix('$2b$', '$2y$');
    }

    public function down(): void
    {
        $this->swapPrefix('$2y$', '$2b$');
    }

    /**
     * Remplace le préfixe des hachages, en conservant sel et condensat.
     * La cible est Postgres ; SQLite est géré pour que la suite de tests tourne.
     */
    private function swapPrefix(string $from, string $to): void
    {
        foreach ([['users', 'password'], ['pending_users', 'hashed_password']] as [$table, $column]) {
            DB::table($table)
                ->where($column, 'like', $from.'%')
                ->update([
                    $column => DB::raw($this->concatExpression($column, $to)),
                ]);
        }
    }

    private function concatExpression(string $column, string $to): string
    {
        // `$` n'a pas de signification particulière dans un littéral SQL entre apostrophes.
        $quoted = "'".$to."'";

        return match (DB::connection()->getDriverName()) {
            'sqlite' => "{$quoted} || substr({$column}, 5)",
            'mysql', 'mariadb' => "concat({$quoted}, substring({$column}, 5))",
            default => "{$quoted} || substring({$column} from 5)",
        };
    }
};
