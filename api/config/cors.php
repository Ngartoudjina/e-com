<?php

/*
|--------------------------------------------------------------------------
| Origines autorisées à appeler l'API
|--------------------------------------------------------------------------
|
| Sans ce fichier, Laravel appliquait son défaut : `allowed_origins => ['*']`.
| N'importe quel site pouvait donc appeler l'API depuis le navigateur d'un
| visiteur. Les jetons Sanctum n'étant pas envoyés automatiquement en
| cross-origin, la portée restait limitée — mais rien ne justifie de laisser
| l'ouverture.
|
| En production, seule l'origine du site est admise. En développement, les
| deux ports habituels de Vite sont ajoutés, faute de quoi le catalogue
| resterait vide sans erreur visible à l'écran.
|
*/

$origines = array_values(array_filter([
    rtrim((string) env('FRONTEND_URL', ''), '/') ?: null,
]));

if (env('APP_ENV') !== 'production') {
    $origines = array_merge($origines, [
        'http://localhost:5173',
        'http://127.0.0.1:5173',
        'http://localhost:4173',
    ]);
}

return [

    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    // Une liste vide fermerait l'API à tout navigateur : mieux vaut le
    // défaut permissif, visible dans les journaux, qu'un site muet dont on
    // ne comprend pas pourquoi il n'affiche rien.
    'allowed_origins' => $origines ?: ['*'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    // Le frontend ne lit aucun en-tête de réponse particulier.
    'exposed_headers' => [],

    // Le contrôle préalable est valable une journée : sans cela, chaque
    // requête non simple en déclenche un second aller-retour.
    'max_age' => 86400,

    /*
     * L'authentification passe par un jeton porté en en-tête Authorization,
     * jamais par un cookie de session : les identifiants de connexion n'ont
     * pas à traverser l'origine.
     */
    'supports_credentials' => false,

];
