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
                // Les six niveaux sont decrits, chacun verifie sur ses
                // Durchfuehrungsbestimmungen officielles (voir config/exams/osd.php).
                ['slug' => 'osd', 'name' => 'ÖSD Zertifikat', 'levels' => ['A1', 'A2', 'B1', 'B2', 'C1', 'C2']],
                ['slug' => 'testdaf', 'name' => 'TestDaF', 'levels' => null],
                // L'echelle generale de telc, A1 a C2, verifiee page par page sur
                // telc.net le 2026-10-10. Les vingt-quatre epreuves specialisees
                // (Beruf, Pflege, Medizin, Hochschule...) ne sont pas decrites.
                ['slug' => 'telc', 'name' => 'telc Deutsch', 'levels' => ['A1', 'A2', 'B1', 'B2', 'C1', 'C2']],
            ],
        ],
    ],
];
