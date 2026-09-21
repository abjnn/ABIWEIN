<?php

/**
 * Paramètres propres à ABIWEIN.
 *
 * Les identifiants de déploiement restent dans les variables secrètes de
 * l'hébergeur : aucune adresse ni aucun mot de passe public n'est inscrit ici.
 */
return [
    // Cette option autorise l'installation initiale du scénario de démonstration en production.
    'deployment_demo_enabled' => env('ABIWEIN_ENABLE_PRODUCTION_DEMO', false),

    // Les valeurs locales restent pratiques, tandis que la production exige des secrets explicites.
    'demo_users' => [
        'administrator' => [
            'email' => env('ABIWEIN_DEMO_ADMIN_EMAIL', env('APP_ENV') === 'production' ? null : 'nadege.nkomo@abiwein.local'),
            'password' => env('ABIWEIN_DEMO_ADMIN_PASSWORD', env('APP_ENV') === 'production' ? null : 'Nadege#Abiwein26!'),
        ],
        'farm_manager' => [
            'email' => env('ABIWEIN_DEMO_MANAGER_EMAIL', env('APP_ENV') === 'production' ? null : 'alain.tchoumi@abiwein.local'),
            'password' => env('ABIWEIN_DEMO_MANAGER_PASSWORD', env('APP_ENV') === 'production' ? null : 'Ferme#Tchoumi26!'),
        ],
        'veterinarian' => [
            'email' => env('ABIWEIN_DEMO_VET_EMAIL', env('APP_ENV') === 'production' ? null : 'mireille.ngono@abiwein.local'),
            'password' => env('ABIWEIN_DEMO_VET_PASSWORD', env('APP_ENV') === 'production' ? null : 'Sante#Ngono2026!'),
        ],
    ],
];
