<?php

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

    // ---- Favoris (authentifié) ----
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/likes', [LikeController::class, 'index']);
        Route::post('/likes/toggle', [LikeController::class, 'toggle']);
    });
});
