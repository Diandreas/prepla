@include('errors.layout', [
    'image' => '/illustrations/etats/500-repare.webp',
    'textes' => [
        'fr' => [
            'code' => 'Erreur 500',
            'titre' => 'Petit souci de notre côté',
            'texte' => 'Quelque chose s’est cassé chez nous, pas chez toi. Le renard répare : réessaie dans un instant.',
            'alt' => 'Le renard PrePla répare une machine avec une clé',
            'bouton' => 'Retour à l’accueil',
        ],
        'en' => [
            'code' => 'Error 500',
            'titre' => 'A small problem on our side',
            'texte' => 'Something broke on our end, not yours. The fox is fixing it: try again in a moment.',
            'alt' => 'The PrePla fox fixing a machine with a wrench',
            'bouton' => 'Back to home',
        ],
    ],
])
