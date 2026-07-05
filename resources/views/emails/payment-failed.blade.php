<x-mail::message>
# Ton dernier paiement n'a pas pu être traité

Bonjour {{ $userName }},

Nous n'avons pas pu prélever le paiement de ton abonnement PrePla Plus. Cela arrive généralement quand une carte a expiré ou que les fonds sont insuffisants.

Pour éviter toute interruption de ton accès Premium, mets à jour ton moyen de paiement dès que possible.

<x-mail::button :url="$subscriptionUrl" color="primary">
Mettre à jour mon paiement
</x-mail::button>

Si tu penses qu'il s'agit d'une erreur, n'hésite pas à nous contacter.

L'équipe PrePla
</x-mail::message>
