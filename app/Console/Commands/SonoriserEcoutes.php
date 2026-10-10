<?php

namespace App\Console\Commands;

use App\Models\Exercise;
use App\Services\AI\MistralService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Ecrit l'enregistrement manquant des epreuves d'ecoute muettes.
 *
 * Certaines epreuves d'ecoute n'ont jamais eu de texte parle : ni dans la
 * question, ni dans le contenu. Leurs questions existent pourtant, avec leurs
 * reponses attendues. On demande donc a l'IA le passage qui rend ces reponses
 * trouvables A L'OREILLE, puis on VERIFIE que chaque reponse attendue y figure
 * reellement avant d'enregistrer quoi que ce soit : un passage qui ne contient
 * pas la reponse transforme l'exercice en devinette.
 */
class SonoriserEcoutes extends Command
{
    protected $signature = 'prepla:sonoriser-ecoutes
        {--exercice= : ne traiter que cet identifiant}
        {--limite=0 : s arreter apres ce nombre d exercices}
        {--sec : montrer ce qui serait ecrit, sans rien enregistrer}';

    protected $description = "Ecrit le passage parle des epreuves d'ecoute qui n'en ont aucun";

    /** Les familles dont chaque reponse attendue doit se retrouver mot pour mot dans le passage. */
    private const FAMILLES_VERIFIABLES = [
        'note-completion', 'form-completion', 'flow-chart-completion',
        'sentence-completion', 'table-completion', 'summary-completion',
        'short-answer', 'dictation',
    ];

    public function handle(MistralService $mistral): int
    {
        $muettes = $this->epreuvesMuettes();

        if ($muettes === []) {
            $this->info('Aucune epreuve d ecoute muette.');

            return self::SUCCESS;
        }

        $limite = (int) $this->option('limite');
        if ($limite > 0) {
            $muettes = array_slice($muettes, 0, $limite);
        }

        $this->info(count($muettes) . ' epreuve(s) a sonoriser.');
        $sec = (bool) $this->option('sec');
        $ecrites = 0;
        $refusees = [];

        foreach ($muettes as $exercice) {
            $langue = $exercice->exam?->language?->name ?? 'English';
            $format = $exercice->exerciseType?->component_key ?? 'mcq';
            $etiquette = "#{$exercice->id} {$format} {$exercice->difficulty} ({$langue})";

            $passage = $this->ecrireLePassage($mistral, $exercice, $langue);

            if ($passage === null) {
                $refusees[] = "$etiquette : l IA n a rien renvoye";
                $this->line("  <fg=red>✗</> $etiquette — pas de reponse");

                continue;
            }

            $manquantes = $this->reponsesAbsentes($exercice, $passage);
            if ($manquantes !== []) {
                $refusees[] = "$etiquette : " . count($manquantes) . ' reponse(s) absente(s) du passage';
                $this->line("  <fg=red>✗</> $etiquette — manque : " . implode(', ', array_slice($manquantes, 0, 4)));

                continue;
            }

            $this->line("  <fg=green>✓</> $etiquette — " . str_word_count($passage) . ' mots');
            if ($this->output->isVerbose()) {
                $this->line('      ' . mb_substr($passage, 0, 160) . '…');
            }

            if (! $sec) {
                $contenu = is_array($exercice->content) ? $exercice->content : [];
                $contenu['audio_text'] = $passage;
                $exercice->content = $contenu;
                $exercice->save();
            }

            $ecrites++;
        }

        $this->newLine();
        $this->info(($sec ? 'A ecrire : ' : 'Ecrites : ') . $ecrites . ' / ' . count($muettes));

        if ($refusees !== []) {
            $this->warn('Laissees de cote (' . count($refusees) . ') :');
            foreach ($refusees as $r) {
                $this->line('  ' . $r);
            }
        }

        return self::SUCCESS;
    }

    /** @return Exercise[] */
    private function epreuvesMuettes(): array
    {
        $cible = $this->option('exercice');
        $muettes = [];

        Exercise::with(['exerciseType:id,skill_type,component_key', 'exam:id,language_id', 'exam.language:id,name'])
            ->when($cible, fn ($q) => $q->whereKey($cible))
            ->chunk(300, function ($lot) use (&$muettes) {
                foreach ($lot as $exercice) {
                    if (! $exercice->estUneEcoute()) {
                        continue;
                    }

                    $aDuSon = false;
                    foreach ((is_array($exercice->questions) ? $exercice->questions : []) as $question) {
                        if (! empty($question['audio_url']) || $exercice->texteParle(is_array($question) ? $question : []) !== '') {
                            $aDuSon = true;
                            break;
                        }
                    }

                    if (! $aDuSon && is_array($exercice->questions) && $exercice->questions !== []) {
                        $muettes[] = $exercice;
                    }
                }
            });

        return $muettes;
    }

