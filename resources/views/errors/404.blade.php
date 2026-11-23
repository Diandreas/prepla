@include('errors.layout', [
    'image' => '/illustrations/etats/404-perdu.webp',
    'textes' => [
        'fr' => [
            'code' => 'Erreur 404',
            'titre' => 'Cette page s’est perdue',
            'texte' => 'Le renard a cherché partout : l’adresse n’existe pas ou plus. Reprends depuis l’accueil.',
            'texte_sobre' => 'Cette adresse n’existe pas ou plus. Reprends depuis l’accueil.',
            'alt' => 'Le renard '.config('marque.nom').' perdu, une carte à la main',
            'bouton' => 'Retour à l’accueil',
        ],
        'en' => [
            'code' => 'Error 404',
            'titre' => 'This page got lost',
            'texte' => 'The fox looked everywhere: this address does not exist anymore. Start again from the home page.',
            'texte_sobre' => 'This address does not exist anymore. Start again from the home page.',
            'alt' => 'The '.config('marque.nom').' fox, lost, holding a map',
            'bouton' => 'Back to home',
        ],
        'de' => [
            'code' => 'Fehler 404',
            'titre' => 'Diese Seite gibt es nicht',
            'texte' => 'Der Fuchs hat überall gesucht: Diese Adresse gibt es nicht oder nicht mehr. Fang auf der Startseite neu an.',
            'texte_sobre' => 'Diese Adresse gibt es nicht oder nicht mehr. Fang auf der Startseite neu an.',
            'alt' => 'Der '.config('marque.nom').'-Fuchs hat sich verlaufen und hält eine Karte',
            'bouton' => 'Zur Startseite',
        ],
    ],
])
