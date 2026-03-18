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
            'slug' => 'spanish',
            'name' => 'Español',
            'native_name' => 'Español',
            'flag' => '🇪🇸',
            'exams' => [
                ['slug' => 'dele', 'name' => 'DELE', 'levels' => ['A1', 'A2', 'B1', 'B2', 'C1', 'C2']],
                ['slug' => 'siele', 'name' => 'SIELE', 'levels' => null],
            ],
        ],
        [
            'slug' => 'chinese',
            'name' => 'Chinois Mandarin',
            'native_name' => '中文',
            'flag' => '🇨🇳',
            'exams' => [
                ['slug' => 'hsk', 'name' => 'HSK', 'levels' => ['HSK 1', 'HSK 2', 'HSK 3', 'HSK 4', 'HSK 5', 'HSK 6']],
            ],
        ],
        [
            'slug' => 'arabic',
            'name' => 'Arabe',
            'native_name' => 'العربية',
            'flag' => '🇸🇦',
            'exams' => [
                ['slug' => 'alpt', 'name' => 'ALPT', 'levels' => null],
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
        [
            'slug' => 'portuguese',
            'name' => 'Portugais',
            'native_name' => 'Português',
            'flag' => '🇧🇷',
            'exams' => [
                ['slug' => 'celpe-bras', 'name' => 'CELPE-Bras', 'levels' => null],
            ],
        ],
        [
            'slug' => 'japanese',
            'name' => 'Japonais',
            'native_name' => '日本語',
            'flag' => '🇯🇵',
            'exams' => [
                ['slug' => 'jlpt', 'name' => 'JLPT', 'levels' => ['N5', 'N4', 'N3', 'N2', 'N1']],
            ],
        ],
        [
            'slug' => 'korean',
            'name' => 'Coréen',
            'native_name' => '한국어',
            'flag' => '🇰🇷',
            'exams' => [
                ['slug' => 'topik', 'name' => 'TOPIK', 'levels' => ['TOPIK I', 'TOPIK II']],
            ],
        ],
        [
            'slug' => 'russian',
            'name' => 'Russe',
            'native_name' => 'Русский',
            'flag' => '🇷🇺',
            'exams' => [
                ['slug' => 'torfl', 'name' => 'TORFL', 'levels' => ['TEL', 'TBL', 'TRKI-1', 'TRKI-2', 'TRKI-3', 'TRKI-4']],
            ],
        ],
    ],

    'pricing' => [
        [
            'name' => 'Free',
            'slug' => 'free',
            'price' => 0,
            'period' => null,
            'features' => [
                '3 exercises per day',
                'Basic progress tracking',
                'Community leaderboard',
                '1 language',
            ],
            'cta' => 'Get Started',
            'highlighted' => false,
        ],
        [
            'name' => 'Pro',
            'slug' => 'pro',
            'price' => 14.99,
            'period' => 'month',
            'features' => [
                'Unlimited exercises',
                'AI exercise generator',
                'AI writing corrector',
                'Detailed analytics',
                'All 10 languages',
                'Priority support',
            ],
            'cta' => 'Start Free Trial',
            'highlighted' => true,
        ],
        [
            'name' => 'Premium',
            'slug' => 'premium',
            'price' => 24.99,
            'period' => 'month',
            'features' => [
                'Everything in Pro',
                'AI speaking feedback',
                'AI study recommendations',
                'Personalized learning path',
                'Mock exam simulations',
                '1-on-1 AI tutoring',
            ],
            'cta' => 'Start Free Trial',
            'highlighted' => false,
        ],
    ],
];
