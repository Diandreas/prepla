<?php

namespace App\Services\Content;

use App\Models\User;
use App\Models\UserError;
use App\Services\ErrorSpacedRepetitionService;

/**
 * Retient ce qu'une rédaction corrigée vient d'apprendre sur l'apprenant.
 *
 * Un enseignant qui lit une copie et y voit un vocabulaire pauvre met du
 * vocabulaire au programme de la semaine suivante. L'application, elle,
 * affichait la correction puis l'oubliait : rien n'était enregistré, donc ni
 * le centre de révision ni le parcours n'en savaient quoi que ce soit. On
 * pouvait faire la même faute d'accord dans dix rédactions de suite sans que
 * rien ne change.
 *
 * Chaque correction devient donc une erreur mémorisée, avec sa catégorie — et
 * elle rejoint la machinerie qui existe déjà : la révision espacée, et les
 * reprises que le parcours insère à partir des catégories ratées.
 */
class ErreursDeRedaction
{
    /**
     * Les catégories que le reste de l'application sait traiter. Une correction
     * étiquetée autrement est ramenée à « writing » plutôt que d'introduire un
     * concept que le parcours ne saurait pas reprendre.
     */
    private const CATEGORIES = ['grammar', 'vocabulary', 'spelling', 'punctuation', 'coherence', 'writing'];

    /** Au-delà, ce n'est plus une correction mais une réécriture : on ne noie pas le centre de révision. */
    private const MAXIMUM = 12;

    public function __construct(private ErrorSpacedRepetitionService $sm2) {}

    /**
     * Mémorise les corrections d'une rédaction.
     *
     * @param  array<int, array<string, mixed>>  $corrections
     * @return list<string>  les catégories retenues, pour la remédiation
     */
    public function memoriser(User $user, array $corrections, ?string $texteSoumis = null): array
    {
        $categories = [];
        $empreinteDuTexte = substr(md5((string) $texteSoumis.now()->toDateString()), 0, 8);

        foreach (array_slice($corrections, 0, self::MAXIMUM) as $rang => $correction) {
            if (! is_array($correction)) {
                continue;
            }

            $faute = trim((string) ($correction['original'] ?? ''));
            $juste = trim((string) ($correction['corrected'] ?? ''));

            // Une correction sans avant ni après n'apprend rien à personne.
            if ($faute === '' || $juste === '' || $faute === $juste) {
                continue;
            }

            $categorie = $this->categorie($correction['category'] ?? null);
            $categories[] = $categorie;

            // La clé identifie la faute, pas la rédaction : refaire la même
            // faute demain réveille l'erreur existante au lieu d'en créer une
            // seconde, et la révision espacée repart de son intervalle.
            $cle = 'redaction-'.$categorie.'-'.substr(md5(mb_strtolower($faute)), 0, 10);

            $erreur = UserError::updateOrCreate(
                ['user_id' => $user->id, 'exercise_id' => null, 'question_id' => $cle],
                [
                    'question_text' => $this->enonce($faute, $texteSoumis),
                    'user_answer' => $faute,
                    'correct_answer' => $juste,
                    'explanation' => trim((string) ($correction['explanation'] ?? '')) ?: null,
                    'skill_type' => 'writing',
                    'exercise_type_slug' => 'writing-corrector',
                    'error_category' => $categorie,
                    'mastered' => false,
                ]
            );

            if ($erreur->wasRecentlyCreated) {
                $this->sm2->initializeForNewError($erreur);
            }

            unset($rang, $empreinteDuTexte);
        }

        return array_values(array_unique($categories));
    }

    /** La catégorie annoncée par l'IA, ramenée à celles que l'application sait reprendre. */
    private function categorie(?string $annoncee): string
    {
        $propre = mb_strtolower(trim((string) $annoncee));

        return in_array($propre, self::CATEGORIES, true) ? $propre : 'writing';
    }

    /**
     * De quoi reconnaître la faute au moment de la revoir.
     *
     * Sans un bout de phrase autour, le centre de révision afficherait « leur »
     * tout seul, et l'apprenant ne saurait pas de quoi on parle.
     */
    private function enonce(string $faute, ?string $texte): string
    {
        if (! $texte) {
            return 'Dans ta rédaction : « '.$faute.' »';
        }

        $position = mb_stripos($texte, $faute);
        if ($position === false) {
            return 'Dans ta rédaction : « '.$faute.' »';
        }

        $debut = max(0, $position - 40);
        $extrait = trim(mb_substr($texte, $debut, mb_strlen($faute) + 80));

        return 'Dans ta rédaction : « …'.$extrait.'… »';
    }
}
