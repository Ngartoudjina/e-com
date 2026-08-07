<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Mail\ResetPasswordMail;
use App\Mail\VerifyEmailMail;
use App\Models\PendingUser;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Authentification. Porté depuis back/src/controllers/auth.controller.js.
 *
 * Firebase disparaît du circuit : l'application émet désormais ses propres
 * identifiants (UID), ses jetons de vérification et de réinitialisation, et
 * délivre des jetons Sanctum en lieu et place des JWT signés à la main.
 *
 * Les jetons transmis par e-mail ne sont jamais stockés en clair : seul leur
 * condensat SHA-256 va en base, et la comparaison est à temps constant.
 */
class AuthController extends Controller
{
    private const DUREE_VERIFICATION = 24; // heures
    private const DUREE_REINITIALISATION = 1; // heure

    // ---------------------------------------------------------------- inscription

    public function register(Request $request): JsonResponse
    {
        $donnees = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:6'],
            'name' => ['required', 'string', 'max:255'],
            'firstName' => ['required', 'string', 'max:255'],
            'lastName' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'address' => ['required', 'string', 'max:500'],
        ]);

        if (User::where('email', $donnees['email'])->exists()) {
            return response()->json(['error' => 'Cet email est déjà utilisé'], 400);
        }

        // Une inscription en attente pour la même adresse est remplacée :
        // sinon un e-mail saisi deux fois resterait bloqué jusqu'à expiration.
        PendingUser::where('email', $donnees['email'])->delete();

        $uid = (string) Str::uuid();
        $jeton = Str::random(64);

        $enAttente = PendingUser::create([
            'uid' => $uid,
            'email' => $donnees['email'],
            'name' => $donnees['name'],
            'first_name' => $donnees['firstName'],
            'last_name' => $donnees['lastName'],
            'phone' => $donnees['phone'],
            'address' => $donnees['address'],
            'hashed_password' => Hash::make($donnees['password']),
            'is_admin' => false,
            'verification_status' => 'pending',
            'verification_token' => hash('sha256', $jeton),
            'verification_token_expiry' => now()->addHours(self::DUREE_VERIFICATION),
        ]);

        Mail::to($enAttente->email)->send(new VerifyEmailMail($enAttente, $jeton));

        return response()->json([
            'message' => 'Un email de vérification a été envoyé. Veuillez vérifier votre email pour compléter l\'inscription.',
            'uid' => $uid,
        ]);
    }

    // ---------------------------------------------------------------- connexion

    public function login(Request $request): JsonResponse
    {
        $donnees = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $utilisateur = User::where('email', $donnees['email'])->first();

        // Message volontairement identique dans les deux cas : ne pas révéler
        // quelles adresses possèdent un compte.
        if (! $utilisateur || ! $utilisateur->password || ! Hash::check($donnees['password'], $utilisateur->password)) {
            return response()->json(['error' => 'Email ou mot de passe incorrect'], 401);
        }

        if (! $utilisateur->email_verified) {
            return response()->json(['error' => 'Veuillez vérifier votre email avant de vous connecter'], 403);
        }

        $utilisateur->forceFill(['last_login' => now()])->save();

        return response()->json([
            'token' => $utilisateur->createToken('api')->plainTextToken,
            'user' => (new UserResource($utilisateur))->resolve(),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        // Ne révoque que le jeton présenté : les autres sessions restent valides.
        $request->user()->currentAccessToken()->delete();

        return response()->json(['success' => true, 'message' => 'Déconnexion réussie']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => (new UserResource($request->user()))->resolve()]);
    }

    // ---------------------------------------------------------------- vérification

    public function verifyEmail(Request $request): JsonResponse
    {
        $donnees = $request->validate([
            'token' => ['required', 'string'],
        ]);

        $enAttente = PendingUser::where('verification_token', hash('sha256', $donnees['token']))->first();

        if (! $enAttente) {
            return response()->json(['error' => 'Lien de vérification invalide'], 400);
        }

        if ($enAttente->verification_token_expiry && now()->greaterThan($enAttente->verification_token_expiry)) {
            return response()->json(['error' => 'Lien de vérification expiré'], 400);
        }

        // Promotion en une transaction : jamais d'utilisateur créé sans que
        // l'inscription en attente correspondante soit consommée.
        $utilisateur = DB::transaction(function () use ($enAttente) {
            $utilisateur = User::create([
                'uid' => $enAttente->uid,
                'email' => $enAttente->email,
                'name' => $enAttente->name,
                'first_name' => $enAttente->first_name,
                'last_name' => $enAttente->last_name,
                'phone' => $enAttente->phone,
                'address' => $enAttente->address,
                'is_admin' => $enAttente->is_admin,
                'email_verified' => true,
                'provider' => 'password',
            ]);

            // Le mot de passe est déjà haché : le passer par `create()` le
            // rehacherait via le cast `hashed`.
            $utilisateur->forceFill(['password' => $enAttente->hashed_password])->save();

            $enAttente->delete();

            return $utilisateur;
        });

        return response()->json([
            'message' => 'Email vérifié avec succès',
            'token' => $utilisateur->createToken('api')->plainTextToken,
            'user' => (new UserResource($utilisateur))->resolve(),
        ]);
    }

    public function resendVerification(Request $request): JsonResponse
    {
        $donnees = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $enAttente = PendingUser::where('email', $donnees['email'])->first();

        // Réponse neutre : l'endpoint ne doit pas dire quelles adresses
        // ont une inscription en cours.
        $reponse = response()->json([
            'message' => 'Si une inscription est en attente pour cette adresse, un nouvel email a été envoyé.',
        ]);

        if (! $enAttente) {
            return $reponse;
        }

        $jeton = Str::random(64);
        $enAttente->forceFill([
            'verification_token' => hash('sha256', $jeton),
            'verification_token_expiry' => now()->addHours(self::DUREE_VERIFICATION),
        ])->save();

        Mail::to($enAttente->email)->send(new VerifyEmailMail($enAttente, $jeton));

        return $reponse;
    }

    // ---------------------------------------------------------------- mot de passe

    public function resetPassword(Request $request): JsonResponse
    {
        $donnees = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $utilisateur = User::where('email', $donnees['email'])->first();

        // Le backend Node répondait « Aucun utilisateur trouvé avec cet email »,
        // ce qui permettait d'énumérer les comptes. Réponse neutre désormais.
        $reponse = response()->json([
            'message' => 'Si un compte existe pour cette adresse, un email de réinitialisation a été envoyé.',
        ]);

        if (! $utilisateur) {
            return $reponse;
        }

        $jeton = Str::random(64);
        $utilisateur->forceFill([
            'reset_token' => hash('sha256', $jeton),
            'reset_token_expiry' => now()->addHours(self::DUREE_REINITIALISATION),
        ])->save();

        Mail::to($utilisateur->email)->send(new ResetPasswordMail($utilisateur, $jeton));

        return $reponse;
    }

    public function confirmResetPassword(Request $request): JsonResponse
    {
        $donnees = $request->validate([
            'token' => ['required', 'string'],
            'newPassword' => ['required', 'string', 'min:6'],
        ]);

        $utilisateur = User::where('reset_token', hash('sha256', $donnees['token']))->first();

        if (! $utilisateur || ! $utilisateur->reset_token_expiry || now()->greaterThan($utilisateur->reset_token_expiry)) {
            return response()->json(['error' => 'Token invalide ou expiré'], 400);
        }

        $utilisateur->forceFill([
            'password' => Hash::make($donnees['newPassword']),
            'reset_token' => null,
            'reset_token_expiry' => null,
        ])->save();

        // Un mot de passe changé doit invalider les sessions existantes.
        $utilisateur->tokens()->delete();

        return response()->json(['message' => 'Mot de passe réinitialisé avec succès']);
    }
}
