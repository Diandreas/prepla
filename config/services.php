<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'http' => [
        'ca_bundle' => env('HTTP_CA_BUNDLE'),
    ],

    'mistral' => [
        'api_key' => env('MISTRAL_API_KEY'),
        'base_url' => 'https://api.mistral.ai/v1',
    ],

    'forvo' => [
        'key' => env('FORVO_API_KEY'),
    ],

    'deepgram' => [
        'api_key' => env('DEEPGRAM_API_KEY'),
    ],

    'stripe' => [
        // Un identifiant de tarif n'existe que dans le mode où il a été créé : codés
        // en dur, ils faisaient tomber le paiement en 500 (« No such price ») dès que
        // les clés passaient en mode réel. Chaque environnement pointe vers les siens.
        //
        // Aucun repli ici, volontairement : une valeur par défaut ramenait en silence
        // des identifiants d'un autre mode dès que le .env manquait — le défaut même
        // qu'on voulait supprimer, mais devenu invisible. Sans variable définie, la
        // page annonce les montants de référence, le paiement refuse proprement, et
        // `php artisan prepla:check` le signale.
        'prices' => [
            'monthly' => env('STRIPE_PRICE_MONTHLY'),
            'annual' => env('STRIPE_PRICE_ANNUAL'),
        ],
    ],

    'google' => [
        'client_id'     => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect'      => env('GOOGLE_REDIRECT_URI', env('APP_URL') . '/auth/google/callback'),
    ],

];
