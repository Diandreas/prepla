{{--
    Couleurs d'un centre, en variables CSS. « html:root » et « html.dark » sont
    plus precis que les « :root » et « .dark » d'app.css : ils l'emportent quel
    que soit l'ordre de chargement. Le meme element est mis a jour par
    resources/js/lib/marque-centre.js (meme id, meme contenu).
    Les valeurs viennent du service Marque, verifiees au format #RRGGBB.
--}}
@php($theme = is_array($marque ?? null) ? ($marque['theme'] ?? null) : null)
@if (is_array($theme) && collect($theme)->every(fn ($c) => is_string($c) && preg_match('/^#[0-9A-Fa-f]{6}$/', $c)))
<style id="marque-centre">
html:root{--primary:{{ $theme['primaire'] }};--primary-foreground:{{ $theme['texte_sur_primaire'] }};--ring:{{ $theme['primaire'] }};--sidebar-primary:{{ $theme['primaire'] }};--sidebar-primary-foreground:{{ $theme['texte_sur_primaire'] }};--sidebar-ring:{{ $theme['primaire'] }};--marque-accent:{{ $theme['accent'] }}}
html.dark{--primary:{{ $theme['primaire_sombre'] }};--primary-foreground:{{ $theme['texte_sur_primaire_sombre'] }};--ring:{{ $theme['primaire_sombre'] }};--sidebar-primary:{{ $theme['primaire_sombre'] }};--sidebar-primary-foreground:{{ $theme['texte_sur_primaire_sombre'] }};--sidebar-ring:{{ $theme['primaire_sombre'] }};--marque-accent:{{ $theme['accent_sombre'] }}}
</style>
@endif
