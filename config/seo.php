<?php

/**
 * Le titre, la description et la banniere de chaque page publique.
 *
 * L'application est rendue par Inertia : toutes ses pages passent par le meme
 * gabarit Blade, qui annoncait donc le meme titre et la meme description a tout
 * le monde — l'accueil, l'inscription, la connexion, les telechargements. Un
 * moteur n'avait aucun moyen de les distinguer, et un lien partage sortait
 * toujours avec le meme apercu.
 *
 * Chaque entree est indexee par le NOM DE ROUTE. Une route absente d'ici prend
 * le defaut : mieux vaut decrire le site que ne rien decrire. Une page qui compte
 * doit figurer ici.
 *
 * `image` est un chemin sous public/. Les bannieres sont fabriquees par
 * `php artisan prepla:fabriquer-og` et versionnees.
 */
return [

    'defaut' => [
        'titre' => "Préparation aux examens de langue avec l'IA",
        'description' => "Prépare ton examen d'anglais, de français ou d'allemand (IELTS, TOEFL, DELF, Goethe…) avec des exercices personnalisés générés par IA, un test de niveau et un parcours adapté. Essai gratuit 7 jours.",
        'image' => 'og/prepla.png',
        'indexable' => true,
    ],

    'pages' => [

        'home' => [
            'titre' => "Préparation aux examens de langue avec l'IA",
            'description' => "Prépare ton examen d'anglais, de français ou d'allemand — IELTS, TOEFL, Cambridge, DELF-DALF, TCF, TEF, Goethe, ÖSD, telc, TestDaF. Test de niveau, parcours adapté et exercices générés par IA. Essai gratuit 7 jours.",
            'image' => 'og/prepla.png',
        ],

        'register' => [
            'titre' => 'Créer un compte — essai gratuit 7 jours',
            'description' => "Ouvre ton compte PrePla : un test de niveau, un parcours construit pour ton examen et des exercices générés à ta mesure. Sept jours d'essai, sans engagement.",
            'image' => 'og/inscription.png',
        ],

        // Une page de connexion n'apporte rien dans un index de moteur : elle
        // n'a pas de contenu a offrir a quelqu'un qui cherche. On la laisse
        // accessible et partageable, mais hors index.
        'login' => [
            'titre' => 'Se connecter',
            'description' => 'Reprends ta préparation là où tu l\'as laissée.',
            'indexable' => false,
        ],

        'password.request' => [
            'titre' => 'Mot de passe oublié',
            'description' => 'Reçois un lien pour choisir un nouveau mot de passe.',
            'indexable' => false,
        ],

        'password.reset' => [
            'titre' => 'Choisir un nouveau mot de passe',
            'description' => 'Choisis un nouveau mot de passe pour ton compte PrePla.',
            'indexable' => false,
        ],

        // « offline » et « offline.app » ne figurent pas ici, a dessein : ce sont
        // des coquilles hors ligne servies depuis le cache du telephone, deja en
        // noindex, et qui ne doivent rien porter de plus que le strict necessaire.
    ],
];
