<?php

/*
 * Sous-domaines et domaines propres des centres (lot B).
 *
 * Rien a regler dans .env pour garder le comportement actuel : tout se deduit
 * de APP_URL. Les cles CENTRES_* ne servent qu'a decaler la racine (par exemple
 * un serveur de recette) ; voir docs/domaines-centres.md.
 */

$hoteDeLApplication = strtolower((string) parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST)) ?: 'localhost';

return [

    // Les hotes ou l'application se comporte comme aujourd'hui : aucun centre
    // n'est deduit de l'adresse.
    'principaux' => array_values(array_unique([$hoteDeLApplication, 'prepla.mirlab.cloud'])),

    // Les sous-domaines des centres se forment sous cette racine :
    // « ecole » donne ecole.prepla.mirlab.cloud.
    'racine' => strtolower((string) env('CENTRES_DOMAINE_RACINE', '')) ?: $hoteDeLApplication,

    // La cible qu'un domaine propre peut viser en CNAME pour etre verifie.
    'cible_cname' => strtolower((string) env('CENTRES_CIBLE_CNAME', '')) ?: 'prepla.mirlab.cloud',

    // Jamais attribues a un centre, et 404 s'ils arrivent jusqu'ici : ils
    // designent des services, ou pourraient le faire croire.
    'reserves' => [
        'www', 'api', 'admin', 'administration', 'app', 'mail', 'webmail', 'smtp', 'imap', 'pop', 'pop3',
        'ftp', 'sftp', 'ssh', 'vpn', 'staging', 'preprod', 'recette', 'dev', 'test', 'tests', 'qa', 'sandbox',
        'preview', 'status', 'static', 'cdn', 'assets', 'media', 'images', 'img', 'files', 'blog', 'docs',
        'support', 'aide', 'help', 'ns', 'ns1', 'ns2', 'mx', 'autoconfig', 'autodiscover', 'cpanel',
        'cloudpanel', 'panel', 'prepla', 'login', 'auth', 'compte', 'account', 'paiement', 'billing',
        'stripe', 'webhook', 'webhooks', 'localhost',
    ],

    // Duree (secondes) pendant laquelle l'association hote -> centre reste en
    // cache. Toute modification d'un domaine l'efface aussitot.
    'cache_secondes' => 600,
];
