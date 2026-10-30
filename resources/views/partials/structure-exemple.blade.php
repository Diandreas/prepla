{{--
    L'exercice reel d'une partie, tel que l'apprenant le recoit.

    Une structure se verifie sur le papier ; un exercice, non. On montre donc le
    texte entier, chaque question, la bonne reponse et son explication — c'est le
    seul moyen pour un enseignant de dire si ce que nous servons ressemble
    vraiment a l'epreuve, et non seulement si le tableau est juste.
--}}
<tr class="ligne-exemple">
    <td colspan="5">
        <details class="exemple">
            <summary>
                Voir l'exercice tel qu'il est servi
                @if ($exemple['questions'])
                    <span class="meta">{{ count($exemple['questions']) }} question{{ count($exemple['questions']) > 1 ? 's' : '' }}, avec les réponses</span>
                @endif
            </summary>

            <div class="corps-exemple">
                @if ($exemple['titre'])
                    <p class="titre-exemple">{{ $exemple['titre'] }}</p>
                @endif

                @if ($exemple['consigne'])
                    <p class="consigne">{{ $exemple['consigne'] }}</p>
                @endif

                @if ($exemple['texte'])
                    <div class="passage">{{ $exemple['texte'] }}</div>
                @endif

                @if ($exemple['audio'])
                    <div class="passage audio">
                        <span class="etiquette">Enregistrement — texte lu</span>
                        {{ $exemple['audio'] }}
                    </div>
                @endif

                <ol class="questions">
                    @foreach ($exemple['questions'] as $question)
                        <li>
                            @if ($question['texte'])
                                <p class="enonce">{{ $question['texte'] }}</p>
                            @endif

                            @if ($question['audio'])
                                <div class="passage audio">
                                    <span class="etiquette">Enregistrement — texte lu</span>
                                    {{ $question['audio'] }}
                                </div>
                            @endif

                            @if ($question['champs'])
                                <p class="champs">Champs à remplir :
                                    @foreach ($question['champs'] as $champ)
                                        <code>{{ is_array($champ) ? ($champ['label'] ?? json_encode($champ)) : $champ }}</code>@if (! $loop->last) @endif
                                    @endforeach
                                </p>
                            @endif

                            @if ($question['options'])
                                <ul class="options">
                                    @foreach ($question['options'] as $option)
                                        @php $texte = is_array($option) ? ($option['text'] ?? reset($option)) : $option; @endphp
                                        <li @class(['juste' => $question['reponse'] !== null && (string) $texte === (string) $question['reponse']])>
                                            {{ $texte }}
                                        </li>
                                    @endforeach
                                </ul>
                            @endif

                            @if ($question['reponse'] !== null && $question['reponse'] !== '')
                                <p class="reponse"><strong>Réponse :</strong> {{ $question['reponse'] }}</p>
                            @endif

                            @if ($question['explication'])
                                <p class="explication">{{ $question['explication'] }}</p>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </div>
        </details>
    </td>
</tr>
