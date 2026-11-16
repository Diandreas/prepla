<?php

/*
 * La marque de l'INSTANCE (marque blanche) : le meme depot sert plusieurs sites,
 * chacun avec son .env. Sans aucune variable MARQUE_*, c'est PrePla, a
 * l'identique. A ne pas confondre avec la marque d'un centre (personnalisation
 * en base, app/Services/Center/Marque.php), qui se pose par-dessus.
 *
 * Voir docs/marque-blanche.md. Verifier avec : php artisan marque:verifier
 */

$liste = static fn (?string $valeur): array => array_values(array_filter(array_map(
    static fn ($v) => strtolower(trim($v)),
    explode(',', (string) $valeur),
), static fn ($v) => $v !== ''));

return [

    'nom' => env('MARQUE_NOM', 'PrePla'),
    'nom_court' => env('MARQUE_NOM_COURT') ?: env('MARQUE_NOM', 'PrePla'),
    'slogan' => env('MARQUE_SLOGAN', 'Préparation aux examens de langue'),

    // Chemins publics (sous public/).
    'logo' => env('MARQUE_LOGO', '/icons/logo.png?v=4'),
    'logo_carre' => env('MARQUE_LOGO_CARRE', '/icons/pwa-512-v4.png'),
    'favicon' => env('MARQUE_FAVICON', '/icons/pwa-192-v4.png'),
    'icone_192' => env('MARQUE_ICONE_192', '/icons/pwa-192-v4.png'),
    'icone_512' => env('MARQUE_ICONE_512', '/icons/pwa-512-v4.png'),
    'icone_maskable' => env('MARQUE_ICONE_MASKABLE', '/icons/pwa-maskable-512-v4.png'),
    'icone_apple' => env('MARQUE_ICONE_APPLE', '/icons/apple-touch-icon-180-v4.png'),

    // #RRGGBB. Primaire : boutons et liens ; secondaire : titres, barre du navigateur.
    'couleur_primaire' => env('MARQUE_COULEUR_PRIMAIRE', '#4A90E2'),
    'couleur_secondaire' => env('MARQUE_COULEUR_SECONDAIRE', '#0B2D63'),

    'email_contact' => env('MARQUE_EMAIL_CONTACT', 'contact@prepla.app'),
    'site' => env('MARQUE_SITE'),

    // Langues de l'interface proposees (fr, en, de) ; vide = toutes.
    'langues_interface' => $liste(env('MARQUE_LANGUES_INTERFACE')),
    'langue_defaut' => env('MARQUE_LANGUE_DEFAUT'),

    // Slugs d'examens proposes (ex. "goethe,telc") ; vide = tous.
    'examens' => $liste(env('MARQUE_EXAMENS')),

    'masquer_kids' => (bool) env('MARQUE_MASQUER_KIDS', false),
    'masquer_prix_publics' => (bool) env('MARQUE_MASQUER_PRIX_PUBLICS', false),
    'mascotte' => (bool) env('MARQUE_MASCOTTE', true),

];