    private function ecrireLePassage(MistralService $mistral, Exercise $exercice, string $langue): ?string
    {
        $attendu = $this->resumeDesQuestions($exercice);
        $niveau = $exercice->difficulty ?: 'B1';

        $consigne = <<<TXT
            You write the RECORDING for a listening comprehension task of a language exam.

            Target language: {$langue}. CEFR level: {$niveau}.

            Below are the questions the candidate must answer by ear, with the answers
            expected from them. Write the spoken passage they listen to.

            {$attendu}

            Rules, all mandatory:
            - Write ONLY in {$langue}, in natural spoken style, 70 to 130 words.
            - A short monologue, or a dialogue between two speakers marked "A:" and "B:".
            - EVERY expected answer listed above must appear in your passage, spelled
              EXACTLY as written there, inside a natural sentence.
            - Never read the questions aloud and never announce the answers as a list.
            - Keep the vocabulary and grammar within {$niveau}.

            Answer with JSON only: {"audio_text": "..."}
            TXT;

        $brut = $mistral->chat([['role' => 'user', 'content' => $consigne]]);
        if (! is_string($brut) || trim($brut) === '') {
            return null;
        }

        $decode = json_decode($brut, true);
        $passage = is_array($decode) ? ($decode['audio_text'] ?? null) : null;

        if (! is_string($passage) || mb_strlen(trim($passage)) < 60) {
            Log::warning('Passage d ecoute inexploitable', ['exercice' => $exercice->id]);

            return null;
        }

        return trim($passage);
    }

    /** Le resume des questions et des reponses attendues, donne a l'IA. */
    private function resumeDesQuestions(Exercise $exercice): string
    {
        $lignes = [];

        foreach ((is_array($exercice->questions) ? $exercice->questions : []) as $i => $question) {
            if (! is_array($question)) {
                continue;
            }

            $numero = $i + 1;
            $enonce = $question['text'] ?? $question['prompt'] ?? '';
            $lignes[] = "Question {$numero}: {$enonce}";

            foreach ($this->reponsesAttendues($question) as $reponse) {
                $lignes[] = "  expected answer: {$reponse}";
            }

            foreach (($question['notes'] ?? $question['fields'] ?? []) as $champ) {
                if (is_array($champ) && ($champ['label'] ?? null)) {
                    $lignes[] = '  field: ' . $champ['label'] . ' = ' . (($champ['value'] ?? '') ?: '(to be heard)');
                }
            }
        }

        return implode("\n", $lignes);
    }

    /**
     * Les reponses attendues, en clair : une lettre de QCM est traduite en son
     * libelle, sans quoi on demanderait a l'IA de placer « B » dans le passage.
     *
     * @return string[]
     */
    private function reponsesAttendues(array $question): array
    {
        $sorties = [];
        $options = Exercise::optionList($question['options'] ?? []);

        $lire = function ($valeur) use ($options): ?string {
            $texte = Exercise::optionText($valeur);
            if ($texte === '') {
                return null;
            }

            // « A », « B »… renvoient a un choix : on rend le libelle, pas la lettre.
            if ($options !== [] && preg_match('/^[A-H]$/i', $texte)) {
                $rang = ord(strtoupper($texte)) - ord('A');

                return $options[$rang] ?? null;
            }

            return $texte;
        };

        $unique = $lire($question['correct_answer'] ?? null);
        if ($unique !== null) {
            $sorties[] = $unique;
        }

        foreach (($question['correct_answers'] ?? []) as $valeur) {
            $texte = $lire($valeur);
            if ($texte !== null) {
                $sorties[] = $texte;
            }
        }

        return array_values(array_unique(array_filter($sorties, fn ($t) => mb_strlen($t) > 1)));
    }

    /**
     * Les reponses attendues qui ne figurent PAS dans le passage.
     *
     * Verification mot pour mot pour les familles a trous — c'est la que l'oreille
     * doit retrouver un mot precis. Pour un QCM ou une association, le libelle peut
     * etre reformule a l'oral : on ne refuse pas sur ce seul motif.
     *
     * @return string[]
     */
    private function reponsesAbsentes(Exercise $exercice, string $passage): array
    {
        $format = $exercice->exerciseType?->component_key ?? '';
        $aVerifier = in_array($format, self::FAMILLES_VERIFIABLES, true);

        if (! $aVerifier) {
            return [];
        }

        $normaliser = fn (string $t) => preg_replace('/[^\p{L}\p{N}]+/u', ' ', mb_strtolower($t));
        $dit = ' ' . $normaliser($passage) . ' ';

        $manquantes = [];
        foreach ((is_array($exercice->questions) ? $exercice->questions : []) as $question) {
            if (! is_array($question)) {
                continue;
            }

            foreach ($this->reponsesAttendues($question) as $reponse) {
                if (! str_contains($dit, ' ' . trim($normaliser($reponse)) . ' ')) {
                    $manquantes[] = $reponse;
                }
            }
        }

        return array_values(array_unique($manquantes));
    }
}
