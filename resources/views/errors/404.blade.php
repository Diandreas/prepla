@include('errors.layout', [
    'image' => '/illustrations/etats/404-perdu.webp',
    'textes' => [
        'fr' => [
            'code' => 'Erreur 404',
            'titre' => 'Cette page s’est perdue',
            'texte' => 'Le renard a cherché partout : l’adresse n’existe pas ou plus. Reprends depuis l’accueil.',
            'alt' => 'Le renard PrePla perdu, une carte à la main',
            'bouton' => 'Retour à l’accueil',
        ],
        'en' => [
            'code' => 'Error 404',
            'titre' => 'This page got lost',
            'texte' => 'The fox looked everywhere: this address does not exist anymore. Start again from the home page.',
            'alt' => 'The PrePla fox, lost, holding a map',
            'bouton' => 'Back to home',
        ],
    ],
])
