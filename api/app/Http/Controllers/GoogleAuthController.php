<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirect;

/**
 * Connexion Google via Socialite.
 *
 * Le backend Node recevait un ID token émis par le SDK Firebase côté navigateur
 * et le validait avec google-auth-library. On adopte ici le flux de redirection
 * standard d'OAuth : le secret client ne quitte jamais le serveur et l'échange
 * de code est vérifié par Google, ce qui supprime toute confiance accordée à un
 * jeton fourni par le client.
 *
 * Conséquence côté frontend : `GoogleSignIn.vue` ne doit plus appeler Firebase
 * mais envoyer l'utilisateur vers /api/auth/google/redirect.
 */
class GoogleAuthController extends Controller
{
    public function redirect(): SymfonyRedirect|JsonResponse
    {
        if (! config('services.google.client_id')) {
            return response()->json(['error' => 'Connexion Google non configurée'], 503);
        }

        return Socialite::driver('google')->stateless()->redirect();
    }

    public function callback(): RedirectResponse|JsonResponse
    {
        try {
            $compte = Socialite::driver('google')->stateless()->user();
        } catch (\Throwable $e) {
            report($e);

            return $this->versLeFront(['error' => 'google_failed']);
        }

        if (! $compte->getEmail()) {
            return $this->versLeFront(['error' => 'google_no_email']);
        }

        // Rattachement par e-mail : un compte créé par mot de passe et une
        // connexion Google sur la même adresse désignent la même personne.
        $utilisateur = User::where('email', $compte->getEmail())->first();

        if ($utilisateur) {
            $utilisateur->forceFill([
                'google_id' => $compte->getId(),
                'photo_url' => $utilisateur->photo_url ?: $compte->getAvatar(),
                // Google a déjà validé l'adresse.
                'email_verified' => true,
                'last_login' => now(),
            ])->save();
        } else {
            $utilisateur = User::create([
                'uid' => (string) Str::uuid(),
                'email' => $compte->getEmail(),
                'name' => $compte->getName() ?: 'Utilisateur',
                'google_id' => $compte->getId(),
                'photo_url' => $compte->getAvatar(),
                'provider' => 'google',
                'email_verified' => true,
                'is_admin' => false,
                'last_login' => now(),
            ]);
        }

        $jeton = $utilisateur->createToken('google')->plainTextToken;

        return $this->versLeFront(['token' => $jeton]);
    }

    /**
     * Renvoie l'utilisateur vers le frontend.
     *
     * Le jeton passe dans le fragment (#) et non la query (?) : un fragment
     * n'est ni envoyé au serveur, ni consigné dans les journaux d'accès,
     * ni transmis via l'en-tête Referer.
     */
    private function versLeFront(array $parametres): RedirectResponse
    {
        $base = rtrim((string) config('app.frontend_url'), '/');

        return redirect()->away($base.'/auth/callback#'.http_build_query($parametres));
    }
}
