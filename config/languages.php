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
            'name' => 'Pro',
            'slug' => 'pro',
            'price' => 14.99,
            'period' => 'mois',
            'features' => [
                'Exercices illimités',
                'Générateur d\'exercices IA',
                'Correcteur de rédaction IA',
                'Analyses détaillées',
                '3 langues disponibles',
                'Support prioritaire',
            ],
            'cta' => 'Essai gratuit',
            'highlighted' => true,
        ],
        [
            'name' => 'Premium',
            'slug' => 'premium',
            'price' => 24.99,
            'period' => 'mois',
            'features' => [
                'Tout ce qu\'inclut Pro',
                'Feedback oral par IA',
                'Recommandations IA personnalisées',
                'Parcours d\'apprentissage adaptatif',
                'Simulations d\'examen complet',
                'Tutorat IA individuel',
            ],
            'cta' => 'Essai gratuit',
            'highlighted' => false,
        ],
    ],
];
