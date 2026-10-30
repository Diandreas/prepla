<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Structures des examens — PrePla</title>
    <meta name="description" content="Les structures officielles des examens de langue utilisées par PrePla, épreuve par épreuve et partie par partie, ouvertes à la relecture.">
    <meta name="robots" content="noindex">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=cormorant-garamond:700i|plus-jakarta-sans:400,500,600,700" rel="stylesheet" />
    <style>
        :root {
            color-scheme: light dark;
            --fond: #f5f8fc;
            --encre: #13233f;
            --carte: #ffffff;
            --trait: rgba(19, 35, 63, .12);
            --doux: rgba(19, 35, 63, .62);
            --lien: #3B82E0;
            --or: #F5A623;
            --officiel: #0f7b4f;
            --officiel-fond: rgba(15, 123, 79, .1);
            --reconstitue: #9a6207;
            --reconstitue-fond: rgba(245, 166, 35, .14);
        }
        @media (prefers-color-scheme: dark) {
            :root:not([data-theme="light"]) {
                --fond: #0b1322;
                --encre: #eef3fb;
                --carte: #121c2f;
                --trait: rgba(238, 243, 251, .14);
                --doux: rgba(238, 243, 251, .66);
                --lien: #7db3f5;
                --officiel: #5fd39b;
                --officiel-fond: rgba(95, 211, 155, .14);
                --reconstitue: #ffc968;
                --reconstitue-fond: rgba(255, 201, 104, .16);
            }
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
            background: var(--fond);
            color: var(--encre);
            line-height: 1.65;
        }
        .wrap { max-width: 1040px; margin: 0 auto; padding: 2.5rem 1rem 5rem; }
        a { color: var(--lien); }
        .brand { display: inline-flex; align-items: baseline; gap: .1rem; text-decoration: none; margin-bottom: 1.75rem; }
        .brand span:first-child,
        .brand .gold { font-family: 'Cormorant Garamond', serif; font-style: italic; font-weight: 700; font-size: 1.6rem; }
        .brand span:first-child { color: inherit; }
        .brand .gold { color: var(--or); }
        h1 { font-size: 1.7rem; margin: 0 0 .5rem; line-height: 1.25; }
        .chapeau { color: var(--doux); max-width: 62ch; margin: 0 0 1.5rem; }
        .legende { background: var(--carte); border: 1px solid var(--trait); border-radius: 12px; padding: 1rem 1.15rem; margin-bottom: 2rem; font-size: .9rem; }
        .legende p { margin: .35rem 0; }
        .onglets { display: flex; flex-wrap: wrap; gap: .5rem; margin-bottom: 2.25rem; }
        .onglet {
            display: inline-block; padding: .45rem .95rem; border-radius: 999px;
            border: 1px solid var(--trait); background: var(--carte);
            text-decoration: none; color: inherit; font-size: .9rem; font-weight: 500;
        }
        .onglet[aria-current="true"] { background: var(--encre); color: var(--fond); border-color: var(--encre); }
        .langue > h2 { font-size: 1.3rem; margin: 2.5rem 0 1rem; }
        .examen { background: var(--carte); border: 1px solid var(--trait); border-radius: 14px; padding: 1.25rem 1.35rem; margin-bottom: 1.5rem; }
        .examen > h3 { margin: 0 0 .15rem; font-size: 1.15rem; }
        .sources { font-size: .82rem; color: var(--doux); margin: 0 0 .75rem; overflow-wrap: anywhere; }
        /* Une source par ligne : les titres de documents sont longs, et sur un
           telephone ils debordaient de l'ecran. */
        .sources span { display: block; }
        details { border-top: 1px solid var(--trait); }
        details > summary {
            cursor: pointer; padding: .7rem 0; font-weight: 600; font-size: .98rem;
            display: flex; flex-wrap: wrap; gap: .5rem; align-items: baseline;
        }
        summary .meta { font-weight: 400; font-size: .85rem; color: var(--doux); }
        .module { padding: .25rem 0 1.25rem; }
        .module > h5 { margin: 1.1rem 0 .3rem; font-size: .98rem; }
        .module .chiffres { font-size: .84rem; color: var(--doux); margin: 0 0 .5rem; }
        .module .desc { font-size: .9rem; margin: 0 0 .6rem; }
        .tableau { width: 100%; border-collapse: collapse; font-size: .87rem; }
        .tableau th, .tableau td { text-align: left; padding: .45rem .55rem; border-bottom: 1px solid var(--trait); vertical-align: top; }
        .tableau th { font-size: .78rem; text-transform: uppercase; letter-spacing: .04em; color: var(--doux); font-weight: 600; }
        .tableau td.num { width: 2.2rem; color: var(--doux); font-variant-numeric: tabular-nums; }
        .tableau td.n { white-space: nowrap; font-variant-numeric: tabular-nums; }
        code { font-family: ui-monospace, Menlo, Consolas, monospace; font-size: .85em; background: var(--fond); padding: .1rem .3rem; border-radius: 4px; }
        .pastille { display: inline-block; font-size: .72rem; font-weight: 600; padding: .1rem .45rem; border-radius: 999px; white-space: nowrap; }
        .pastille.officiel { color: var(--officiel); background: var(--officiel-fond); }
        .pastille.reconstitue { color: var(--reconstitue); background: var(--reconstitue-fond); }
        .pastille.exercices { color: var(--lien); background: rgba(59, 130, 224, .12); }
        .choix { font-size: .8rem; color: var(--doux); display: block; }
        /* L'exercice reel, deplie sous sa partie. */
        .ligne-exemple td { padding: 0 0 .5rem; border-bottom: 1px solid var(--trait); }
        details.exemple { border: 0; }
        details.exemple > summary { padding: .35rem 0; font-size: .85rem; font-weight: 600; color: var(--lien); }
        .corps-exemple { padding: .35rem 0 .75rem; font-size: .88rem; }
        .titre-exemple { font-weight: 600; margin: .2rem 0; }
        .consigne { font-style: italic; color: var(--doux); margin: .2rem 0 .6rem; }
        .passage {
            white-space: pre-wrap; background: var(--fond); border: 1px solid var(--trait);
            border-radius: 8px; padding: .75rem .9rem; margin: 0 0 .8rem; line-height: 1.6;
        }
        .passage.audio { border-style: dashed; }
        .etiquette { display: block; font-size: .72rem; text-transform: uppercase; letter-spacing: .05em; color: var(--doux); margin-bottom: .35rem; }
        .questions { margin: 0; padding-left: 1.2rem; }
        .questions > li { margin-bottom: 1rem; }
        .enonce { margin: 0 0 .35rem; font-weight: 500; }
        .options { list-style: none; margin: .3rem 0; padding: 0; display: flex; flex-wrap: wrap; gap: .35rem; }
        .options li { border: 1px solid var(--trait); border-radius: 6px; padding: .15rem .55rem; font-size: .85rem; }
        .options li.juste { border-color: var(--officiel); background: var(--officiel-fond); color: var(--officiel); font-weight: 600; }
        .reponse { margin: .3rem 0 .2rem; }
        .explication { margin: .2rem 0 0; color: var(--doux); }
        .champs { margin: .3rem 0; color: var(--doux); }
        .pied { margin-top: 3rem; padding-top: 1.25rem; border-top: 1px solid var(--trait); font-size: .88rem; color: var(--doux); }
        @media (max-width: 640px) {
            .tableau thead { display: none; }
            .tableau tr { display: block; padding: .6rem 0; border-bottom: 1px solid var(--trait); }
            .tableau td { display: block; border: 0; padding: .1rem 0; }
            /* La largeur fixe du desktop coupait « Partie » de son numero. */
            .tableau td.num { width: auto; font-weight: 600; }
            .tableau td.num::before { content: 'Partie '; }
        }
        @media print {
            body { background: #fff; }
            .onglets, .pied a { display: none; }
            details { break-inside: avoid; }
            details > summary { list-style: none; }
        }
    </style>
</head>
<body>
<div class="wrap">
    <a href="/" class="brand"><span>Pre</span><span class="gold">Pla</span></a>

    <h1>Structures des examens</h1>
    <p class="chapeau">
        Voici, telles que notre application les utilise, les structures des examens de langue :
        chaque épreuve, sa durée, et chacune de ses parties avec le nombre de questions attendu.
        Cette page est ouverte sans compte, pour qu'un enseignant puisse la relire en face des
        documents officiels de l'organisme. Si vous trouvez un écart, c'est nous qui avons tort —
        écrivez-nous.
    </p>

    <div class="legende">
        <p><span class="pastille officiel">officiel</span>
            la partie est vérifiée dans le document publié par l'organisme, avec ses chiffres.</p>
        <p><span class="pastille reconstitue">reconstituée</span>
            la partie est reconstituée d'après des sujets ou des manuels : c'est là qu'une erreur
            est la plus probable, et là que votre relecture nous est la plus utile.</p>
        <p style="margin-top:.6rem">
            Là où nous avons écrit l'épreuve nous-mêmes, la partie porte un lien
            <strong>« Voir l'exercice tel qu'il est servi »</strong> : il déplie le texte entier,
            chaque question, la bonne réponse et son explication — exactement ce que reçoit
            l'apprenant. Une structure se vérifie sur le papier ; un exercice, non.
        </p>
        <p style="color:var(--doux);margin-top:.6rem">
            La durée totale d'un examen est celle que l'organisme annonce. Elle ne correspond
            presque jamais à la somme de ses épreuves, parce que l'oral se passe souvent un autre
            jour : ce n'est pas une erreur de calcul.
        </p>
    </div>

    <nav class="onglets">
        <a class="onglet" href="{{ route('structures') }}" @if (! $choisie) aria-current="true" @endif>Toutes les langues</a>
        @foreach ($langues as $langue)
            <a class="onglet"
               href="{{ route('structures', ['langue' => $langue['slug']]) }}"
               @if ($choisie === $langue['slug']) aria-current="true" @endif>
                {{ $langue['name'] }}
            </a>
        @endforeach
    </nav>

    @foreach ($retenues as $langue)
        <section class="langue">
            <h2 id="{{ $langue['slug'] }}">{{ $langue['name'] }}</h2>

            @foreach ($langue['exams'] as $examen)
                <article class="examen">
                    <h3>{{ $examen['name'] }}</h3>

                    @if ($examen['verifications'])
                        <p class="sources">
                            Sources relevées :
                            @foreach ($examen['verifications'] as $palier => $source)
                                <span>{{ $palier }} — {{ $source['document'] ?? '' }}@if (! empty($source['date'])) ({{ $source['date'] }})@endif</span>
                            @endforeach
                        </p>
                    @endif

                    @forelse ($examen['paliers'] as $palier)
                        <details @if (count($examen['paliers']) === 1) open @endif>
                            <summary>
                                {{ $palier['name'] }}
                                <span class="meta">
                                    {{ count($palier['sections']) }} épreuve{{ count($palier['sections']) > 1 ? 's' : '' }}
                                    @if ($palier['duree']) · durée annoncée {{ $palier['duree'] }} min @endif
                                </span>
                                @if (! empty($palier['exemples']))
                                    <span class="pastille exercices">{{ $palier['exemples'] }} exercice{{ $palier['exemples'] > 1 ? 's' : '' }} à lire</span>
                                @endif
                            </summary>

                            <div class="module">
                                @foreach ($palier['sections'] as $section)
                                    <h5>{{ $section['name'] }}</h5>
                                    <p class="chiffres">
                                        @if ($section['duree']) {{ $section['duree'] }} min @endif
                                        @if ($section['questions']) · {{ $section['questions'] }} questions @endif
                                        @if ($section['poids']) · {{ $section['poids'] }} % @endif
                                        @if ($section['max']) · noté sur {{ $section['max'] }} @endif
                                        @if ($section['competence']) · {{ $section['competence'] }} @endif
                                    </p>

                                    @if ($section['description'])
                                        <p class="desc">{{ $section['description'] }}</p>
                                    @endif

                                    @if ($section['parties'])
                                        <table class="tableau">
                                            <thead>
                                            <tr>
                                                <th>N°</th>
                                                <th>Partie</th>
                                                <th>Tâche</th>
                                                <th>Items</th>
                                                <th>Source</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @foreach ($section['parties'] as $partie)
                                                <tr>
                                                    <td class="num">{{ $partie['numero'] }}</td>
                                                    <td>
                                                        <strong>{{ $partie['intitule'] ?? $partie['name'] }}</strong>
                                                        @if ($partie['intitule'] && $partie['name'])
                                                            <span class="choix">{{ $partie['name'] }}</span>
                                                        @endif
                                                        @foreach ($partie['formats'] as $format)
                                                            <code>{{ $format }}</code>@if (! $loop->last) @endif
                                                        @endforeach
                                                        @if (count($partie['formats']) > 1)
                                                            <span class="choix">format au choix du candidat</span>
                                                        @endif
                                                    </td>
                                                    <td>{{ $partie['tache'] }}</td>
                                                    <td class="n">
                                                        @if ($partie['items']) {{ $partie['items'] }} @endif
                                                        @if ($partie['points']) <span class="choix">{{ $partie['points'] }} pts</span> @endif
                                                    </td>
                                                    <td>
                                                        @if ($partie['source'] === 'officiel')
                                                            <span class="pastille officiel">officiel</span>
                                                        @elseif ($partie['source'])
                                                            <span class="pastille reconstitue">reconstituée</span>
                                                        @endif
                                                    </td>
                                                </tr>

                                                @if (! empty($partie['exemple']))
                                                    @include('partials.structure-exemple', ['exemple' => $partie['exemple'], 'partie' => $partie])
                                                @endif
                                            @endforeach
                                            </tbody>
                                        </table>
                                    @endif
                                @endforeach
                            </div>
                        </details>
                    @empty
                        <p class="sources">Structure pas encore décrite partie par partie.</p>
                    @endforelse
                </article>
            @endforeach
        </section>
    @endforeach

    <p class="pied">
        Un écart, une partie qui manque, un chiffre qui ne correspond pas à votre document ?
        Dites-le nous :
        <a href="mailto:prepla.mirlab@gmail.com">prepla.mirlab@gmail.com</a> ·
        <a href="https://wa.me/237693427913" target="_blank" rel="noopener">WhatsApp</a>.
        <br>
        Les structures sont relevées sur les sites des organismes. Les versions « junior »,
        « for Schools » et « Jugendliche » ne sont pas décrites ici : ce sont d'autres examens.
        <br>
        Les exercices écrits à la main existent pour l'instant en <strong>allemand</strong>
        (Goethe A1, A2, B1 et telc B1). Une partie sans exercice n'a pas de lien à déplier :
        sa structure est décrite, son contenu reste à écrire.
    </p>
</div>
</body>
</html>
