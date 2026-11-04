{{--
    Notification envoyee au nom d'un centre : meme contenu que le modele de
    Laravel (notifications::email), mais l'en-tete et le pied portent le nom du
    centre quand il masque la marque PrePla ($nomDuSite).
--}}
<x-mail::layout>
<x-slot:header>
<x-mail::header :url="config('app.url')">
{{ $nomDuSite }}
</x-mail::header>
</x-slot:header>

@if (! empty($greeting))
# {{ $greeting }}
@endif

@foreach ($introLines as $line)
{{ $line }}

@endforeach

@isset($actionText)
<x-mail::button :url="$actionUrl" color="primary">
{{ $actionText }}
</x-mail::button>
@endisset

@foreach ($outroLines as $line)
{{ $line }}

@endforeach

@if (! empty($salutation))
{{ $salutation }}
@endif

@isset($actionText)
<x-slot:subcopy>
<x-mail::subcopy>
Si le bouton « {{ $actionText }} » ne fonctionne pas, copiez cette adresse dans votre navigateur : <span class="break-all">[{{ $displayableActionUrl }}]({{ $actionUrl }})</span>
</x-mail::subcopy>
</x-slot:subcopy>
@endisset

<x-slot:footer>
<x-mail::footer>
© {{ date('Y') }} {{ $nomDuSite }}
</x-mail::footer>
</x-slot:footer>
</x-mail::layout>
