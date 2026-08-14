<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminSettingsController;
use App\Http\Controllers\AffiliateController;
use App\Services\SettingsService;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes API
|--------------------------------------------------------------------------
|
| Portage de back/src/routes/*.js. Les chemins suivent ce que consomme
| réellement le frontend Vue, et non le montage du backend Node : celui-ci
| exposait le catalogue sur `/api/` (app.use('/api', productRoutes) combiné à
| router.get('/')), si bien que l'appel `/api/products` du frontend tombait sur
| la route `/:id` et répondait « Produit non trouvé ».
|
| Le préfixe `/api` est ajouté par bootstrap/app.php : ne pas le répéter ici.
|
| Cadence alignée sur l'express-rate-limit du backend Node : 300 requêtes
| par tranche de 15 minutes.
*/

Route::middleware('throttle:300,15')->group(function () {

    // ---- Catalogue public ----
    // `simple-all` et `count` sont déclarés avant `{id}`, sinon ils seraient
    // capturés par le paramètre.
    Route::prefix('products')->group(function () {
        Route::get('/', [ProductController::class, 'index']);
        Route::get('/simple-all', [ProductController::class, 'simpleAll']);
        Route::get('/count', [ProductController::class, 'count']);
        Route::get('/{id}', [ProductController::class, 'show']);
    });

    /*
     * Réglages de la boutique.
     * Seuil de franco, frais de port, annonces : une seule source, partagée
     * par le bandeau, le panier et le calcul de commande. Ces valeurs étaient
     * recopiées dans le frontend, sans garantie qu'elles restent d'accord.
     */
    Route::get('/settings', fn (SettingsService $reglages) => response()->json($reglages->pourLeSite()));

    // Prévisualisation d'un code promotionnel depuis le panier.
    Route::post('/promo/check', [OrderController::class, 'verifierPromo']);

    // ---- Commandes ----
    // La création accepte les visiteurs non connectés : le tunnel ne demande
    // qu'un e-mail. Une commande passée connecté est rattachée au compte.
    Route::post('/orders', [OrderController::class, 'store']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/orders', [OrderController::class, 'index']);
        Route::get('/orders/{reference}', [OrderController::class, 'show']);
    });

    // ---- Newsletter ----
    Route::post('/subscribe', [NewsletterController::class, 'subscribe']);
    Route::post('/unsubscribe', [NewsletterController::class, 'unsubscribe']);

    // Désabonnement en un clic depuis un e-mail. L'URL porte une signature
    // vérifiée par le middleware `signed` : l'adresse en clair ne suffit pas.
    Route::get('/unsubscribe', [NewsletterController::class, 'unsubscribeByLink'])
        ->name('newsletter.unsubscribe')
        ->middleware('signed');

    // ---- Favoris (authentifié) ----
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/likes', [LikeController::class, 'index']);
        Route::post('/likes/toggle', [LikeController::class, 'toggle']);
    });

    // ---- Affiliation ----
    // Le suivi de clic est public : il s'exécute avant toute connexion.
    Route::post('/track-click', [AffiliateController::class, 'trackClick']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/affiliate/request', [AffiliateController::class, 'submitRequest']);
        Route::get('/affiliate/status', [AffiliateController::class, 'status']);
        Route::get('/affiliate/affiliate-stats', [AffiliateController::class, 'stats']);
    });

    // ---- Administration ----
    Route::middleware(['auth:sanctum', 'admin'])->group(function () {
        Route::post('/upload', [AdminController::class, 'upload']);
        Route::post('/send-bulk-email', [AdminController::class, 'sendBulkEmail']);

        Route::prefix('admin')->group(function () {
            Route::get('/products', [AdminController::class, 'listProducts']);
            Route::post('/products', [AdminController::class, 'createProduct']);
            Route::put('/products/{id}', [AdminController::class, 'updateProduct']);
            Route::delete('/products/{id}', [AdminController::class, 'deleteProduct']);

            Route::get('/users', [AdminController::class, 'listUsers']);
            Route::patch('/users/{uid}/role', [AdminController::class, 'updateUserRole']);

            Route::get('/analytics', [AdminController::class, 'analytics']);

            Route::get('/orders', [AdminController::class, 'listOrders']);
            Route::patch('/orders/{reference}/status', [AdminController::class, 'updateOrderStatus']);

            // Réglages de la boutique.
            Route::get('/settings', [AdminSettingsController::class, 'index']);
            Route::put('/settings', [AdminSettingsController::class, 'update']);
            Route::delete('/settings/{champ}', [AdminSettingsController::class, 'reset']);

            // Codes promotionnels.
            Route::get('/promos', [AdminSettingsController::class, 'promos']);
            Route::post('/promos', [AdminSettingsController::class, 'creerPromo']);
            Route::put('/promos/{id}', [AdminSettingsController::class, 'modifierPromo']);
            Route::delete('/promos/{id}', [AdminSettingsController::class, 'supprimerPromo']);
        });

        // Gestion des demandes d'affiliation.
        Route::get('/affiliate/requests/{tab}', [AffiliateController::class, 'listRequests']);
        Route::post('/affiliate/{id}/approve', [AffiliateController::class, 'approve']);
        Route::post('/affiliate/{id}/reject', [AffiliateController::class, 'reject']);
        Route::delete('/affiliate/{id}', [AffiliateController::class, 'destroy']);
    });
});

/*
| Authentification.
|
| Cadence resserrée, comme l'authLimiter du backend Node : ces routes sont
| exposées au bourrinage de mots de passe et à l'énumération de comptes.
*/
Route::prefix('auth')->middleware('throttle:20,15')->group(function () {

    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/verify-email', [AuthController::class, 'verifyEmail']);
    Route::post('/resend-verification', [AuthController::class, 'resendVerification']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    Route::post('/confirm-reset-password', [AuthController::class, 'confirmResetPassword']);

    // Flux OAuth Google (redirection, pas d'ID token fourni par le client).
    Route::get('/google/redirect', [GoogleAuthController::class, 'redirect']);
    Route::get('/google/callback', [GoogleAuthController::class, 'callback']);

});

/*
 * Routes de session déjà authentifiées.
 *
 * Elles sortent de la cadence resserrée ci-dessus : celle-ci vise le
 * bourrinage de mots de passe, or `me` et `logout` exigent un jeton valide.
 * `me` est appelée à chaque démarrage de l'application ; sous 20 requêtes par
 * quart d'heure, une vingtaine de rechargements suffisait à la faire répondre
 * 429 — ce que le frontend prenait pour un jeton expiré.
 */
Route::prefix('auth')->middleware(['auth:sanctum', 'throttle:300,15'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
});
