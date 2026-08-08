<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Réserve la route aux administrateurs.
 *
 * Équivalent de `authenticateAdmin` côté Node, à ceci près que l'appartenance
 * est relue en base à chaque requête : le drapeau `is_admin` ne transite pas
 * dans le jeton, si bien qu'une révocation prend effet immédiatement.
 */
class EnsureIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $utilisateur = $request->user();

        if (! $utilisateur) {
            return response()->json(['error' => 'Token d\'accès requis'], 401);
        }

        if (! $utilisateur->is_admin) {
            return response()->json(['error' => 'Accès admin requis'], 403);
        }

        return $next($request);
    }
}
