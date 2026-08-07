<?php

namespace App\Http\Controllers;

use App\Models\Subscriber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Newsletter. Porté depuis les fonctions subscribe / unsubscribe de
 * back/src/controllers/user.controller.js.
 *
 * Le champ `opt_out` manquait au schéma Node : il est ajouté par la migration
 * add_opt_out_to_subscribers, sans quoi le désabonnement reste sans effet.
 */
class NewsletterController extends Controller
{
    public function subscribe(Request $request): JsonResponse
    {
        $email = $request->input('email');

        if (! is_string($email) || ! preg_match('/^[^\s@]+@[^\s@]+\.[^\s@]+$/', $email)) {
            return response()->json(['error' => 'Email invalide'], 400);
        }

        $abonne = Subscriber::find($email);

        if ($abonne) {
            // Réabonnement : on lève l'opt-out sans écraser la date d'origine.
            $abonne->forceFill([
                'opt_out' => false,
                'subscribed_at' => $abonne->subscribed_at ?? now(),
            ])->save();

            return response()->json([
                'success' => true,
                'message' => 'Vous êtes déjà abonné',
            ]);
        }

        Subscriber::create([
            'email' => $email,
            'subscribed_at' => now(),
            'opt_out' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Inscription réussie à la newsletter',
        ], 201);
    }

    public function unsubscribe(Request $request): JsonResponse
    {
        $email = $request->input('email');

        if (! is_string($email) || $email === '') {
            return response()->json(['error' => 'Email requis'], 400);
        }

        // Réponse volontairement identique que l'adresse existe ou non :
        // cela évite de transformer l'endpoint en oracle d'existence de compte.
        Subscriber::whereKey($email)->update(['opt_out' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Désabonnement pris en compte',
        ]);
    }
}
