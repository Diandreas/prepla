<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Emplacement des pages
    |--------------------------------------------------------------------------
    |
    | Le paquet cherche par défaut dans resources/js/Pages, avec un P majuscule,
    | alors que ce projet range ses pages dans resources/js/pages. Sur macOS le
    | système de fichiers ignore la casse et personne ne voyait rien ; sur Linux —
    | l'intégration continue et le serveur de production — la résolution échouait
    | et sept tests d'affichage tombaient sur « Inertia page component file does
    | not exist », sans rapport avec un vrai défaut de l'application.
    |
    */

    'page_paths' => [
        resource_path('js/pages'),
    ],

    'page_extensions' => [
        'js',
        'jsx',
        'ts',
        'tsx',
    ],

    'testing' => [
        'ensure_pages_exist' => true,
        'page_paths' => [
            resource_path('js/pages'),
        ],
        'page_extensions' => [
            'js',
            'jsx',
            'ts',
            'tsx',
        ],
    ],

];
