<?php

return [
    'languages' => [
        [
            'slug' => 'english',
            'name' => 'English',
            'native_name' => 'English',
            'flag' => '🇬🇧',
            'exams' => [
                ['slug' => 'ielts', 'name' => 'IELTS', 'levels' => null],
                ['slug' => 'toefl', 'name' => 'TOEFL', 'levels' => null],
                ['slug' => 'cambridge', 'name' => 'Cambridge', 'levels' => ['A2 Key', 'B1 Preliminary', 'B2 First', 'C1 Advanced', 'C2 Proficiency']],
            ],
        ],
        [
            'slug' => 'french',
            'name' => 'Français',
            'native_name' => 'Français',
            'flag' => '🇫🇷',
            'exams' => [
                ['slug' => 'tcf', 'name' => 'TCF', 'levels' => null],
                ['slug' => 'tef', 'name' => 'TEF', 'levels' => null],
                ['slug' => 'delf-dalf', 'name' => 'DELF/DALF', 'levels' => ['A1', 'A2', 'B1', 'B2', 'C1', 'C2']],
            ],
        ],
        [
            'slug' => 'german',
            'name' => 'Allemand',
            'native_name' => 'Deutsch',
            'flag' => '🇩🇪',
            'exams' => [
                ['slug' => 'goethe', 'name' => 'Goethe-Zertifikat', 'levels' => ['A1', 'A2', 'B1', 'B2', 'C1', 'C2']],
                ['slug' => 'testdaf', 'name' => 'TestDaF', 'levels' => null],
            ],
        ],
    ],

    // Aligné sur les vrais tarifs Stripe (SubscriptionController::PRICE_MONTHLY/
    // PRICE_ANNUAL) — avant, cette page annonçait 3 plans (Gratuit/Pro 14.99€/
    // Premium 24.99€) qui n'ont jamais existé côté paiement réel (un seul niveau
    // "PrePla Plus" à 9.99€/mois ou 79.99€/an). Un visiteur cliquant "Essai
    // gratuit" sur le plan Pro découvrait un tarif totalement différent à
    // l'inscription — trompeur, corrigé en simplifiant à Gratuit/Premium.
    'pricing' => [
        [
            'name' => 'Gratuit',
            'slug' => 'free',
            'price' => 0,
            'period' => null,
            'features' => [
                '3 exercices par jour',
                'Suivi de progression basique',
                'Classement communautaire',
                '1 langue',
            ],
            'cta' => 'Commencer gratuitement',
            'highlighted' => false,
        ],
        [
            'name' => 'PrePla Plus',
            'slug' => 'premium',
            'price' => 9.99,
            'period' => 'mois',
            'features' => [
                'Exercices illimités, toutes langues',
                'Correction IA instantanée pour rédactions et oraux',
                'Expliqueur d\'erreurs illimité sur chaque exercice',
                'Générateur d\'exercices IA sans limites quotidiennes',
                'Statistiques de progression avancées',
                'Audio TTS pour tous les exercices d\'écoute',
            ],
            'cta' => 'Essai gratuit',
            'highlighted' => true,
        ],
    ],
];
