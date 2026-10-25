<?php

namespace App\Console\Commands;

use App\Models\Exercise;
use App\Services\AI\MistralService;
use Illuminate\Console\Command;

/**
 * Ajoute leurs POINTS À COCHER aux exercices oraux déjà en base.
 *
 * Un examinateur telc ne juge pas une Vorstellung à l'impression : il vérifie
 * que le candidat a dit son nom, d'où il vient, ce qu'il fait. L'évaluation
 * sait faire exactement cela, et le générateur les produit désormais — mais les
 * 538 exercices oraux déjà écrits n'en portaient aucun.
 *
 * On ne les régénère pas : certains portent les tentatives d'apprenants, et
 * les refaire jetterait ce travail. On ajoute les points à la consigne qui
 * existe déjà, sans toucher au reste.
 */
class PointsOraux extends Command
{
    protected $signature = 'prepla:points-oraux
        {--limite=0 : s arreter apres ce nombre d exercices}
        {--refaire : reecrire aussi les points deja poses}
        {--tierce : ne reprendre que les listes ecrites pour l examinateur}
        {--sec : annoncer ce qui serait fait, sans rien ecrire}';

    protected $description = 'Ajoute leurs points a cocher aux exercices oraux qui n en ont pas';

    /**
     * Ce qui trahit un point ecrit pour l'examinateur plutot que pour
     * l'apprenant : « dit son prenom » au lieu de « Dis ton prenom ».
     */
    private const MARQUEURS_TIERS = ['il/elle', "l'apprenant", 'le candidat', "qu'il ", "qu'elle ", ' son ', ' sa ', ' ses '];

    /** Ce qui prouve au contraire qu'on s'adresse bien a l'apprenant. */
    private const MARQUEURS_TOI = [' ton ', ' ta ', ' tes ', ' tu ', '-toi', ' toi '];

    public function handle(MistralService $mistral): int
    {
        $sec = (bool) $this->option('sec');
        $limite = (int) $this->option('limite');

        $faits = 0;
        $refuses = 0;

        Exercise::with('exerciseType:id,slug,component_key', 'exam:id,language_id', 'exam.language:id,name')
            ->whereHas('exerciseType', fn ($q) => $q->whereIn('component_key', ['speaking-recorder', 'role-play']))
            ->chunkById(100, function ($lot) use ($mistral, $sec, $limite, &$faits, &$refuses) {
                foreach ($lot as $exercice) {
                    if ($limite > 0 && $faits >= $limite) {
                        return false;
                    }

                    $questions = is_array($exercice->questions) ? $exercice->questions : [];
                    $modifie = false;

                    foreach ($questions as $i => $question) {
                        if (! is_array($question)) {
                            continue;
                        }

                        $poses = $question['expected_points'] ?? null;

                        if ($this->option('tierce')) {
                            // On ne touche qu'aux listes qui parlent de l'apprenant
                            // a la troisieme personne : le reste est deja bon, et
                            // le refaire gaspillerait des appels au modele.
                            if (! is_array($poses) || ! $this->parleDeLApprenant($poses)) {
                                continue;
                            }
                        } elseif (! $this->option('refaire') && ! empty($poses)) {
                            continue;
                        }

                        $consigne = trim((string) ($question['text'] ?? $question['scenario'] ?? ''));
                        if ($consigne === '') {
                            continue;
                        }

                        if ($sec) {
                            $this->line('  <fg=cyan>?</> #'.$exercice->id.' '.mb_substr($consigne, 0, 60).'…');
                            $modifie = true;

                            continue;
                        }

                        $points = $this->points($mistral, $consigne, $exercice);
                        if ($points === null) {
                            $refuses++;
                            $this->line('  <fg=red>✗</> #'.$exercice->id.' points refuses');

                            continue;
                        }

                        $questions[$i]['expected_points'] = $points;
                        $modifie = true;
                        $this->line('  <fg=green>✓</> #'.$exercice->id.' '.count($points).' point(s) : '.implode(' · ', $points));
                    }

                    if ($modifie) {
                        $faits++;
                        if (! $sec) {
                            $exercice->questions = $questions;
                            $exercice->saveQuietly();
                        }
                    }
                }

                return true;
            });

        $this->newLine();
        $this->info(($sec ? 'A faire : ' : '').$faits.' exercice(s) oraux'
            .($refuses ? ', '.$refuses.' refus' : ''));

        return self::SUCCESS;
    }

    /**
     * Trois à cinq points vérifiables à la seule écoute.
     *
     * Vérifiés avant d'être écrits : une liste vide, trop courte, ou faite de
     * phrases entières ne sert à rien — l'examinateur doit pouvoir cocher.
     *
     * @return list<string>|null
     */
    private function points(MistralService $mistral, string $consigne, Exercise $exercice): ?array
    {
        $langue = $exercice->exam?->language?->name ?? 'English';
        $niveau = $exercice->difficulty ?: 'B1';

        $reponse = $mistral->chat([
            ['role' => 'system', 'content' =>
                "You prepare the examiner's checklist for a spoken exam task in {$langue}, CEFR {$niveau}.\n"
                ."Return 3 to 5 SHORT items, in French, ADDRESSED TO THE CANDIDATE as things to say. "
                ."The learner reads them on a card before speaking: write \"Dis ton prénom\", \"Donne une raison\", \"Propose une heure\" — never the third person, never vague (\"parle bien\"). Each must stay verifiable from the recording alone.
"
                .'Reply with JSON only: {"points": ["...", "..."]}'],
            ['role' => 'user', 'content' => $consigne],
        ]);

        $decode = is_string($reponse) ? json_decode($reponse, true) : null;
        $points = is_array($decode) ? ($decode['points'] ?? null) : null;

        if (! is_array($points) || count($points) < 3 || count($points) > 5) {
            return null;
        }

        $propres = [];
        foreach ($points as $point) {
            $texte = is_string($point) ? trim($point) : '';
            // Une phrase entiere n'est pas un point a cocher.
            if ($texte === '' || mb_strlen($texte) > 70) {
                return null;
            }
            // Le modele retombe regulierement dans la voix de l'examinateur
            // malgre la consigne. On le verifie plutot que de l'esperer : mieux
            // vaut un refus qu'une carte qui ne parle pas a l'apprenant.
            if ($this->estALExaminateur($texte)) {
                return null;
            }
            $propres[] = $texte;
        }

        return $propres;
    }

    /**
     * Un point qui decrit l'apprenant a la troisieme personne.
     *
     * Les possessifs seuls ne suffisent pas a juger : « Dis ce que tu penses de
     * sa proposition » s'adresse bien a l'apprenant. On ne retient donc un
     * marqueur de troisieme personne que si rien, dans le meme point, ne
     * s'adresse a l'apprenant.
     */
    private function estALExaminateur(string $point): bool
    {
        $texte = ' '.mb_strtolower($point).' ';

        foreach (self::MARQUEURS_TOI as $marqueur) {
            if (mb_strpos($texte, $marqueur) !== false) {
                return false;
            }
        }

        foreach (self::MARQUEURS_TIERS as $marqueur) {
            if (mb_strpos($texte, $marqueur) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, mixed>  $points
     */
    private function parleDeLApprenant(array $points): bool
    {
        foreach ($points as $point) {
            if (is_string($point) && $this->estALExaminateur($point)) {
                return true;
            }
        }

        return false;
    }
}
