<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AffiliateController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\NewsletterController;
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

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});
