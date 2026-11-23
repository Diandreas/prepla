@include('errors.layout', [
    'image' => '/illustrations/etats/500-repare.webp',
    'textes' => [
        'fr' => [
            'code' => 'Erreur 500',
            'titre' => 'Petit souci de notre côté',
            'texte' => 'Quelque chose s’est cassé chez nous, pas chez toi. Le renard répare : réessaie dans un instant.',
            'texte_sobre' => 'Quelque chose s’est cassé chez nous, pas chez toi. Réessaie dans un instant.',
            'alt' => 'Le renard '.config('marque.nom').' répare une machine avec une clé',
            'bouton' => 'Retour à l’accueil',
        ],
        'en' => [
            'code' => 'Error 500',
            'titre' => 'A small problem on our side',
            'texte' => 'Something broke on our end, not yours. The fox is fixing it: try again in a moment.',
            'texte_sobre' => 'Something broke on our end, not yours. Try again in a moment.',
            'alt' => 'The '.config('marque.nom').' fox fixing a machine with a wrench',
            'bouton' => 'Back to home',
        ],
        'de' => [
            'code' => 'Fehler 500',
            'titre' => 'Ein kleines Problem bei uns',
            'texte' => 'Bei uns ist etwas kaputtgegangen, nicht bei dir. Der Fuchs repariert es: Versuch es gleich noch einmal.',
            'texte_sobre' => 'Bei uns ist etwas kaputtgegangen, nicht bei dir. Versuch es gleich noch einmal.',
            'alt' => 'Der '.config('marque.nom').'-Fuchs repariert eine Maschine mit einem Schraubenschlüssel',
            'bouton' => 'Zur Startseite',
        ],
    ],
])
