<x-mail::message>
# Merci {{ $userName }}, ton paiement est confirmé !

Nous avons bien reçu ton paiement de **{{ $amount }}** pour PrePla Plus.

@if ($hostedInvoiceUrl || $invoicePdfUrl)
<x-mail::button :url="$invoicePdfUrl ?? $hostedInvoiceUrl" color="primary">
Télécharger ma facture (PDF)
</x-mail::button>
@endif

@if ($hostedInvoiceUrl && $invoicePdfUrl)
Tu peux aussi [consulter la facture en ligne]({{ $hostedInvoiceUrl }}).
@endif

Retrouve l'historique de tes factures à tout moment depuis [ton espace abonnement]({{ $subscriptionUrl }}).

Bonne préparation !

L'équipe PrePla
</x-mail::message>
