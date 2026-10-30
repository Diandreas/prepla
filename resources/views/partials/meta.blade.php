{{--
    Les balises de partage et de referencement, en un seul endroit.

    Elles vivent dans le Blade et non dans React : sans rendu serveur, les robots
    des reseaux sociaux (WhatsApp, Facebook, LinkedIn) n'executent pas le
    JavaScript et ne verraient rien du tout.

    Chaque page passe ce qui la distingue ; le reste prend la valeur par defaut.
    Une page qui ne passe rien decrit le site, ce qui vaut mieux que de ne rien
    decrire — mais une page qui compte doit passer son propre titre, sinon tous
    les liens partages se ressemblent.

    @param  string  $titre         Titre de la page, sans le nom du site.
    @param  string  $description   Une a deux phrases. C'est ce que lit l'humain.
    @param  string  $image         URL absolue d'une banniere 1200x630.
    @param  string  $type          « website » ou « article ».
    @param  bool    $indexable     false pour tenir la page hors des moteurs.
--}}
@php
    $titre = $titre ?? "Préparation aux examens de langue avec l'IA";
    $description = $description ?? "Prépare ton examen d'anglais, de français ou d'allemand (IELTS, TOEFL, DELF, Goethe…) avec des exercices personnalisés générés par IA, un test de niveau et un parcours adapté. Essai gratuit 7 jours.";
    $image = $image ?? url('/og/prepla.png');
    $type = $type ?? 'website';
    $indexable = $indexable ?? true;
    // `url()->current()` laisse tomber la query : les trois pages par langue se
    // seraient toutes declarees copies de /structures, et aucune n'aurait ete
    // indexee pour ses propres examens. Une page qui compte sur sa query passe
    // donc son canonique.
    $canonique = $canonique ?? url()->current();
    $complet = $titre.' — PrePla';
@endphp

<meta name="description" content="{{ $description }}">
<link rel="canonical" href="{{ $canonique }}">

@unless ($indexable)
    <meta name="robots" content="noindex, follow">
@endunless

<meta property="og:type" content="{{ $type }}">
<meta property="og:site_name" content="PrePla">
<meta property="og:title" content="{{ $complet }}">
<meta property="og:description" content="{{ $description }}">
<meta property="og:url" content="{{ $canonique }}">
<meta property="og:locale" content="fr_FR">

{{-- Les reseaux attendent du 1200x630. L'icone carree de la PWA, qui servait
     ici, etait recadree en vignette minuscule. --}}
<meta property="og:image" content="{{ $image }}">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="{{ $titre }} — PrePla">

{{-- « summary_large_image » affiche la banniere en grand ; « summary » la
     reduisait a une vignette carree a cote du texte. --}}
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $complet }}">
<meta name="twitter:description" content="{{ $description }}">
<meta name="twitter:image" content="{{ $image }}">
<meta name="twitter:image:alt" content="{{ $titre }} — PrePla">
