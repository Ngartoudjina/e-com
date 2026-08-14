<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Comptes de démonstration : un client et un administrateur, tous deux
 * déjà vérifiés, pour pouvoir parcourir l'espace client et l'administration
 * sans passer par la boucle d'e-mail.
 *
 * Réservé au développement. Ne pas exécuter en production.
 */
class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->warn('Seeder de démonstration ignoré en production.');

            return;
        }

        $comptes = [
            ['marie.laurent@exemple.fr', 'Marie', 'Laurent', false],
            ['admin@exemple.fr', 'Awa', 'Diallo', true],
        ];

        foreach ($comptes as [$email, $prenom, $nom, $admin]) {
            if (User::where('email', $email)->exists()) {
                continue;
            }

            User::create([
                'uid' => (string) Str::uuid(),
                'email' => $email,
                'name' => "$prenom $nom",
                'first_name' => $prenom,
                'last_name' => $nom,
                'password' => 'MotDePasse!2026',
                'email_verified' => true,
                'is_admin' => $admin,
                'provider' => 'password',
            ]);
        }

        $this->command?->info('Comptes de démonstration : marie.laurent@exemple.fr et admin@exemple.fr — MotDePasse!2026');
    }
}
