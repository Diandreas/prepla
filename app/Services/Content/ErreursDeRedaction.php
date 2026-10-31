<?php

namespace App\Services\Content;

use App\Models\User;
use App\Models\UserError;
use App\Services\ErrorSpacedRepetitionService;

/**
 * Retient ce qu'une production corrigée — rédaction ou prise de parole — vient
 * d'apprendre sur l'apprenant.
 *
 * Un enseignant qui lit une copie et y voit un vocabulaire pauvre met du
 * vocabulaire au programme de la semaine suivante. L'application, elle,
 * affichait la correction puis l'oubliait : rien n'était enregistré, donc ni
 * le centre de révision ni le parcours n'en savaient quoi que ce soit. On
 * pouvait faire la même faute d'accord dans dix rédactions de suite sans que
 * rien ne change.
 *
 * Chaque correction devient donc une erreur mémorisée, avec sa catégorie ET sa
 * sous-catégorie — « grammaire » ne dit rien à un centre, « verbe en deuxième
 * position » lui dit quoi travailler. Elle rejoint la machinerie qui existe
 * déjà : la révision espacée, les reprises du parcours, et le suivi du centre.
 *
 * C'est à l'écrit et à l'oral longs que les faiblesses profondes se révèlent :
 * un QCM dit qu'une réponse est fausse, une rédaction dit pourquoi.
 */
class ErreursDeRedaction
{
    /**
     * Les catégories que le reste de l'application sait traiter, et pour chacune
     * les sous-catégories admises. Une étiquette inconnue est ramenée à sa
     * famille plutôt que d'introduire un concept que le parcours ne saurait pas
     * reprendre.
     */
    public const TAXONOMIE = [
        'grammar' => ['tense', 'agreement', 'word-order', 'article', 'preposition', 'modal', 'pronoun', 'case', 'verb-form', 'conjugation', 'negation', 'subordination'],
        'vocabulary' => ['lexical', 'register', 'collocation', 'range', 'word-formation', 'false-friend'],
        'spelling' => ['accent', 'capitalisation', 'typo'],
        'punctuation' => [],
        'coherence' => ['connectors', 'structure', 'reference', 'relevance'],
        'writing' => [],
        // Seulement pour l'oral : ce qui se lit dans une transcription.
        'speaking' => ['fluency', 'completeness', 'interaction'],
    ];

    /** Au-delà, ce n'est plus une correction mais une réécriture : on ne noie pas le centre de révision. */
    private const MAXIMUM = 12;

    public function __construct(private ErrorSpacedRepetitionService $sm2) {}

    /**
     * Mémorise les corrections d'une production.
     *
     * @param  array<int, array<string, mixed>>  $corrections  {original, corrected, category[, subcategory], explanation}
     * @param  string  $competence  « writing » ou « speaking »
     * @return list<string>  les catégories retenues, pour la remédiation
     */
    public function memoriser(User $user, array $corrections, ?string $texteSoumis = null, string $competence = 'writing'): array
    {
        $oral = $competence === 'speaking';
        $categories = [];

        foreach (array_slice($corrections, 0, self::MAXIMUM) as $correction) {
            if (! is_array($correction)) {
                continue;
            }

            $faute = trim((string) ($correction['original'] ?? ''));
            $juste = trim((string) ($correction['corrected'] ?? ''));

            // Une correction sans avant ni après n'apprend rien à personne.
            if ($faute === '' || $juste === '' || $faute === $juste) {
                continue;
            }

            [$categorie, $sousCategorie] = $this->classer(
                $correction['category'] ?? null,
                $correction['subcategory'] ?? null,
                $oral,
            );
            $categories[] = $categorie;

            // La clé identifie la faute, pas la production : refaire la même
            // faute demain réveille l'erreur existante au lieu d'en créer une
            // seconde, et la révision espacée repart de son intervalle.
            $cle = ($oral ? 'oral-' : 'redaction-').$categorie.'-'.substr(md5(mb_strtolower($faute)), 0, 10);

            $erreur = UserError::updateOrCreate(
                ['user_id' => $user->id, 'exercise_id' => null, 'question_id' => $cle],
                [
                    'question_text' => $this->enonce($faute, $texteSoumis, $oral),
                    'user_answer' => $faute,
                    'correct_answer' => $juste,
                    'explanation' => trim((string) ($correction['explanation'] ?? '')) ?: null,
                    'skill_type' => $oral ? 'speaking' : 'writing',
                    'exercise_type_slug' => $oral ? 'speaking-evaluation' : 'writing-corrector',
                    'error_category' => $categorie,
                    'subcategory' => $sousCategorie,
                    'mastered' => false,
                ]
            );

            if ($erreur->wasRecentlyCreated) {
                $this->sm2->initializeForNewError($erreur);
            }
        }

        return array_values(array_unique($categories));
    }

    /**
     * La catégorie annoncée par l'IA, ramenée à celles que l'application sait
     * reprendre. L'IA écrit souvent « grammar.word-order » d'un seul tenant :
     * on sépare la famille de la sous-catégorie.
     *
     * @return array{0: string, 1: string|null}
     */
    private function classer(?string $annoncee, ?string $sousAnnoncee, bool $oral): array
    {
        $propre = mb_strtolower(trim((string) $annoncee));
        [$famille, $sous] = array_pad(explode('.', $propre, 2), 2, null);
        $sous = $sous ?? (mb_strtolower(trim((string) $sousAnnoncee)) ?: null);

        $admise = array_key_exists((string) $famille, self::TAXONOMIE) && ($famille !== 'speaking' || $oral);
        if (! $admise) {
            return [$oral ? 'speaking' : 'writing', null];
        }

        $sous = $sous !== null && in_array($sous, self::TAXONOMIE[$famille], true) ? $sous : null;

        return [$famille, $sous];
    }

    /**
     * De quoi reconnaître la faute au moment de la revoir.
     *
     * Sans un bout de phrase autour, le centre de révision afficherait « leur »
     * tout seul, et l'apprenant ne saurait pas de quoi on parle.
     */
    private function enonce(string $faute, ?string $texte, bool $oral = false): string
    {
        $ou = $oral ? 'Dans ta réponse orale' : 'Dans ta rédaction';

        if (! $texte) {
            return $ou.' : « '.$faute.' »';
        }

        $position = mb_stripos($texte, $faute);
        if ($position === false) {
            return $ou.' : « '.$faute.' »';
        }

        $debut = max(0, $position - 40);
        $extrait = trim(mb_substr($texte, $debut, mb_strlen($faute) + 80));

        return $ou.' : « …'.$extrait.'… »';
    }
}
