<?php

/**
 * Réglages commerciaux.
 *
 * Ces valeurs étaient jusqu'ici recopiées dans le frontend — le seuil de
 * franco apparaissait dans le bandeau d'annonces et dans le panier, sans
 * garantie qu'ils restent d'accord. Elles sont désormais définies ici et
 * exposées par GET /api/settings, qui devient la seule source.
 *
 * Une table de réglages modifiable depuis l'administration prendra la relève ;
 * elle lira les valeurs ci-dessous comme défauts.
 */
return [
    'devise' => 'EUR',

    'livraison' => [
        // Seuil de gratuité, en euros.
        'franco' => (float) env('BOUTIQUE_FRANCO', 150),

        'modes' => [
            'standard' => [
                'libelle' => 'Standard · 2 à 3 jours',
                'detail' => 'Réception estimée sous 3 jours ouvrés',
                'prix' => (float) env('BOUTIQUE_PORT_STANDARD', 6.9),
                'delai_jours' => 3,
            ],
            'express' => [
                'libelle' => 'Express · demain avant 13 h',
                'detail' => 'Commande passée avant 15 h',
                'prix' => (float) env('BOUTIQUE_PORT_EXPRESS', 12),
                'delai_jours' => 1,
            ],
        ],
    ],

    'tva' => (float) env('BOUTIQUE_TVA', 0.20),

    // Messages du bandeau, jusqu'ici écrits en dur dans AnnouncementBar.vue.
    'annonces' => [
        'Livraison offerte dès 150 €',
        'Retours gratuits sous 30 jours',
        'Paiement en 3 fois',
    ],

    'retours' => [
        'jours' => 30,
    ],
];
