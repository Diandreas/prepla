<?php

namespace App\Services\AI;

use App\Models\Exam;
use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Services\Content\ExerciseSchemaRegistry;
use App\Services\Content\StructureOfficielle;
use App\Services\ImageLibraryService;
use Illuminate\Support\Facades\Log;

class ExerciseGeneratorService
{
    public function __construct(
        protected MistralService $mistral,
        protected ImageLibraryService $imageLibrary
    ) {}

    public function generateBatch(array $typeIds, Exam $exam, string $difficulty = 'B1'): array
    {
        $exercises = [];
        foreach ($typeIds as $typeId) {
            $exerciseType = ExerciseType::find($typeId);
            if ($exerciseType) {
                $exercises[] = $this->generate($exerciseType, $exam, $difficulty);
            }
        }

        return $exercises;
    }

    /**
     * @param  array{theme?: ?string, consignes?: ?string, nombre_questions?: ?int}|null  $demandeDuCentre
     *         ce qu'un enseignant de centre precise dans le createur d'exercices :
     *         un theme, des consignes libres, un nombre de questions. Tout est
     *         facultatif ; sans demande, rien ne change pour les autres appels.
     */
    public function generate(ExerciseType $exerciseType, Exam $exam, string $difficulty = 'B1', ?array $lessonContext = null, ?array $partieExamen = null, ?string $faiblesse = null, ?array $demandeDuCentre = null, ?int $palier = null): Exercise
    {
        $exam->loadMissing('language');
        $language = $exam->language?->name ?? 'English';
        $languageNative = $exam->language?->native_name ?? $language;

        // LA PARTIE D'EPREUVE, TOUJOURS — et plus seulement quand l'apprenant
        // l'a nommee. Sans elle, le modele suivait un gabarit interne : trois
        // questions pour un QCM, quatre pour une association, quel que soit
        // l'examen. Releve en production avant ce changement : 33 exercices
        // d'entrainement a l'examen sur 121 portaient le nombre d'items reel.
        // Un QCM de trois questions n'est pas un Lesen Teil 2 du Goethe B1, qui
        // en compte six : l'apprenant travaille, mais pas son epreuve.
        $partieExamen ??= app(StructureOfficielle::class)
            ->partiePour($exam, $difficulty, $exerciseType);

        // LE NOMBRE DEMANDE PAR LE CENTRE passe devant celui de la partie : c'est
        // l'enseignant qui sait combien de questions tiennent dans sa seance.
        $demandeDuCentre = self::demandeDuCentre($demandeDuCentre);
        if ($demandeDuCentre['nombre_questions'] !== null) {
            $partieExamen = array_merge($partieExamen ?? [], ['items' => $demandeDuCentre['nombre_questions']]);
        }

        $itemsRequis = (int) ($partieExamen['items'] ?? 0) ?: null;

        $prompt = $this->buildPrompt($exerciseType, $exam, $difficulty, $language, $languageNative, $lessonContext, $partieExamen, $faiblesse, $demandeDuCentre, $palier);

        // Ce que l'exercice doit respecter une fois ecrit, et pas seulement ce
        // qu'on demande : les libelles du vrai/faux de CETTE epreuve, la langue
        // des explications pour CE niveau.
        $libellesVraiFaux = $this->libellesVraiFaux($exerciseType, $exam, $partieExamen);
        $langueDesExplications = $this->langueDesExplications($difficulty, $language, $lessonContext);
        $skillType = $exerciseType->section?->skill_type ?? 'reading';

        // Up to 2 attempts: if the first generation contains structurally broken
        // MCQ-style questions (duplicate options, correct_answer letter out of
        // range, etc.), retry the WHOLE batch once before falling back to
        // dropping only the broken questions. This is what was producing exercises
        // where the marked-correct option didn't match the explanation at all.
        $data = null;
        $raisons = [];
        for ($attempt = 0; $attempt < 2; $attempt++) {
            $response = $this->mistral->chat([
                ['role' => 'system', 'content' => 'You are an expert language exam content creator. Always respond with valid JSON only.'],
                ['role' => 'user', 'content' => $prompt],
            ]);

            if (! $response) {
                $raisons[] = 'aucune reponse du fournisseur';

                continue;
            }
            $candidate = json_decode($response, true);
            if (! isset($candidate['content'], $candidate['questions']) || count($candidate['questions']) === 0) {
                // « AI failed » sans la raison a coute une enquete entiere : on
                // garde le debut de la reponse, c'est ce qui permet de trancher
                // entre un quota epuise, un JSON casse et un schema refuse.
                $raisons[] = 'reponse illisible ou sans question : '.mb_substr(trim($response), 0, 180);

                continue;
            }

            $produced = count($candidate['questions']);
            $avantFiltre = $candidate['questions'];
            $candidate['questions'] = $this->rectifierLeType($candidate['questions'], $exerciseType->component_key);
            $candidate['questions'] = $this->normalizeOptionLetters($candidate['questions']);
            $candidate['questions'] = $this->appliquerLibellesVraiFaux($candidate['questions'], $libellesVraiFaux);
            $candidate['questions'] = $this->shuffleOptions($candidate['questions']);
            $candidate['questions'] = $this->dropInvalidChoiceQuestions($candidate['questions'], $exerciseType->component_key);
            $candidate['questions'] = $this->dropInvalidMultiFieldQuestions($candidate['questions'], $exerciseType->component_key);
            $kept = count($candidate['questions']);

            if ($kept === 0) {
                // Le nombre de refus ne suffisait pas a savoir POURQUOI : on garde
                // le type que chaque question se declare et ses champs, c'est ce
                // qui montre si Mistral a repondu a cote ou s'il a juste oublie
                // un champ attendu.
                $vus = array_map(fn ($q) => is_array($q)
                    ? ($q['type'] ?? 'sans type').'{'.implode(',', array_slice(array_keys($q), 0, 8)).'}'
                    : gettype($q), $avantFiltre);
                $raisons[] = 'les '.$produced.' question(s) ecrites ont toutes ete refusees par le schema '
                    .$exerciseType->component_key.' — recu : '.implode(' | ', array_unique($vus));

                continue;
            }

            // LE COMPTE DE L'EPREUVE, verifie et non pas seulement demande.
            //
            // Le modele obeit la plupart du temps, pas toujours. Un exercice qui
            // porte le mauvais nombre d'items n'est pas la partie qu'il pretend
            // etre : on le refuse et on recommence, plutot que de l'offrir a un
            // apprenant qui croit s'entrainer au Teil 2.
            $obtenus = $this->compterLesItems($candidate['questions'], $exerciseType->component_key);
            if ($itemsRequis && $obtenus !== $itemsRequis) {
                $raisons[] = "{$obtenus} item(s) au lieu des {$itemsRequis} de la partie officielle";
                Log::info('Exercise generation: item count off, retrying', [
                    'component' => $exerciseType->component_key,
                    'attendu' => $itemsRequis,
                    'obtenu' => $obtenus,
                ]);

                continue;
            }

            // LA QUALITE, verifiee et non pas seulement demandee. L'audit du
            // 2026-11-04 a trouve, dans ce que le prompt interdisait deja, des
            // trous dont la reponse est ecrite dans le texte, des ecoutes dont
            // le son ne dit que la question, des sections de grammaire remplies
            // de comprehension ecrite, des textes de 120 mots au C1. Le modele
            // obeit la plupart du temps : on refuse et on recommence le reste.
            $defauts = $this->defautsDeQualite($candidate, $exerciseType, $difficulty, $skillType, $libellesVraiFaux, $lessonContext, $langueDesExplications);
            if ($defauts !== []) {
                $raisons[] = implode(' ; ', $defauts);
                Log::info('Exercise generation: quality check failed, retrying', [
                    'component' => $exerciseType->component_key,
                    'defauts' => $defauts,
                ]);

                continue;
            }

            // LA CLE, resolue par un second regard qui ne la connait pas.
            if ($desaccord = $this->verifierALAveugle($candidate, $exerciseType->component_key, $libellesVraiFaux)) {
                $raisons[] = $desaccord;
                Log::info('Exercise generation: blind check disagrees, retrying', [
                    'component' => $exerciseType->component_key,
                    'desaccord' => $desaccord,
                ]);

                continue;
            }

            // Une seule question rescapée sur trois passait pour un exercice valide :
            // l'apprenant recevait une série amputée sans rien en savoir, et le second
            // essai — prévu pour ça — n'était jamais tenté. On ne se contente d'un
            // reste minoritaire qu'au dernier essai, faute de mieux.
            $isLastAttempt = $attempt === 1;
            if ($isLastAttempt || $kept >= (int) ceil($produced / 2)) {
                $data = $candidate;
                break;
            }

            $raisons[] = $kept.' question(s) gardees sur '.$produced;
            Log::info('Exercise generation: majority of questions rejected, retrying', [
                'component' => $exerciseType->component_key,
                'produced' => $produced,
                'kept' => $kept,
            ]);
        }

        if ($data) {
            // For visual exercise types (diagram-labeling, graph-description), pick
            // an image from the pre-generated library instead of leaving image_url null.
            $imageCategory = $this->imageLibrary->categoryFor($exerciseType->component_key);
            if ($imageCategory) {
                $exam->loadMissing('language');
                $examSlug = strtolower($exam->slug ?? $exam->name);
                $data['questions'] = array_map(function ($q) use ($examSlug, $imageCategory) {
                    if (empty($q['image_url'])) {
                        $pickedUrl = $this->imageLibrary->pickFor($examSlug, $imageCategory);
                        if ($pickedUrl) {
                            $q['image_url'] = $pickedUrl;
                        }
                    }

                    return $q;
                }, $data['questions']);
            }

            // La langue des explications reste notee : le vivier est partage, et
            // un exercice ecrit pour un debutant hispanophone n'explique pas en
            // francais. C'est ce qui permettra de le reconnaitre.
            $contenu = $data['content'];
            if (is_array($contenu) && $this->exigeUneExplication($exerciseType->component_key)) {
                $contenu['explanation_language'] = $langueDesExplications;
            }
            // Le palier vise reste note : le vivier est partage, et c'est ce qui
            // permet de servir un exercice plus exigeant a qui reussit tout.
            if (is_array($contenu) && $palier !== null) {
                $contenu['palier'] = $palier;
            }

            return Exercise::create([
                'exercise_type_id' => $exerciseType->id,
                'exam_id' => $exam->id,
                'content' => $contenu,
                'questions' => $data['questions'],
                'difficulty' => $difficulty,
                'xp_reward' => $this->calculateXpReward($difficulty),
                'is_ai_generated' => true,
            ]);
        }

        // AI failed: do NOT persist a meaningless placeholder exercise ("What is
        // this exercise about?") — that junk was being shown to learners. Throw so
        // the caller can skip this type / retry / show a clean message instead.
        Log::warning('ExerciseGenerator: AI failed, no exercise generated', [
            'exercise_type' => $exerciseType->component_key,
            'exercise_type_slug' => $exerciseType->slug,
            'exam_id' => $exam->id,
            'niveau' => $difficulty,
            'raisons' => $raisons,
        ]);
        throw new \RuntimeException('AI exercise generation failed for '.$exerciseType->component_key
            .($raisons ? ' — '.implode(' ; ', $raisons) : ''));
    }

    /**
     * Strip letter prefixes like "A) ", "B) " that Mistral sometimes adds to options.
     *
     * Et aussi « A » + espace : « D Dance group », « C Fireworks show »... Ce
     * prefixe-la passait, puis shuffleOptions deplacait les options : a #2287,
     * la bonne reponse etait « C » et l'option affichee en C s'appelait
     * « A Spring Festival ». On ne le retire que quand TOUTES les options
     * d'une question en portent un, avec des lettres distinctes — « A book »
     * seul n'est pas une lettre d'option. Et la reponse attendue designait la
     * LETTRE ecrite, pas la position : on la reporte sur la position de
     * l'option qui portait cette lettre, avant tout melange.
     */
    private function normalizeOptionLetters(array $questions): array
    {
        return array_map(function ($q) {
            if (! is_array($q)) {
                return $q;
            }

            $opts = $q['options'] ?? null;
            if (is_array($opts) && count($opts) >= 2 && array_is_list($opts)) {
                $lettres = [];
                $textes = [];
                foreach ($opts as $opt) {
                    if (! is_string($opt) || ! preg_match('/^\s*([A-H])(?:[).:]\s*|\s+)(\S.*)$/su', $opt, $m)) {
                        $lettres = null;
                        break;
                    }
                    $lettres[] = $m[1];
                    $textes[] = trim($m[2]);
                }

                if ($lettres !== null && count(array_unique($lettres)) === count($lettres)) {
                    $q['options'] = $textes;
                    $ca = $q['correct_answer'] ?? null;
                    if (is_string($ca) && preg_match('/^[A-H]$/', strtoupper(trim($ca)))) {
                        $rang = array_search(strtoupper(trim($ca)), $lettres, true);
                        if ($rang !== false) {
                            $q['correct_answer'] = chr(65 + $rang);
                        }
                    }

                    return $q;
                }
            }

            if (isset($q['options']) && is_array($q['options'])) {
                $q['options'] = array_map(
                    fn ($opt) => is_string($opt) ? preg_replace('/^[A-D]\)\s*/u', '', $opt) : $opt,
                    $q['options']
                );
            }

            return $q;
        }, $questions);
    }

    /**
     * Shuffle options so the correct answer isn't ALWAYS in position A (Mistral
     * systematically writes the right option first). Reorders options AND any
     * parallel arrays (image_prompts/image_options for picture-mcq) in LOCKSTEP,
     * keeping image↔label paired, then re-points the letter answer.
     */
    private function shuffleOptions(array $questions): array
    {
        return array_map(function ($q) {
            $opts = $q['options'] ?? null;
            $ca = $q['correct_answer'] ?? null;
            if (! is_array($opts) || count($opts) < 2 || ! is_string($ca)) {
                return $q;
            }
            $letter = strtoupper(trim($ca));
            if (! preg_match('/^[A-Z]$/', $letter)) {
                return $q; // answer isn't a letter (free text) → leave as is
            }
            $correctIdx = ord($letter) - 65;
            if ($correctIdx < 0 || $correctIdx >= count($opts)) {
                return $q;
            }
            $order = range(0, count($opts) - 1);
            for ($i = count($order) - 1; $i > 0; $i--) {
                $j = random_int(0, $i);
                [$order[$i], $order[$j]] = [$order[$j], $order[$i]];
            }
            $reorder = fn ($arr) => (is_array($arr) && count($arr) === count($order))
                ? array_map(fn ($k) => $arr[$k], $order) : $arr;

            $q['options'] = $reorder($opts);
            if (isset($q['image_prompts'])) {
                $q['image_prompts'] = $reorder($q['image_prompts']);
            }
            if (isset($q['image_options'])) {
                $q['image_options'] = $reorder($q['image_options']);
            }

            $newPos = array_search($correctIdx, $order, true);
            $q['correct_answer'] = chr(65 + ($newPos === false ? 0 : $newPos));

            return $q;
        }, $questions);
    }

    /**
     * Drop questions whose options/correct_answer are structurally broken —
     * this is what caused exercises where the option marked "correct" didn't
     * match the explanation at all (duplicate options, correct_answer letter
     * pointing outside the options array, or an empty/blank option chosen as
     * correct). Non-choice question types (gap-fill, essay, etc.) pass through
     * untouched since they have no `options` array to validate.
     * $componentKey additionally gates a required non-empty `explanation` for
     * every automatically-scored type (see exigeUneExplication) — it was being
     * silently omitted by Mistral without any retry being triggered.
     */
    /**
     * Generic placeholder/sentinel content that has occasionally leaked out of
     * Mistral as a fallback response instead of a real exercise (e.g. a
     * matching exercise whose payload was actually "What is this exercise
     * about?" / "Language practice" in English, regardless of the requested
     * language/component) — confirmed on real generated data (Exercise #354).
     */
    private const SENTINEL_TEXTS = ['what is this exercise about', 'language practice'];

    /**
     * Combien d'items porte reellement cet exercice ?
     *
     * Un item n'est pas toujours une question. Pour les formats a champs
     * multiples — notes, formulaire, tableau, resume a completer — les items
     * sont les TROUS, additionnes sur toutes les questions. Les compter comme
     * des questions donnait « 1 au lieu de 5 » sur des exercices parfaitement
     * conformes, et aurait fait refuser du bon contenu.
     */
    private function compterLesItems(array $questions, ?string $componentKey): int
    {
        if (ExerciseSchemaRegistry::familyFor((string) $componentKey) === ExerciseSchemaRegistry::FAMILY_MULTI) {
            $trous = 0;
            foreach ($questions as $q) {
                if (is_array($q['correct_answers'] ?? null)) {
                    $trous += count($q['correct_answers']);
                }
            }

            return $trous;
        }

        return count($questions);
    }

    /**
     * La longueur du texte a lire, en mots, selon le niveau.
     *
     * Le prompt imposait « 150-200 words » a tous les niveaux : trop pour un
     * A1, beaucoup trop peu pour un C1. Releve : 129 mots en moyenne pour les
     * textes generes au C1, quand une epreuve verifiee B1 en compte environ 200.
     *
     * @return array{0: int, 1: int}
     */
    private function plageDuPassage(string $difficulty): array
    {
        return match ($difficulty) {
            'A0', 'A1' => [80, 120],
            'A2' => [120, 180],
            'B1' => [200, 300],
            'B2' => [300, 400],
            'C1', 'C2' => [400, 600],
            default => [200, 300],
        };
    }

    /**
     * Dans quelle langue expliquer : celle de l'apprenant jusqu'au A2, celle de
     * l'examen a partir du B1.
     *
     * Les explications etaient toujours dans la langue de l'examen : un
     * francophone A1 lisait en allemand pourquoi il s'etait trompe en allemand
     * (#476). La langue de l'apprenant est celle de son profil, a defaut le
     * francais.
     */
    private function langueDesExplications(string $difficulty, string $language, ?array $lessonContext): string
    {
        if (! in_array($difficulty, ['A0', 'A1', 'A2'], true)) {
            return $language;
        }

        return $this->langueMaternelle($lessonContext);
    }

    private function langueMaternelle(?array $lessonContext): string
    {
        $langue = $lessonContext['native_language'] ?? null;
        if (! is_string($langue) || trim($langue) === '') {
            $langue = auth()->user()?->profile?->native_language;
        }

        return is_string($langue) && trim($langue) !== '' ? trim($langue) : 'Français';
    }

    /** Les choix du vrai/faux, dans l'ordre vrai, faux, non mentionne. */
    private const LIBELLES_VRAI_FAUX = [
        'german' => ['Richtig', 'Falsch', 'Nicht im Text'],
        'french' => ['Vrai', 'Faux', 'Non mentionné'],
        'english' => ['True', 'False', 'Not Given'],
    ];

    /**
     * Les libelles du vrai/faux de CETTE epreuve, ou null hors vrai/faux.
     *
     * Le generateur demandait « True/False/Not Given » partout : les examens
     * allemands recevaient des libelles anglais (True 96 et False 133 contre
     * Richtig 24 et Falsch 36), et un « Not Given » que le Goethe ne propose
     * jamais. On suit la configuration de l'examen :
     *  - la partie travaillee dit si elle offre deux ou trois choix (type
     *    `true-false` ou `true-false-not-given`) ;
     *  - un type pedagogique hors partie suit l'examen : s'il declare des
     *    parties en vrai/faux et AUCUNE avec « non mentionne », il n'en aura
     *    pas non plus (Goethe, OSD, DELF) ; sinon le type decide.
     * La langue des libelles est celle de l'examen.
     *
     * @return list<string>|null
     */
    private function libellesVraiFaux(ExerciseType $exerciseType, Exam $exam, ?array $partieExamen): ?array
    {
        if ($exerciseType->component_key !== 'true-false-ng') {
            return null;
        }

        $exam->loadMissing('language');
        $libelles = self::LIBELLES_VRAI_FAUX[$exam->language?->slug] ?? self::LIBELLES_VRAI_FAUX['english'];

        // UNE PARTIE EN OUI / NON. Certaines parties en `true-false` ne se
        // jouent pas en « juste / faux » mais en « Ja / Nein » : le Goethe B1
        // Lesen Teil 4 (pour ou contre ?), les Lesen Aufgabe 2 et 4 de l'OSD,
        // des Horen du Goethe (« repondre par oui ou non »). C'est la partie
        // qui le dit, dans sa propre description : on la lit, partie par
        // partie, sans l'etendre a tout l'examen.
        if ($partieExamen && ($partieExamen['exercise_type_id'] ?? null) === $exerciseType->id
            && $this->partieEnOuiNon($partieExamen)) {
            return self::LIBELLES_OUI_NON[$exam->language?->slug] ?? self::LIBELLES_OUI_NON['english'];
        }

        $troisChoix = $exerciseType->slug !== 'true-false';

        // Une partie de l'epreuve emploie ce type : son slug est le type de la
        // partie, et il fait foi tel quel.
        if (! $partieExamen || ($partieExamen['exercise_type_id'] ?? null) !== $exerciseType->id) {
            $typesDeclares = [];
            foreach (\App\Models\ExamBlueprint::where('exam_id', $exam->id)->get() as $plan) {
                foreach ($plan->sections_config ?? [] as $section) {
                    foreach (($section['parts'] ?? []) as $partie) {
                        if (is_array($partie) && in_array($partie['type'] ?? null, ['true-false', 'true-false-not-given'], true)) {
                            $typesDeclares[] = $partie['type'];
                        }
                    }
                }
            }

            if ($typesDeclares !== [] && ! in_array('true-false-not-given', $typesDeclares, true)) {
                $troisChoix = false;
            }
        }

        return $troisChoix ? $libelles : array_slice($libelles, 0, 2);
    }

    private function libellesOuiNon(array $libelles): bool
    {
        return in_array($libelles, self::LIBELLES_OUI_NON, true);
    }

    /** Les choix d'une partie qui se joue en oui / non, dans l'ordre oui, non. */
    private const LIBELLES_OUI_NON = [
        'german' => ['Ja', 'Nein'],
        'french' => ['Oui', 'Non'],
        'english' => ['Yes', 'No'],
    ];

    /**
     * La partie se joue-t-elle en oui / non, d'apres sa propre description
     * (nom, nom francais, tache) dans config/exams ?
     */
    private function partieEnOuiNon(array $partie): bool
    {
        $description = mb_strtolower(implode(' ', array_filter([
            $partie['name'] ?? null, $partie['name_fr'] ?? null, $partie['task'] ?? null,
        ], 'is_string')));

        return (bool) preg_match('/\bja\s*(?:\/|ou|oder|or)\s*nein\b|\boui\s*(?:\/|ou)\s*non\b|\byes\s*(?:\/|or)\s*no\b|\bpour\s+ou\s+contre\b/u', $description);
    }

    /**
     * Ramene la reponse de chaque vrai/faux sur un libelle de l'epreuve, et
     * declare les choix offerts (`answer_options`, que lit le composant).
     *
     * Le modele ecrit souvent « True » pour un examen allemand : la reponse
     * est juste, le libelle non. On la reporte sur « Richtig ». Une question
     * qui repond « non mentionne » la ou l'epreuve n'offre que deux choix est
     * ecartee : elle n'a pas de bonne reponse a l'ecran.
     */
    private function appliquerLibellesVraiFaux(array $questions, ?array $libelles): array
    {
        if (! $libelles) {
            return $questions;
        }

        $sens = [
            0 => ['true', 'vrai', 'richtig', 'ja', 'yes', 'juste', 'correct', 'oui', 'dafür', 'für'],
            1 => ['false', 'faux', 'falsch', 'nein', 'no', 'incorrect', 'non', 'dagegen', 'gegen'],
            2 => ['not given', 'not mentioned', 'non mentionné', 'non mentionne', 'on ne sait pas',
                'nicht im text', 'steht nicht im text', 'nicht erwähnt', 'nicht gegeben', 'ng'],
        ];

        $gardees = [];
        foreach ($questions as $q) {
            if (! is_array($q) || ! is_scalar($q['correct_answer'] ?? null)) {
                $gardees[] = $q;

                continue;
            }

            $reponse = mb_strtolower(trim((string) $q['correct_answer']));
            $rang = null;
            foreach ($libelles as $i => $libelle) {
                if ($reponse === mb_strtolower($libelle)) {
                    $rang = $i;
                }
            }
            if ($rang === null) {
                foreach ($sens as $i => $mots) {
                    if (in_array($reponse, $mots, true)) {
                        $rang = $i;
                    }
                }
            }

            if ($rang === null || ! isset($libelles[$rang])) {
                continue; // pas de bonne reponse parmi les choix de l'epreuve
            }

            $q['correct_answer'] = $libelles[$rang];
            $q['answer_options'] = $libelles;
            $gardees[] = $q;
        }

        return $gardees;
    }

    /**
     * Les defauts qu'un exercice ecrit ne doit pas avoir, chacun dit en clair.
     *
     * Vide quand l'exercice est bon. Chaque defaut fait refuser l'exercice et
     * recommencer, comme un mauvais nombre d'items.
     *
     * @return list<string>
     */
    private function defautsDeQualite(array $candidate, ExerciseType $exerciseType, string $difficulty, string $skillType, ?array $libellesVraiFaux, ?array $lessonContext, string $langueDesExplications = ''): array
    {
        $componentKey = (string) $exerciseType->component_key;
        $content = is_array($candidate['content'] ?? null) ? $candidate['content'] : [];
        $questions = array_values(array_filter($candidate['questions'] ?? [], 'is_array'));
        $passage = is_string($content['passage'] ?? null) ? trim($content['passage']) : '';
        $defauts = [];

        // f. LA GRAMMAIRE N'EST PAS UNE LECTURE. Au TEF, 12 exercices de
        //    grammaire sur 12 etaient de la comprehension ecrite ; au TCF, 6 sur 6.
        //    Sauf la ou l'examen declare lui-meme un texte a trous en grammaire
        //    (telc, Sprachbausteine : une lettre a trous) : le texte y est
        //    l'epreuve, et le refuser ecrasait la structure de l'examen.
        if ($skillType === 'grammar') {
            if ($passage !== '' && ! $this->texteATrous($exerciseType)) {
                $defauts[] = 'section de grammaire avec un texte a lire';
            }
            $consignes = [is_string($content['instructions'] ?? null) ? $content['instructions'] : ''];
            foreach ($questions as $q) {
                $consignes[] = is_string($q['text'] ?? null) ? $q['text'] : '';
            }
            // La consigne d'un texte a trous parle de son texte (« complete les
            // trous du texte ») : ce n'est pas une question de comprehension.
            if (! $this->texteATrous($exerciseType) && preg_match('/selon le texte|d[\'’]apr[eè]s le texte|dans le texte|according to the (text|passage)|in the (text|passage)|laut (dem )?text|im text|aus dem text/iu', implode("\n", $consignes))) {
                $defauts[] = 'section de grammaire qui renvoie a un texte';
            }
        }

        // d. LA LONGUEUR DU TEXTE A LIRE, selon le niveau. On tolere un ecart
        //    (le modele compte mal les mots) : moins de 80 % du minimum ou plus
        //    de 150 % du maximum, c'est un autre niveau.
        if ($skillType === 'reading' && $passage !== '' && $this->passageAttendu($componentKey, $skillType, $lessonContext)) {
            [$min, $max] = $this->plageDuPassage($difficulty);
            $mots = Exercise::nombreDeMots($passage);
            if ($mots < (int) floor($min * 0.8) || $mots > (int) ceil($max * 1.5)) {
                $defauts[] = "texte de {$mots} mots, attendu {$min}-{$max} au {$difficulty}";
            }
        }

        // e. TEXTES A TROUS : rien a recopier. 79 % des trous du vivier libre
        //    avaient leur reponse ecrite dans le passage, et 37 reprenaient
        //    tel quel l'indice entre parentheses (#476 : « eine ___ (Schwester) »
        //    → « Schwester »).
        if ($componentKey === 'gap-fill') {
            foreach ($questions as $n => $q) {
                $reponse = $q['correct_answer'] ?? null;
                if (! is_string($reponse) || trim($reponse) === '' || is_array($q['options'] ?? null)) {
                    continue;
                }
                $reponse = trim($reponse);
                if ($passage !== '' && $this->contientLeMot($passage, $reponse)) {
                    $defauts[] = 'trou '.($n + 1)." : la reponse « {$reponse} » est ecrite dans le texte";
                }
                $texte = is_string($q['text'] ?? null) ? $q['text'] : '';
                if (preg_match_all('/_{2,}\s*\(([^)]+)\)/u', $texte, $indices)) {
                    foreach ($indices[1] as $indice) {
                        if (mb_strtolower(trim($indice)) === mb_strtolower($reponse)) {
                            $defauts[] = 'trou '.($n + 1)." : la reponse « {$reponse} » recopie l'indice";
                        }
                    }
                }
            }
        }

        // g. ECOUTES : le son dit le passage, pas la question. Releve : des
        //    ecoutes dont le son disait « Was macht Anna im Park? » (#464), ou
        //    la consigne (#387). Les dictees et repliques sont courtes par nature.
        if ($skillType === 'listening' && ! in_array($componentKey, ['dictation', 'listen-repeat', 'listen-choose-response'], true)) {
            foreach ($questions as $n => $q) {
                $son = $q['audio_text'] ?? $content['audio_text'] ?? null;
                if (! is_string($son) || trim($son) === '') {
                    continue; // pas de son ecrit : rien a juger ici
                }
                $motsDuSon = Exercise::nombreDeMots($son);
                $enonce = is_string($q['text'] ?? null) ? $q['text'] : '';
                if ($motsDuSon < 25) {
                    $defauts[] = 'ecoute '.($n + 1)." : {$motsDuSon} mots a entendre, il en faut au moins 25";
                } elseif ($enonce !== '' && $this->recopie($son, $enonce)) {
                    $defauts[] = 'ecoute '.($n + 1).' : le son recopie la question';
                }
            }
        }

        //    Et la MEME regle que le service : un son que
        //    Exercise::answerableQuestions remplacerait par le passage (moins du
        //    tiers du passage) n'est pas jouable tel quel, et sa piste serait
        //    jetee. Le generateur acceptait a 25 mots ce que le service
        //    ecartait ensuite. On demande donc a l'exercice lui-meme.
        if ($passage !== '') {
            $essai = new Exercise(['content' => $content, 'questions' => $questions]);
            $essai->setRelation('exerciseType', $exerciseType);
            foreach ($questions as $n => $q) {
                if (is_string($q['audio_text'] ?? null) && trim($q['audio_text']) !== ''
                    && $essai->sonDeLaQuestionTropPauvre($q)) {
                    $defauts[] = 'ecoute '.($n + 1).' : le son fait moins du tiers du passage, le lecteur le remplacerait';
                }
            }
        }

        // c. LA LANGUE DES EXPLICATIONS, verifiee et pas seulement demandee :
        //    un exercice explique dans la langue de l'examen la ou on attendait
        //    celle de l'apprenant entrerait dans le vivier sous une etiquette
        //    fausse. On ne juge que ce qui se reconnait sans doute possible.
        if ($langueDesExplications !== ''
            && ($ecart = $this->explicationsDansUneAutreLangue($questions, $langueDesExplications))) {
            $defauts[] = $ecart;
        }

        // h. VRAI/FAUX : au moins un « non mentionne » quand l'epreuve en
        //    offre un. Il n'apparaissait que 17 fois sur 320 questions : on
        //    s'entrainait a un choix qu'on ne cochait jamais.
        if ($libellesVraiFaux && count($libellesVraiFaux) === 3 && count($questions) >= 3) {
            $reponses = array_map(fn ($q) => $q['correct_answer'] ?? null, $questions);
            if (! in_array($libellesVraiFaux[2], $reponses, true)) {
                $defauts[] = "aucune affirmation « {$libellesVraiFaux[2]} »";
            }
        }

        // Deux textes sous la meme etiquette rendent une association
        // insoluble : a #581, les textes A et C s'appelaient tous deux
        // « Person A ».
        if ($componentKey === 'multiple-matching') {
            foreach ($questions as $q) {
                $titres = array_map(
                    fn ($t) => mb_strtolower(trim((string) (is_array($t) ? ($t['title'] ?? '') : ''))),
                    is_array($q['texts'] ?? null) ? $q['texts'] : []
                );
                $titres = array_filter($titres, fn ($t) => $t !== '');
                if (count($titres) !== count(array_unique($titres))) {
                    $defauts[] = 'deux textes portent la meme etiquette';
                }
            }
        }

        return array_values(array_unique($defauts));
    }

    /**
     * Les types dont l'epreuve est, par nature, un TEXTE a trous : le slug est
     * celui que chaque examen donne a sa partie (config/exams/*.php). Au telc,
     * les Sprachbausteine sont rangees en grammaire mais se passent sur une
     * lettre a trous (mcq-cloze, puis summary-completion) : la consigne
     * generale de la grammaire — « des phrases isolees, pas de texte » —
     * contredisait celle de la partie.
     */
    private const TYPES_TEXTE_A_TROUS = ['mcq-cloze', 'open-cloze', 'summary-completion'];

    private function texteATrous(ExerciseType $exerciseType): bool
    {
        return in_array($exerciseType->slug, self::TYPES_TEXTE_A_TROUS, true);
    }

    /**
     * Des mots-outils propres a chaque langue : assez pour reconnaitre la
     * langue d'une explication, sans pretendre a mieux.
     */
    private const MOTS_OUTILS = [
        'fr' => ['le', 'la', 'les', 'des', 'est', 'une', 'du', 'et', 'pour', 'dans', 'qui', 'pas', 'avec', 'au', 'sont', 'ce', 'il', 'on'],
        'en' => ['the', 'is', 'and', 'of', 'to', 'it', 'that', 'because', 'not', 'with', 'are', 'this', 'means'],
        'de' => ['der', 'die', 'das', 'und', 'ist', 'nicht', 'mit', 'ein', 'eine', 'im', 'den', 'dem', 'von', 'weil', 'auf', 'steht'],
        'es' => ['el', 'los', 'las', 'porque', 'una', 'con', 'por', 'del', 'está', 'significa', 'y'],
        'it' => ['il', 'gli', 'della', 'perché', 'sono', 'non', 'è', 'che'],
        'pt' => ['os', 'não', 'uma', 'com', 'porque', 'é', 'da', 'também'],
    ];

    /** Le code d'une langue nommee comme dans un profil (« Français », « German »...), ou null. */
    private function codeDeLangue(string $nom): ?string
    {
        $nom = strtolower(trim(\Illuminate\Support\Str::ascii($nom)));
        $nom = preg_replace('/[^a-z]/', '', $nom);

        return match (true) {
            in_array($nom, ['francais', 'french', 'franzosisch', 'frances'], true) => 'fr',
            in_array($nom, ['english', 'anglais', 'englisch', 'ingles'], true) => 'en',
            in_array($nom, ['deutsch', 'german', 'allemand', 'aleman'], true) => 'de',
            in_array($nom, ['espanol', 'spanish', 'espagnol', 'spanisch'], true) => 'es',
            in_array($nom, ['italiano', 'italian', 'italien', 'italienisch'], true) => 'it',
            in_array($nom, ['portugues', 'portuguese', 'portugais', 'portugiesisch'], true) => 'pt',
            default => null,
        };
    }

    /**
     * Les explications sont-elles clairement ecrites dans une AUTRE langue
     * que celle attendue ? Rend le defaut, ou null.
     *
     * Volontairement prudent : on ne refuse que quand une autre langue se
     * reconnait nettement (au moins quatre mots-outils) et que la langue
     * attendue n'en montre AUCUN. Une explication en francais qui cite un mot
     * allemand (« der » est masculin) reste donc en francais.
     */
    private function explicationsDansUneAutreLangue(array $questions, string $langueAttendue): ?string
    {
        $attendue = $this->codeDeLangue($langueAttendue);
        if ($attendue === null) {
            return null; // langue qu'on ne sait pas reconnaitre : on ne juge pas
        }

        $textes = [];
        foreach ($questions as $q) {
            // Une explication par trou, quand il y en a : l'explication
            // d'ensemble en est composee, la compter aussi doublerait tout.
            if (is_array($q['explanations'] ?? null)) {
                foreach ($q['explanations'] as $e) {
                    if (is_string($e)) {
                        $textes[] = $e;
                    }
                }
            } elseif (is_string($q['explanation'] ?? null)) {
                $textes[] = $q['explanation'];
            }
        }
        // Les mots cites entre guillemets sont des exemples, pas la langue de l'explication.
        $texte = preg_replace('/«[^»]*»|"[^"]*"|“[^”]*”|„[^“”]*[“”]/u', ' ', implode(' ', $textes));
        $mots = preg_split('/[^\p{L}]+/u', mb_strtolower((string) $texte), -1, PREG_SPLIT_NO_EMPTY);
        if ($mots === []) {
            return null;
        }

        $compte = [];
        foreach (self::MOTS_OUTILS as $code => $liste) {
            $compte[$code] = count(array_filter($mots, fn ($m) => in_array($m, $liste, true)));
        }
        arsort($compte);
        $dominante = array_key_first($compte);

        if ($dominante !== $attendue && $compte[$dominante] >= 4 && $compte[$attendue] === 0) {
            return "explications ecrites dans une autre langue que {$langueAttendue}";
        }

        return null;
    }

    /** Le mot (ou l'expression) figure-t-il tel quel dans le texte, en mot entier ? */
    private function contientLeMot(string $texte, string $mot): bool
    {
        return (bool) preg_match('/(?<![\p{L}\p{N}])'.preg_quote($mot, '/').'(?![\p{L}\p{N}])/iu', $texte);
    }

    /** Le son ne fait-il que redire la question ? */
    private function recopie(string $son, string $enonce): bool
    {
        $aPlat = fn (string $t) => trim(preg_replace('/[^\p{L}\p{N}]+/u', ' ', mb_strtolower($t)));
        $son = $aPlat($son);
        $enonce = $aPlat($enonce);
        if ($enonce === '' || $son === '') {
            return false;
        }
        if ($son === $enonce) {
            return true;
        }

        // La question, a peine entouree : « Hor zu. Was macht Anna im Park? »
        return str_contains($son, $enonce)
            && Exercise::nombreDeMots($son) <= Exercise::nombreDeMots($enonce) * 1.5;
    }

    /**
     * La verification a l'aveugle : un second appel resout l'exercice sans
     * la cle.
     *
     * Rend la raison du refus, ou null quand l'exercice passe. On ne juge que
     * ce qui se compare sans ambiguite : les questions a choix (une lettre) et
     * les vrai/faux (un libelle). Si la verification elle-meme echoue —
     * fournisseur muet, reponse illisible —, on ne refuse pas : ce n'est pas
     * l'exercice qui est en faute, et le refuser priverait l'apprenant de tout
     * exercice pendant une panne.
     */
    private function verifierALAveugle(array $candidate, ?string $componentKey, ?array $libellesVraiFaux): ?string
    {
        if (! config('prepla.generation.verification_aveugle', true)) {
            return null;
        }

        $content = is_array($candidate['content'] ?? null) ? $candidate['content'] : [];
        $items = [];
        $attendus = [];
        $optionsParId = [];
        $support =trim((string) ($content['passage'] ?? $content['audio_text'] ?? ''));

        foreach (array_values($candidate['questions'] ?? []) as $n => $q) {
            if (! is_array($q)) {
                continue;
            }
            $id = (string) ($n + 1);
            if ($support === '' && is_string($q['audio_text'] ?? null)) {
                $support = trim($q['audio_text']);
            }
            $enonce = trim((string) Exercise::optionText($q['text'] ?? $q['sentence'] ?? ''));

            $options = Exercise::optionList($q['options'] ?? null);
            $lettre = is_string($q['correct_answer'] ?? null) ? strtoupper(trim($q['correct_answer'])) : '';
            if ($options !== [] && preg_match('/^[A-Z]$/', $lettre)) {
                $choix = [];
                foreach ($options as $i => $option) {
                    $choix[chr(65 + $i)] = $option;
                }
                $items[] = ['id' => $id, 'question' => $enonce, 'options' => $choix];
                $attendus[$id] = $lettre;
                $optionsParId[$id] = $choix;

                continue;
            }

            if ($libellesVraiFaux && is_string($q['correct_answer'] ?? null)) {
                $items[] = ['id' => $id, 'statement' => $enonce, 'choices' => $libellesVraiFaux];
                $attendus[$id] = mb_strtolower(trim($q['correct_answer']));
            }
        }

        if ($items === []) {
            return null;
        }

        $demande = "You are a careful candidate sitting a language exam. Solve every item below WITHOUT any answer key, "
            ."using only the text provided (it may be the transcript of a recording).\n"
            ."For an item with \"options\", answer with the LETTER of the single correct option. "
            ."For an item with \"choices\", answer with one of the choices, copied exactly.\n"
            ."If more than one option could be defended as correct, or none is correct, put the item id in \"ambiguous\".\n"
            ."Return JSON only: {\"answers\": {\"<id>\": \"<letter or choice>\"}, \"ambiguous\": [\"<id>\"]}\n\n"
            .($support !== '' ? "TEXT:\n{$support}\n\n" : '')
            .'ITEMS: '.json_encode($items, JSON_UNESCAPED_UNICODE);

        $reponse = $this->mistral->chat([
            ['role' => 'system', 'content' => 'You solve exam items honestly and say when an item is ambiguous. Always respond with valid JSON only.'],
            ['role' => 'user', 'content' => $demande],
        ]);
        $lu = is_string($reponse) ? json_decode($reponse, true) : null;
        if (! is_array($lu) || ! is_array($lu['answers'] ?? null)) {
            Log::info('Exercise generation: blind check unavailable, exercise kept', ['component' => $componentKey]);

            return null;
        }

        $ecarts = [];
        foreach ($attendus as $id => $attendu) {
            $donnee = $lu['answers'][$id] ?? null;
            if (! is_scalar($donnee) || trim((string) $donnee) === '') {
                continue; // non resolue : ni pour ni contre
            }
            $donnee = $this->lireLaReponseAveugle(trim((string) $donnee), $optionsParId[$id] ?? null, $libellesVraiFaux);
            if ($donnee !== $attendu) {
                $ecarts[] = "question {$id} : resolue « {$donnee} », la cle dit « {$attendu} »";
            }
        }
        foreach ((array) ($lu['ambiguous'] ?? []) as $id) {
            if (is_scalar($id) && isset($attendus[(string) $id])) {
                $ecarts[] = "question {$id} : plusieurs bonnes reponses ou aucune";
            }
        }

        return $ecarts === [] ? null : 'verification a l\'aveugle : '.implode(' ; ', array_unique($ecarts));
    }

    /**
     * La reponse du second regard, ramenee a la forme de la cle : une lettre
     * majuscule pour un choix, un libelle en minuscules pour un vrai/faux.
     *
     * @param  array<string, string>|null  $options  lettre → texte de l'option
     */
    private function lireLaReponseAveugle(string $donnee, ?array $options, ?array $libellesVraiFaux): string
    {
        if ($options !== null) {
            // « B », « B) », « b. Berlin »
            if (preg_match('/^([A-Za-z])(?:$|[).:\s])/u', $donnee, $m) && isset($options[strtoupper($m[1])])) {
                return strtoupper($m[1]);
            }
            // Le texte de l'option recopie au lieu de sa lettre.
            foreach ($options as $lettre => $texte) {
                if (mb_strtolower(trim($texte)) === mb_strtolower($donnee)) {
                    return $lettre;
                }
            }

            return mb_strtoupper($donnee);
        }

        if ($libellesVraiFaux) {
            $ramenee = $this->appliquerLibellesVraiFaux([['correct_answer' => $donnee]], $libellesVraiFaux);
            if (isset($ramenee[0]['correct_answer'])) {
                return mb_strtolower($ramenee[0]['correct_answer']);
            }
        }

        return mb_strtolower($donnee);
    }

    /**
     * L'exercice doit-il s'appuyer sur un texte a lire ?
     *
     * Pour un exercice de lecon (grammaire, vocabulaire), le texte est un
     * piege : l'apprenant recopie au lieu d'appliquer la regle. Et une section
     * de grammaire n'en a jamais : c'etait de la comprehension ecrite deguisee.
     */
    private function passageAttendu(string $componentKey, string $skillType, ?array $lessonContext, bool $texteATrous = false): bool
    {
        // Une partie de grammaire que l'examen declare en texte a trous (telc,
        // Sprachbausteine) a un texte : c'est la lettre a trous elle-meme.
        if ($skillType === 'grammar') {
            return $texteATrous;
        }

        // Une ecoute n'a pas de texte a LIRE : ce qu'on entend est dans
        // audio_text. Le prompt demandait en plus un passage a la longueur de
        // lecture du niveau (400-600 mots au C1) : l'enregistrement de 60-100
        // mots en faisait alors moins du tiers, et le service le remplacait
        // par ce passage en jetant sa piste (Exercise::sonDeLaQuestionTropPauvre).
        if ($skillType === 'listening') {
            return false;
        }

        $avecTexte = in_array($componentKey, ['mcq', 'true-false-ng', 'gap-fill', 'matching', 'sentence-completion', 'short-answer', 'open-cloze'], true);

        return $avecTexte && ! ($lessonContext && $skillType !== 'reading');
    }

    private function dropInvalidChoiceQuestions(array $questions, ?string $componentKey = null): array
    {
        // L'EXPLICATION POUR TOUT FORMAT CORRIGE AUTOMATIQUEMENT. Elle n'etait
        // exigee que pour le QCM, le texte a trous et la phrase a completer :
        // en production, 346 associations sur 451 et toutes les completions de
        // notes en etaient privees. Un apprenant qui se trompe sans savoir
        // pourquoi n'apprend rien. Pour les formats a champs multiples, c'est
        // une explication PAR TROU (voir explicationsParTrou).
        $requireExplanation = $this->exigeUneExplication($componentKey);
        $multi = ExerciseSchemaRegistry::familyFor((string) $componentKey) === ExerciseSchemaRegistry::FAMILY_MULTI;

        $questions = array_map(
            fn ($q) => ($requireExplanation && $multi && is_array($q)) ? $this->explicationsParTrou($q) : $q,
            $questions
        );

        return array_values(array_filter($questions, function ($q) use ($requireExplanation, $componentKey) {
            if (! is_array($q)) {
                return false;
            }
            // Reject if the question's own declared `type` doesn't match the
            // component_key it was generated for — Mistral occasionally returns
            // a generic/wrong-type payload (confirmed: a "matching" request
            // answered with type "mcq" and English placeholder content instead
            // of a real exercise in the target language).
            $declaredType = $q['type'] ?? null;
            if ($componentKey && is_string($declaredType) && $declaredType !== $componentKey
                && ! in_array($declaredType, ['role-play', 'synthesis'], true)) {
                // role-play/synthesis are legitimate shared render types used by
                // several component_keys (oral-debate, negotiation, synthesis-essay…).
                return false;
            }
            $text = mb_strtolower(trim((string) ($q['text'] ?? '')));
            if ($componentKey === 'build-a-sentence') {
                $words = $q['words'] ?? null;
                if (! is_array($words) || count($words) < 2 || ! is_string($q['correct_answer'] ?? null)) {
                    return false;
                }
                // Every token, including duplicates, must be usable exactly once.
                $normalize = fn ($value) => trim(preg_replace('/[.!?]+$/u', '', mb_strtolower(trim($value))));
                foreach ($words as $word) {
                    if (! is_string($word) || trim($word) === '') return false;
                }
                $tokens = preg_split('/\s+/u', $normalize(implode(' ', $words)), -1, PREG_SPLIT_NO_EMPTY);
                $expected = preg_split('/\s+/u', $normalize($q['correct_answer']), -1, PREG_SPLIT_NO_EMPTY);
                sort($tokens);
                sort($expected);
                if ($tokens !== $expected) return false;
            }
            foreach (self::SENTINEL_TEXTS as $sentinel) {
                if ($text !== '' && str_contains($text, $sentinel)) {
                    return false;
                }
            }

            $opts = $q['options'] ?? null;
            if (! is_array($opts)) {
                // L'explication est LUE : rendue en objet (concept/evidence/hint), le
                // transtypage levait une alerte et la question etait jetee — parfois
                // toute la fournee, donc aucun exercice servi.
                if ($requireExplanation && $this->corrigeeAutomatiquement($q)
                    && trim(\App\Models\Exercise::optionText($q['explanation'] ?? '')) === '') {
                    return false;
                }

                return true; // not a choice-based question type — nothing else to validate
            }

            // Options must be non-empty, distinct strings.
            $normalized = array_map(fn ($o) => is_string($o) ? trim(mb_strtolower($o)) : null, $opts);
            if (in_array(null, $normalized, true) || in_array('', $normalized, true)) {
                return false;
            }
            if (count(array_unique($normalized)) !== count($normalized)) {
                return false; // duplicate options → shuffle/answer becomes ambiguous
            }

            $ca = $q['correct_answer'] ?? null;
            if (! is_string($ca)) {
                return false;
            }
            $letter = strtoupper(trim($ca));
            if (! preg_match('/^[A-Z]$/', $letter)) {
                return false; // choice question must resolve to a single letter after shuffling
            }
            $idx = ord($letter) - 65;
            if ($idx < 0 || $idx >= count($opts)) {
                return false;
            }

            if ($requireExplanation && trim(\App\Models\Exercise::optionText($q['explanation'] ?? '')) === '') {
                return false;
            }

            return true;
        }));
    }

    /**
     * Ce format est-il corrige automatiquement, et doit-il donc s'expliquer ?
     *
     * Tout ce qui n'est pas juge par l'IA : redactions et prises de parole
     * n'ont pas de reponse attendue a expliquer.
     */
    private function exigeUneExplication(?string $componentKey): bool
    {
        if (! $componentKey || in_array($componentKey, Exercise::AI_EVALUATED_TYPES, true)) {
            return false;
        }

        return ! in_array(ExerciseSchemaRegistry::familyFor($componentKey), [
            ExerciseSchemaRegistry::FAMILY_AI_WRITING, ExerciseSchemaRegistry::FAMILY_AI_SPEAKING,
        ], true);
    }

    /** La question porte-t-elle une reponse attendue (donc une correction automatique) ? */
    private function corrigeeAutomatiquement(array $q): bool
    {
        $attendu = $q['correct_answer'] ?? $q['correct_answers'] ?? $q['correct_order'] ?? null;

        return is_array($attendu) ? $attendu !== [] : (is_scalar($attendu) && trim((string) $attendu) !== '');
    }

    /**
     * Une explication par trou pour les formats a champs multiples.
     *
     * Le modele rend `explanations`, une carte aux memes cles que
     * `correct_answers`. Sans une entree non vide pour CHAQUE trou, la
     * question perd son explication et sera refusee. Avec, on en compose
     * aussi `explanation`, le champ que lisent la correction et l'ecran.
     */
    private function explicationsParTrou(array $q): array
    {
        $attendues = $q['correct_answers'] ?? null;
        $explications = $q['explanations'] ?? null;
        if (! is_array($attendues) || $attendues === []) {
            return $q;
        }

        if (! is_array($explications)) {
            unset($q['explanation']);

            return $q;
        }

        $lignes = [];
        foreach (array_keys($attendues) as $cle) {
            $texte = trim(Exercise::optionText($explications[$cle] ?? $explications[(string) $cle] ?? ''));
            if ($texte === '') {
                unset($q['explanation']);

                return $q;
            }
            $lignes[] = $this->nomDuTrou($q, $cle).' : '.$texte;
        }

        $q['explanation'] = implode("\n", $lignes);

        return $q;
    }

    /** Comment designer un trou dans l'explication composee. */
    private function nomDuTrou(array $q, int|string $cle): string
    {
        $attendue = $q['correct_answers'][$cle] ?? null;

        // Une association rend une lettre (« s1 » → « B ») : la lettre seule ne
        // dit rien, la cle de l'affirmation si.
        return is_scalar($attendue) && mb_strlen(trim((string) $attendue)) > 1
            ? '« '.trim((string) $attendue).' »'
            : (string) $cle;
    }

    /**
     * Validate the multi-field question types (correct_answers is a key→value
     * map rather than a single letter). Without this, exercises like
     * summary-completion could ship with a correct_answers value that isn't
     * even present in word_list — the student could then NEVER pick the right
     * answer from the dropdown, since it only lists word_list entries.
     */
    /**
     * Détecte une valeur "non vide" qui ressemble en fait à un marqueur de
     * blanc raté (ex: "___", "___ Jahre", "He ") plutôt qu'un vrai texte
     * pré-rempli — ces items ne rendent JAMAIS d'input côté front (seul
     * value==='' déclenche un champ éditable), donc l'élève ne peut ni les
     * voir comme un blanc à remplir ni les corriger : l'exercice est cassé.
     */
    private function hasFakeBlankMarker(array $items): bool
    {
        foreach ($items as $item) {
            $value = $item['value'] ?? null;
            if (! is_string($value) || $value === '') {
                continue;
            }
            // "___", "____ Jahre", "___..." : suite de underscores en tête de valeur.
            if (preg_match('/^_{2,}/', trim($value))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Vérifie que correct_answers correspond EXACTEMENT aux blancs réels de la
     * question (mêmes clés pour une map, même nombre pour une liste positionnelle)
     * — sans ça, une clé/entrée en trop pointe vers un champ que l'élève ne peut
     * jamais remplir, plafonnant le score sous le seuil de réussite quoi qu'il réponde.
     */
    private function multiFieldAnswersMatchBlanks(mixed $correctAnswers, array $blanks): bool
    {
        if (empty($blanks)) {
            return true; // rien à compléter, correct_answers n'a pas d'importance ici
        }
        if (! is_array($correctAnswers) || count($correctAnswers) === 0) {
            return false;
        }

        if (array_is_list($correctAnswers)) {
            return count($correctAnswers) === count($blanks);
        }

        $caKeys = array_map('strval', array_keys($correctAnswers));
        $blankKeys = array_map('strval', $blanks);
        sort($caKeys);
        sort($blankKeys);

        return $caKeys === $blankKeys;
    }

    private function dropInvalidMultiFieldQuestions(array $questions, string $componentKey): array
    {
        $multiFieldTypes = ['note-completion', 'form-completion', 'summary-completion', 'table-completion', 'flow-chart-completion', 'multiple-matching', 'diagram-labeling', 'open-cloze'];
        if (! in_array($componentKey, $multiFieldTypes, true)) {
            return $questions;
        }

        $norm = fn ($s) => is_string($s) ? trim(mb_strtolower($s)) : null;

        return array_values(array_filter($questions, function ($q) use ($componentKey, $norm) {
            $ca = $q['correct_answers'] ?? null;

            switch ($componentKey) {
                case 'note-completion':
                    // Un blanc mal signalé (l'IA écrit parfois "___" ou un préfixe de
                    // phrase comme value au lieu d'une vraie chaîne vide) rend le champ
                    // non-éditable côté front (isBlankNote() ne teste que value==='') —
                    // l'exercice devient alors partiellement ou totalement infaisable
                    // quoi que l'élève réponde. On le détecte ici pour rejeter/régénérer
                    // plutôt que de laisser passer un exercice cassé.
                    if ($this->hasFakeBlankMarker($q['notes'] ?? [])) {
                        return false;
                    }
                    $blanks = array_keys(array_filter($q['notes'] ?? [], fn ($n) => ($n['value'] ?? null) === ''));

                    return count($blanks) > 0 && $this->multiFieldAnswersMatchBlanks($ca, $blanks);

                case 'form-completion':
                    if ($this->hasFakeBlankMarker($q['fields'] ?? [])) {
                        return false;
                    }
                    $blanks = array_keys(array_filter($q['fields'] ?? [], fn ($f) => ($f['value'] ?? null) === ''));

                    return count($blanks) > 0 && $this->multiFieldAnswersMatchBlanks($ca, $blanks);

                case 'flow-chart-completion':
                    $blanks = array_keys(array_filter($q['steps'] ?? [], fn ($s) => ! empty($s['is_blank'])));

                    return empty($blanks) || (is_array($ca) && count($ca) > 0);

                case 'summary-completion':
                    if (! is_array($ca) || ! isset($q['word_list']) || ! is_array($q['word_list'])) {
                        return ! is_array($ca) ? true : false;
                    }
                    $wordList = array_map($norm, $q['word_list']);
                    foreach ($ca as $v) {
                        if (! in_array($norm($v), $wordList, true)) {
                            return false; // answer not selectable from the dropdown → unsolvable
                        }
                    }

                    return true;

                case 'table-completion':
                    $rows = $q['rows'] ?? null;
                    if (! is_array($rows)) {
                        return true;
                    }
                    $blankCount = 0;
                    foreach ($rows as $row) {
                        if (is_array($row)) {
                            foreach ($row as $cell) {
                                if ($cell === '') {
                                    $blankCount++;
                                }
                            }
                        }
                    }

                    return $blankCount === 0 || (is_array($ca) && count($ca) > 0);

                case 'multiple-matching':
                    $textIds = array_map(fn ($t) => $t['id'] ?? null, $q['texts'] ?? []);
                    $statementIds = array_map(fn ($s) => $s['id'] ?? null, $q['statements'] ?? []);
                    if (empty($statementIds) || ! is_array($ca)) {
                        return ! empty($statementIds) ? false : true;
                    }
                    foreach ($statementIds as $sid) {
                        if (! array_key_exists($sid, $ca) || ! in_array($ca[$sid], $textIds, true)) {
                            return false;
                        }
                    }

                    return true;

                case 'diagram-labeling':
                    $labelIds = array_map(fn ($l) => $l['id'] ?? null, $q['labels'] ?? []);
                    if (empty($labelIds)) {
                        return true;
                    }
                    if (! is_array($ca)) {
                        return false;
                    }
                    foreach ($labelIds as $lid) {
                        if (empty($ca[$lid] ?? null)) {
                            return false;
                        }
                    }

                    return true;

                case 'open-cloze':
                    $text = $q['text'] ?? '';
                    preg_match_all('/\((\d+)\)___/', is_string($text) ? $text : '', $matches);
                    $gapCount = count($matches[1] ?? []);

                    return $gapCount === 0 || (is_array($ca) && count($ca) >= $gapCount);

                default:
                    return true;
            }
        }));
    }

    /**
     * Scale a writing task's min/max word count to the CEFR level, instead of
     * a fixed value. Confirmed on real data (Exercise #359) that a fixed
     * 150-250 words for essay-editor was applied even at A1, where the actual
     * CEFR/Goethe expectation is closer to 30-40 words — wildly unrealistic
     * for a true beginner. Ratios roughly follow short-answer's existing
     * C1/C2 special-case, generalised across the whole scale.
     */
    private function wordRangeFor(string $difficulty): array
    {
        return match ($difficulty) {
            'A0', 'A1' => [30, 50],
            'A2' => [50, 80],
            'B1' => [80, 120],
            'B2' => [120, 180],
            'C1', 'C2' => [180, 250],
            default => [80, 120],
        };
    }

    /**
     * La demande d'un centre, nettoyee : textes sur une ligne et coupes, nombre
     * de questions borne de 1 a 20. Ce qui manque vaut null.
     *
     * @return array{theme: ?string, consignes: ?string, nombre_questions: ?int}
     */
    public static function demandeDuCentre(?array $demande): array
    {
        $texte = function ($valeur, int $max): ?string {
            if (! is_string($valeur)) {
                return null;
            }
            $propre = trim((string) preg_replace('/\s+/u', ' ', $valeur));

            return $propre === '' ? null : mb_substr($propre, 0, $max);
        };
        $nombre = (int) ($demande['nombre_questions'] ?? 0);

        return [
            'theme' => $texte($demande['theme'] ?? null, 120),
            'consignes' => $texte($demande['consignes'] ?? null, 600),
            'nombre_questions' => $nombre > 0 ? min($nombre, 20) : null,
        ];
    }

    private function buildPrompt(ExerciseType $exerciseType, Exam $exam, string $difficulty, string $language, string $languageNative, ?array $lessonContext = null, ?array $partieExamen = null, ?string $faiblesse = null, ?array $demandeDuCentre = null, ?int $palier = null): string
    {
        $componentKey = $exerciseType->component_key;
        $skillType = $exerciseType->section?->skill_type ?? 'reading';
        // La langue de l'apprenant : celle de la lecon, sinon celle de son
        // profil, a defaut le francais. Hors lecon, elle valait toujours
        // « Français », meme pour un apprenant hispanophone.
        $nativeLang = $this->langueMaternelle($lessonContext);
        $langueExplications = $this->langueDesExplications($difficulty, $language, $lessonContext);
        $libellesVraiFaux = $this->libellesVraiFaux($exerciseType, $exam, $partieExamen);

        // Lesson-aware context: when an exercise is generated AFTER a lesson, it MUST test
        // the lesson's specific concept (e.g. "grammar.simple_present") — not random content.
        $lessonDirective = '';
        if ($lessonContext) {
            $title = $lessonContext['title'] ?? '';
            $concept = $lessonContext['concept'] ?? '';
            $isSynthesis = ! empty($lessonContext['is_synthesis']);
            $conceptsMixed = $lessonContext['concepts_to_mix'] ?? [];

            if ($isSynthesis) {
                $conceptsList = implode(', ', $conceptsMixed);
                $lessonDirective = <<<DIRECTIVE

CRITICAL — CHAPTER SYNTHESIS (BOSS LEVEL)
This is the END-OF-CHAPTER synthesis. The student just completed multiple
lessons in this chapter. The concepts covered are: {$conceptsList}.

This exercise MUST mix questions covering DIFFERENT concepts from the list above.
- Each of the 5 questions should test a DIFFERENT concept from the list (one per concept)
- Questions must require RECALL from long-term memory (not just re-reading a passage)
- Difficulty matches the chapter level
- Instructions in {$nativeLang} for A0/A1/A2 students
- This is a high-stakes test of mastery — write clear, fair, distinct questions

Output 5 questions. Each question's `text` should clearly indicate which concept
it tests (e.g. "Pronoms : ___" or "Présent simple : ___").
Each question MUST include `error_category`: the specific concept it assesses,
using a dotted category such as grammar.tense, grammar.articles or vocabulary.basic.
Never label every mistake level_exam or session_mistake: remediation uses these tags.
DIRECTIVE;
            } else {
                $lessonDirective = <<<DIRECTIVE

CRITICAL — LESSON-ALIGNED EXERCISE
The student just finished the lesson: "{$title}" (concept: {$concept}).
This exercise MUST test that specific concept. Examples:
- If concept involves verb conjugation: gap-fills must blank out the verb to conjugate
- If concept involves pronouns: gap-fills must blank out the pronoun
- If concept involves vocabulary: gap-fills must blank out a vocabulary item the lesson taught
- If concept involves grammar rule: produce sentences that require applying THAT rule

FORBIDDEN: producing a generic reading-comprehension where the gap answer is
literally findable by copying a word from the passage 1-2 sentences earlier.
The student must apply REASONING from the lesson, not visual recall.

For beginners (A0/A1/A2 native: {$nativeLang}):
- Instructions/questions in {$nativeLang}
- Example sentences in {$language} with {$nativeLang} translations
- Each gap-fill should isolate ONE concept point at a time
DIRECTIVE;
            }
        }

        if (! empty($lessonContext['key_vocabulary']) && empty($lessonContext['is_synthesis'])) {
            $words = json_encode($lessonContext['key_vocabulary'], JSON_UNESCAPED_UNICODE);
            $lessonDirective .= "\nUse these lesson words naturally in examples and sentence-building questions when relevant to the concept: {$words}. Keep the existing lesson concept as the main objective; do not introduce unrelated difficult vocabulary.\n";
        }
        [$wMin, $wMax] = $this->wordRangeFor($difficulty);

        $isListening = $skillType === 'listening';
        // In LISTENING, the audio is the CONTENT to understand (a 60-100-word
        // dialogue/monologue), and questions test comprehension of it by ear. The
        // spoken passage must carry the same audio_text on EACH question so the
        // player can play it. The questions must NOT restate the audio in writing.
        $audioField = $isListening ? ', audio_text (string: the SAME spoken passage of 60-100 words in '.$language.' — a short dialogue or monologue — repeated identically on every question; questions ask about details/intentions/facts that can only be answered by listening, NOT by reading the question)' : '';

        $listeningDirective = $isListening
            ? "\n\nLISTENING RULES:\n- audio_text is the recording (a 60-100-word dialogue/monologue). It is the SAME on every question.\n- audio_text is NEVER the question itself nor the instruction: it is the text the student must understand, at least 25 words.\n- Questions test comprehension BY EAR (who, what, where, why, intention). Do NOT write the answer in the question.\n- Never just name a term and ask to match it to its definition — that's not listening."
            : '';

        // A beginner (A0/A1/A2) cannot parse a scenario/role instruction written
        // entirely in the target language — they don't know enough of it yet. So
        // for speaking exercises, the STAGE DIRECTIONS (scenario, role, candidate
        // hints) go in the student's native language, while the examiner's actual
        // spoken lines (what gets read aloud via TTS) stay in the target language —
        // that's the whole point of a listening/speaking exercise.
        $isBeginnerLevel = in_array($difficulty, ['A0', 'A1', 'A2'], true);
        $scenarioLang = $isBeginnerLevel ? $nativeLang : $language;
        $speakingDirective = $isBeginnerLevel
            ? "\n\nBEGINNER SPEAKING ({$difficulty}): the student does not read {$language} fluently yet.\n- \"scenario\", \"role\", and every candidate \"prompt\" (hint) MUST be written in {$nativeLang}, so the student understands WHAT to do.\n- Only the examiner's spoken \"text\" lines (read aloud via TTS) stay in {$language}, and must use SIMPLE, SHORT, high-frequency {$difficulty}-level sentences.\n- Never require the student to have read a {$language} instruction to know how to respond — the instruction itself must be in {$nativeLang}."
            : '';

        // Rien n'INTERDISAIT le QCM pour une tache orale, et la directive
        // ci-dessus ne vaut qu'aux niveaux debutants. Au B1, interroge sur une
        // « Discussion argumentee », Mistral rendait cinq questions a choix de
        // comprehension ecrite. Le validateur refusait la fournee entiere, a
        // juste titre, et aucun exercice oral ne pouvait entrer dans le vivier.
        $speakingDirective .= in_array($componentKey, ['speaking-recorder', 'role-play'], true)
            ? "

THIS IS A SPEAKING TASK — the student RECORDS their own voice. It is NOT a comprehension test.
- Return EXACTLY ONE question object, never several.
- NEVER include an \"options\" array, and NEVER write comprehension questions about a text.
- \"correct_answer\" is null: nothing here is marked right or wrong automatically.
- The question must give the student something to SAY."
            : '';

        // POURQUOI DES POINTS ATTENDUS. Une epreuve orale ne se corrige pas « au
        // feeling » : l'examinateur telc coche. A la Vorstellung, il verifie que
        // le candidat a dit son nom, d'ou il vient, ce qu'il fait. Sans cette
        // liste, l'IA jugeait librement et l'apprenant ne savait jamais ce qu'il
        // avait OUBLIE de dire — ce qui est precisement ce qu'il perd des points.
        $directiveDesPoints = in_array($componentKey, ['speaking-recorder', 'role-play'], true)
            ? "\n\nEXPECTED POINTS — mandatory. List 3 to 5 short, checkable things the answer must contain. "
                ."They are shown to the candidate on a card BEFORE speaking, so address them directly, and each must be verifiable from the recording alone "
                ."(\"Dis ton prénom\", \"Donne une raison\", \"Propose une heure\"), never in the third person, never vague (\"parle bien\"). "
                ."Keep them within reach of a {$difficulty} learner."
            : '';

        // Same beginner problem as speaking, but for the plain "text" instruction
        // field of types that have a REAL standalone instruction (as opposed to
        // summary-completion/open-cloze, where "text" IS the content to fill in —
        // translating that would break the exercise). Only applies when there is
        // no lessonContext already handling the native-language instruction.
        $instructionAdaptedTypes = ['form-completion', 'table-completion', 'flow-chart-completion', 'diagram-labeling', 'gapped-text', 'ordering'];
        $instructionLang = ($isBeginnerLevel && in_array($componentKey, $instructionAdaptedTypes, true)) ? $nativeLang : $language;
        $instructionDirective = ($isBeginnerLevel && in_array($componentKey, $instructionAdaptedTypes, true) && ! $lessonContext)
            ? "\n\nBEGINNER INSTRUCTION ({$difficulty}): the student does not read {$language} fluently yet.\n- The \"text\" instruction field MUST be written in {$nativeLang}, so the student understands WHAT is asked.\n- The actual content to complete/label/reorder (fields/rows/steps/labels/paragraphs/sentence) MUST STAY in {$language} — only the meta-instruction telling the student what to do switches language."
            : '';

        // Une tache de description d'image (Bildbesprechung de l'OSD B2) se reconnait
        // par son slug : le composant, lui, est l'enregistreur oral ordinaire.
        $estDescriptionImage = $exerciseType->slug === 'picture-description';

        $questionFormat = match (true) {
            $estDescriptionImage => 'Array of 1 question with: id (string), type ("speaking-recorder"), text (instruction in '.$scenarioLang.' asking the student to FIRST describe what the picture shows, THEN comment on it and give their own opinion), image_prompt (a short English description of an everyday scene to illustrate — people doing something ordinary, e.g. "a family having breakfast in a small kitchen" — no text, no charts), prep_time (60), speak_time (180), image_url (null), correct_answer (null)',
            default => match ($componentKey) {
            // --- Original components ---
            'mcq' => 'Array of 3 questions, each with: id (string q1/q2/q3), type ("mcq"), text (question in '.$language.'), options (array of exactly 4 answer choices in '.$language.', do NOT include letters like "A)" prefix), correct_answer (ONLY the letter: "A", "B", "C", or "D" corresponding to the correct option index), explanation (string in '.$langueExplications.')'.$audioField,
            'true-false-ng' => 'Array of 3 statements, each with: id (string), type ("true-false-ng"), text (statement in '.$language.'), correct_answer ('.implode('/', array_map(fn ($l) => '"'.$l.'"', $libellesVraiFaux ?? ['True', 'False', 'Not Given'])).' — exactly one of these labels), explanation (string)'.$audioField,
            'gap-fill' => 'Array of 3 items, each with: id (string), type ("gap-fill"), text (sentence in '.$language.' containing EXACTLY ONE blank written as ___ — never two or more blanks in the same sentence), correct_answer (string: the single word or phrase that fills that one blank), explanation (string)'.$audioField,
            'matching' => 'Array of 4 questions, each with: id (string q1-q4), type ("matching"), text (the term/concept to identify in '.$language.'), options (array of exactly 4 definitions in '.$language.' — one correct, three plausible distractors, shuffled), correct_answer (ONLY the letter "A", "B", "C", or "D" for the correct option)'.$audioField,
            'essay-editor' => 'Array of 1 question with: id (string), type ("essay-editor"), text (writing prompt in '.$language.', scoped to what a '.$difficulty.' learner can realistically write about), min_words ('.$wMin.'), max_words ('.$wMax.'), correct_answer (null — scored manually)',
            'sentence-completion' => 'Array of 3 items, each with: id (string), type ("sentence-completion"), text (incomplete sentence in '.$language.'), options (array of 4 choices), correct_answer (letter "A"/"B"/"C"/"D"), explanation (string)'.$audioField,
            'short-answer' => 'Array of 3 items, each with: id (string), type ("short-answer"), text (question in '.$language.'), correct_answer ('.(in_array($difficulty, ['C1', 'C2'], true) ? 'a concise written answer, one short sentence ~10-15 words, capturing the key idea' : 'short answer string, max 3 words').'), explanation (string)'.$audioField,
            'note-completion' => 'Array of 1 question with: id (string), type ("note-completion"), notes (array of objects {label: string, value: string} where value is blank "" for items the student must fill, or pre-filled text), correct_answers (object whose keys are the 0-BASED INDEX of each blank note in the notes array — e.g. if notes[1] and notes[3] are blank, correct_answers is {"1": "...", "3": "..."})'.($isListening ? ', audio_text (string: spoken passage 80-120 words in '.$language.' — the recording students listen to)' : ''),
            'ordering' => 'Array of 1 question with: id (string), type ("ordering"), text (instruction in '.$instructionLang.'), items (array of 5-6 items to reorder, in CORRECT order), correct_order (array of ids in correct sequence)',
            'dictation' => 'Array of 1 question with: id (string), type ("dictation"), audio_text (text to be read aloud in '.$language.', 30-50 words), text (instruction), correct_answer (the exact text)',
            'open-cloze' => 'Array of 1 question with: id (string), type ("open-cloze"), text (a COHERENT connected passage of 80-120 words in '.$language.' with 8-10 numbered gaps written EXACTLY as (1)___, (2)___, (3)___ … in reading order; each gap targets grammar/function words the student must PRODUCE — articles, prepositions, verb forms, conjunctions, pronouns — NOT random content words copyable from context), correct_answers (object mapping "1","2",… to the exact expected word for each gap)',
            'word-formation' => 'Array of 3 items, each with: id (string), type ("word-formation"), text (sentence with ___ gap), root_word (base word to transform), correct_answer (the transformed word), explanation (string)',
            'key-word-transformation' => 'Array of 3 items, each with: id (string), type ("key-word-transformation"), original_sentence (in '.$language.'), key_word (word that must be used), correct_answer (transformed sentence), explanation (string)',

            // --- Sprint 1: Simple text components ---
            'short-writing' => 'Array of 1 question with: id (string), type ("short-writing"), text (writing prompt in '.$language.', e.g. write a postcard/email/message), context (optional context string), min_words (30), max_words (80), correct_answer (null)',
            'form-completion' => 'Array of 1 question with: id (string), type ("form-completion"), text (instruction in '.$instructionLang.'), fields (array of 6-8 objects {label: string in '.$language.', value: string or "" for blanks, type: "text"|"date"|"select", options?: string[] for select}), correct_answers (object whose keys are the 0-BASED INDEX of each blank field in the fields array — e.g. if fields[2] is blank, correct_answers is {"2": "..."})',
            'summary-completion' => 'Array of 1 question with: id (string), type ("summary-completion"), text (summary passage in '.$language.' with ___ for each blank), word_list (array of 8-10 words, including correct answers plus distractors), correct_answers (object mapping blank index "0","1",etc. to correct word)',

            // --- Sprint 2: Table/visual components ---
            'table-completion' => 'Array of 1 question with: id (string), type ("table-completion"), text (instruction in '.$instructionLang.'), headers (array of column header strings), rows (2D array of strings, use "" for blank cells students must fill), correct_answers (object mapping "rowIndex-colIndex" to correct value for each blank cell)',
            'flow-chart-completion' => 'Array of 1 question with: id (string), type ("flow-chart-completion"), text (instruction in '.$instructionLang.'), steps (array of objects {text: string or "" for blanks, is_blank: boolean}), correct_answers (object mapping blank step indices to correct text)',
            'multiple-matching' => 'Array of 1 question with: id (string), type ("multiple-matching"), texts (array of 4 objects {id: "A"/"B"/"C"/"D", title: string, content: string in '.$language.'}), statements (array of 6 objects {id: "s1"-"s6", text: string in '.$language.'}), correct_answers (object mapping statement id to text id, e.g. {"s1":"B","s2":"A",...})',

            // --- Sprint 3: Interactive components ---
            'insert-text' => 'Array of 1 question with: id (string), type ("insert-text"), sentence (the sentence to insert, in '.$language.'), passage (text with markers [A], [B], [C], [D] where sentence could be inserted), correct_answer ("A"/"B"/"C"/"D")',
            'gapped-text' => 'Array of 1 question with: id (string), type ("gapped-text"), text (instruction in '.$instructionLang.'), passage_parts (array of strings, the main text split at gaps), paragraphs (array of objects {id: "p1"-"p5", text: string in '.$language.'} — paragraphs to place in gaps), correct_order (array of paragraph ids in correct gap order)',
            'graph-description' => 'Array of 1 question with: id (string), type ("graph-description"), text (writing prompt describing the chart in '.$language.'), chart_data ({type: "bar"|"line"|"pie", labels: string[], datasets: [{label: string, data: number[]}]} — REQUIRED, this is what the student sees), image_prompt (a short English description of the same chart, used only if chart_data is unusable), min_words ('.$wMin.'), max_words ('.$wMax.'), correct_answer (null)',
            'academic-discussion' => 'Array of 1 question with: id (string), type ("academic-discussion"), professor_prompt (discussion topic in '.$language.'), student_posts (array of 2 objects {name: string, text: opinion in '.$language.'}), writing_prompt (instruction for student response in '.$language.'), min_words ('.$wMin.'), max_words ('.$wMax.'), correct_answer (null)',

            // --- Sprint 4: Audio/Speaking components ---
            // scenario/role/prompt use $scenarioLang (native language for A0-A2 beginners,
            // target language otherwise) since they're instructions the student must
            // understand — only the examiner's spoken "text" stays in $language.
            'speaking-recorder' => 'Array of 1 question with: id (string), type ("speaking-recorder"), text (speaking prompt in '.$scenarioLang.', e.g. describe an experience, give opinion on topic), expected_points (array of 3 to 5 SHORT strings in '.$scenarioLang.' — the things the answer must contain for the examiner to tick them off; for a self-introduction: name, where you are from, what you do, how long you have been learning, one hobby), prep_time (30), speak_time (60), image_url (null), correct_answer (null)',
            'role-play' => 'Array of 1 question with: id (string), type ("role-play"), expected_points (array of 3 to 5 SHORT strings in '.$scenarioLang.' — what the candidate must manage to say or obtain for the task to count as done), scenario (situation description in '.$scenarioLang.'), role (candidate role in '.$scenarioLang.'), dialogue_turns (array of 4-6 objects {speaker: "examiner"|"candidate", text: string for examiner lines in '.$language.', prompt: string for candidate hints in '.$scenarioLang.'}), correct_answer (null)',
            // (suite du match sur $componentKey)
            // Interactive speaking — all rendered by the role-play component (type "role-play"),
            // alternating examiner (spoken via TTS) ↔ candidate (records, live-scored).
            'oral-debate' => 'Array of 1 question with: id (string), type ("role-play"), scenario (a debatable topic + the examiner\'s stance, in '.$scenarioLang.'), role ("Tu défends ton point de vue face à l\'examinateur" translated to '.$scenarioLang.' if needed), dialogue_turns (array of 6 objects ALTERNATING: examiner turns have text = a provocative claim/counter-argument in '.$language.'; candidate turns have prompt = what to argue, in '.$scenarioLang.', e.g. "Réfute cet argument avec un exemple"), correct_answer (null)',
            'negotiation' => 'Array of 1 question with: id (string), type ("role-play"), scenario (a situation requiring agreement, e.g. planning an outing together / sharing tasks, in '.$scenarioLang.'), role (candidate role, in '.$scenarioLang.'), dialogue_turns (array of 6 objects ALTERNATING: examiner text = a proposal or objection in '.$language.'; candidate prompt = in '.$scenarioLang.', e.g. "Propose une alternative et justifie" / "Trouve un compromis"), correct_answer (null)',
            'speaking-elicitation' => 'Array of 1 question with: id (string), type ("role-play"), scenario (a context where the candidate must OBTAIN information, e.g. about a job ad / a course, in '.$scenarioLang.'), role ("Tu poses des questions pour obtenir des informations" in '.$scenarioLang.'), dialogue_turns (array of 5 objects: each examiner turn text = a brief setup/answer in '.$language.', each candidate prompt = in '.$scenarioLang.', e.g. "Pose une question pour savoir : [horaires/prix/lieu/…]"), correct_answer (null)',
            'listen-repeat' => 'Array of 3 items, each with: id (string), type ("listen-repeat"), audio_text (a sentence of 8-15 words in '.$language.' to be read aloud and repeated), correct_answer (the same sentence exactly — used to score pronunciation fidelity)',
            'picture-mcq' => 'Array of 3 questions, each with: id (string), type ("picture-mcq"), text (the question/instruction in '.$language.'), image_prompts (array of EXACTLY 4 short English scene descriptions for image generation, e.g. "a woman drinking coffee at a café", "a man riding a bicycle"; concrete, distinct, simple), options (array of 4 short '.$language.' labels matching each image), correct_answer (the letter "A","B","C" or "D" of the right picture)'.($isListening ? ', audio_text (the SAME spoken passage on each question — what the student hears and must match to a picture)' : ''),
            // TOEFL 2026
            'complete-the-words' => 'Array of 1 question with: id (string), type ("complete-the-words"), text (an academic paragraph of 60-90 words in '.$language.' where 6-10 words have MISSING LETTERS shown as the first 2-3 letters followed by underscores for each missing letter, e.g. "The eco___ grew while gov______ spending fell"), correct_answers (object mapping "0","1",… in reading order to the FULL correct word for each blanked word)',
            'build-a-sentence' => 'Array of 3 questions, each with: id (string), type ("build-a-sentence"), text (the preceding line of a short student exchange in '.$language.', for context), words (array of the sentence tokens IN CORRECT ORDER — the UI shuffles them), correct_answer (the full correct sentence string)',
            'listen-choose-response' => 'Array of 3 questions, each with: id (string), type ("listen-choose-response"), audio_text (a single short spoken sentence or question in '.$language.' — e.g. "Do you know where the library is?"), options (array of 4 possible spoken replies in '.$language.', one pragmatically correct, three plausible but wrong), correct_answer (the letter of the best reply)',
            // Écriture guidée (AI-évaluée)
            'guided-rewrite' => 'Array of 1 question with: id (string), type ("guided-rewrite"), source_text (a passage of 80-120 words in '.$language.'), text (instruction in '.$language.': reformuler/résumer le passage en utilisant les mots imposés), must_use (array of 3-5 mandatory words/expressions in '.$language.'), min_words (40), max_words (80), correct_answer (null — scored by AI)',
            'text-continuation' => 'Array of 1 question with: id (string), type ("text-continuation"), source_text (the BEGINNING of a story/fait divers, 2-3 sentences in '.$language.'), text (instruction: continuer le texte de façon cohérente), min_words (80), max_words (120), correct_answer (null — scored by AI)',

            // --- Sprint 5: Complex components ---
            'diagram-labeling' => 'Array of 1 question with: id (string), type ("diagram-labeling"), text (instruction in '.$instructionLang.'), image_url (null), image_prompt (a short English description of the diagram to illustrate, e.g. "a simple labelled diagram of a bicycle"), labels (array of 4-6 objects {id: "l1"-"l6", x: number 10-90, y: number 10-90, answer: correct label string}), correct_answers (object mapping label id to correct text)',
            'synthesis' => 'Array of 1 question with: id (string), type ("synthesis"), documents (array of 2-3 objects {title: string, content: text of 100-150 words in '.$language.'}), writing_prompt (synthesis instruction in '.$language.'), min_words (220), max_words (250), correct_answer (null)',
            'synthesis-essay' => 'Array of 1 question with: id (string), type ("synthesis") [the component renders "synthesis"], documents (array of 2-3 objects {title: string, content: text of 120-180 words in '.$language.' presenting DIFFERENT viewpoints on a topic}), writing_prompt (instruction in '.$language.': first SYNTHESISE the documents, THEN take and defend a personal position — DALF C2 style), min_words (250), max_words (300), correct_answer (null)',
            'integrated-task' => 'Array of 1 question with: id (string), type ("integrated-task"), reading_passage ({title: string, content: 200-word text in '.$language.'}), audio_text (100-word listening text in '.$language.' for TTS), audio_lang ("'.strtolower(substr($language, 0, 2)).'"), response_type ("writing"), writing_prompt (instruction in '.$language.'), min_words (150), max_words (225), correct_answer (null)',

                default => 'Array of 3 questions with: id (string), type ("mcq"), text, options (4 choices), correct_answer, explanation',
            },
        };

        // Determine content structure based on component type.
        // For lesson-aligned grammar/vocab exercises (gap-fill, sentence-completion,
        // short-answer, mcq, matching), the passage is usually a distractor: it tempts
        // students to copy-paste instead of applying the concept. Skip the passage when
        // we have lesson context, unless the skill is reading.
        // Une section de grammaire n'a pas de texte a lire (voir passageAttendu).
        $texteATrous = $skillType === 'grammar' && $this->texteATrous($exerciseType);
        $needsPassage = $this->passageAttendu($componentKey, $skillType, $lessonContext, $texteATrous);

        // La longueur du texte suit le niveau : « 150-200 words » partout
        // donnait des textes trop longs au A1 et trop courts au C1.
        [$passageMin, $passageMax] = $this->plageDuPassage($difficulty);
        $contentStructure = match (true) {
            // Le texte a trous d'une partie de grammaire : la lettre elle-meme,
            // dont les trous sont les items. Rien a comprendre, tout a completer.
            $texteATrous && $componentKey !== 'summary-completion' && $componentKey !== 'open-cloze' => '"content": {"passage": "The '.$difficulty.'-level text in '.$language.' ('.$passageMin.'-'.$passageMax.' words) with its gaps numbered (1)___, (2)___ … in reading order, one per item", "instructions": "Instructions in '.$language.'"}',
            $needsPassage && ! $texteATrous => '"content": {"passage": "A '.$difficulty.'-level text in '.$language.' ('.$passageMin.'-'.$passageMax.' words) — questions must require INFERENCE, not literal copying from the passage; distractors must be things the text mentions but that do not answer the question (paraphrase, never copy)", "instructions": "Instructions in '.$language.'"}',
            default => '"content": {"instructions": "Instructions in '.$language.'"}',
        };

        // Deux consignes de grammaire : des phrases isolees en general, mais le
        // texte a trous la ou l'examen le declare pour cette partie. Envoyer
        // les deux a la fois donnait au modele des ordres contraires.
        $grammarDirective = match (true) {
            $texteATrous => "\n\nTHIS IS A GRAMMAR PART PASSED ON A TEXT WITH GAPS (as the exam part above describes):\n- Write ONE coherent text (a letter, an e-mail, an article — whatever the part says) and blank out the words to test.\n- Each item is ONE gap of that text and tests grammar or vocabulary the student must apply — never comprehension of the text. When an item has a \"text\", it repeats the sentence of the text that holds its gap, the gap written ___.\n- NEVER ask \"according to the text\" questions: the text is only the frame of the gaps.",
            $skillType === 'grammar' => "\n\nTHIS IS A GRAMMAR SECTION, NOT A READING TEST:\n- Do NOT write any passage or text to read: no \"passage\" field.\n- Each item is a standalone sentence that tests ONE grammar point the student must apply.\n- NEVER write \"according to the text\", \"selon le texte\", \"laut Text\" or any reference to a text.",
            default => '',
        };

        $gapFillBan = $componentKey === 'gap-fill'
            ? "\n\nCRITICAL FOR GAP-FILL (grammar drilling — make it CHALLENGING):\n- The answer to each blank MUST NOT appear verbatim in the passage or in any other question.\n- Each gap tests grammar or vocabulary the student has to KNOW and PRODUCE, not COPY.\n- When the gap is a VERB: put the infinitive/base form in parentheses right after the blank so the learner knows what to conjugate, e.g. (in {$language}) 'Gestern ___ (gehen) ich ins Kino.' → answer 'ging' (correct tense + person).\n- When the gap tests WORD FORM/ENDINGS (e.g. German adjective endings, plurals, declensions): give the base word in parentheses and require the correctly inflected form, e.g. 'Ich sehe einen ___ (groß) Hund.' → 'großen'.\n- The answer must NEVER be identical to the word in parentheses: the student must transform it.\n- Vary tenses/persons/cases across the 3 items so it really tests the rule.\n- Bad: 'Madrid is a ___ city' with 'big' visible earlier. NEVER do this."
            : '';

        // L'EXPLICATION, pour tout ce qui est corrige automatiquement, dans la
        // langue de l'apprenant jusqu'au A2 et dans celle de l'examen ensuite.
        $explanationDirective = '';
        if ($this->exigeUneExplication($componentKey)) {
            $explanationDirective = ExerciseSchemaRegistry::familyFor($componentKey) === ExerciseSchemaRegistry::FAMILY_MULTI
                ? "\n\nEXPLANATIONS — mandatory: every question MUST include \"explanations\", an object with EXACTLY the same keys as correct_answers, giving for EACH blank one short sentence in {$langueExplications} that says why this answer is right."
                : "\n\nEXPLANATIONS — mandatory: every question MUST include \"explanation\", one or two sentences in {$langueExplications} saying why the right answer is right (and, when there are options, why the most tempting wrong one is wrong).";
        }

        $trueFalseDirective = $libellesVraiFaux
            ? "\n\nTRUE/FALSE LABELS: this exam part uses exactly these answers: ".implode(' / ', $libellesVraiFaux).'. '
                .'correct_answer is one of them, copied exactly.'
                .(count($libellesVraiFaux) === 3
                    ? " At least one statement MUST be \"{$libellesVraiFaux[2]}\": the text says nothing about it either way."
                    : ($this->libellesOuiNon($libellesVraiFaux)
                        ? ' This part is answered YES or NO: each item is a question (or a stance, e.g. is this person for or against?) whose answer is clearly yes or no according to the text. There is NO "not given" answer.'
                        : ' There is NO "not given" answer in this part: every statement is clearly right or wrong according to the text.'))
            : '';

        // Comprehension grounding: for reading/listening, EVERY question must be
        // answerable from the passage/recording alone. This stops the recurring
        // problem of questions asking about facts that are not in the text.
        $isComprehension = in_array($skillType, ['reading', 'listening'], true);
        $comprehensionGuard = $isComprehension
            ? "\n\nCRITICAL FOR THIS COMPREHENSION EXERCISE:\n- EVERY question's correct answer MUST be explicitly stated in, or directly inferable from, the passage/recording. NEVER ask about a detail, name, number or fact that is not present in the text.\n- Distractors must be plausible but clearly wrong according to the text.\n- For listening, audio_text IS the transcript that will be read aloud (the student does NOT see it); there is no separate passage — questions must be answerable by ear, so keep them concrete and not dependent on exact spelling.\n- Do NOT require outside/world knowledge the text doesn't provide."
            : '';

        // Level guard: keep vocabulary and structures within the target CEFR level so
        // the learner isn't tested on notions far beyond what they're learning.
        // Ces deux consignes ne concernent que les exercices a choix ou a champs
        // multiples. Envoyees a un expose oral, elles tiraient Mistral vers un QCM :
        // pour « speaking-long-turn » il repondait « Sie horen gleich einen Text »,
        // et le validateur refusait l'integralite de la fournee — donc aucun exercice
        // oral ne pouvait entrer dans le vivier. On ne parle d'options qu'a qui en a.
        $consignesDeCorrection = $this->consignesDeCorrection($componentKey);

        // LA PARTIE DE L'EPREUVE, quand l'apprenant s'y entraine nommement.
        // Plusieurs parties d'un meme module partagent souvent le meme format
        // de reponse — au telc, les trois parties de Horverstehen sont toutes
        // en vrai/faux — et different par leur support et leur nombre d'items.
        // Sans cette directive, « Teil 2 » rendait un exercice indistinguable
        // de « Teil 1 ».
        $directiveDeLaPartie = $this->directiveDeLaPartie($partieExamen, $exam);

        // CE QUI RESISTE A CET APPRENANT. Le generateur savait l'examen, le
        // niveau et la partie — jamais ce que la personne ratait. Un exercice
        // ecrit pour quelqu'un qui bute sur les accords doit porter sur les
        // accords, sinon il ne lui sert a rien de plus qu'un exercice au hasard.
        $directiveFaiblesse = $faiblesse
            ? "

TARGET THIS WEAKNESS: the learner keeps getting «{$faiblesse}» wrong. "
                .'Build the exercise so that answering it correctly REQUIRES handling that point. '
                .'Do not mention the weakness in the wording — just make it the difficulty.'
            : '';

        // CE QUE L'ENSEIGNANT DEMANDE. Un centre prepare ses eleves a une
        // situation precise (« la sante », « le travail en Allemagne ») : sans
        // theme, l'exercice tombait sur un sujet au hasard. Ses consignes sont
        // celles d'un professeur, jamais un ordre qui leverait les regles du
        // format, du niveau ou du JSON : on le dit au modele.
        $demandeDuCentre = self::demandeDuCentre($demandeDuCentre);
        $directiveDuCentre = '';
        if ($demandeDuCentre['theme'] !== null) {
            $directiveDuCentre .= "\n\nTOPIC REQUESTED BY THE TEACHER: «{$demandeDuCentre['theme']}». "
                .'Every text, situation and question must be about this topic.';
        }
        if ($demandeDuCentre['consignes'] !== null) {
            $directiveDuCentre .= "\n\nTEACHER'S NOTES — follow them unless they contradict the format, the level or the JSON structure required here: «{$demandeDuCentre['consignes']}»";
        }

        $levelGuard = "\n\nLEVEL: Keep vocabulary, grammar and topics appropriate for CEFR {$difficulty}. Do not use structures or rare vocabulary clearly above {$difficulty}."
            // La difficulte a l'interieur du niveau, selon les derniers resultats
            // de l'apprenant (voir PalierDeDifficulte).
            .($palier !== null ? \App\Services\Content\PalierDeDifficulte::consigne($palier, $difficulty) : '');

        // LE NOMBRE D'ITEMS DE L'EPREUVE REMPLACE CELUI DU GABARIT.
        //
        // Chaque format portait un compte en dur dans sa description — « Array
        // of 3 questions » pour un QCM, « Array of 4 » pour une association —
        // et c'est le gabarit que le modele suivait, pas la consigne de partie
        // qui, elle, annoncait le bon chiffre un peu plus haut. Entre les deux,
        // le schema gagne toujours. Resultat : un Lesen Teil 2 du Goethe B1,
        // six questions a l'examen, en comptait trois chez nous.
        // Un item n'est pas toujours une question. Pour les formats a champs
        // multiples — completer des notes, un formulaire, un tableau — les
        // items sont les TROUS d'une seule question : y ecrire « 5 questions »
        // fabriquerait cinq exercices miniatures au lieu d'une grille a cinq
        // trous. On le dit donc dans la langue de chaque famille.
        $items = (int) ($partieExamen['items'] ?? 0);
        $famille = ExerciseSchemaRegistry::familyFor($componentKey);
        $directiveDesItems = '';

        if ($items > 0 && $famille === ExerciseSchemaRegistry::FAMILY_EXACT) {
            $remplace = preg_replace('/^Array of \d+(\s*-\s*\d+)?/u',
                'Array of EXACTLY '.$items, $questionFormat, 1, $compte);
            if ($compte > 0) {
                $questionFormat = $remplace;
            }
            $directiveDesItems = "\n\nITEM COUNT: produce EXACTLY {$items} questions — no more, no fewer. "
                .'The real exam part has that many, and an exercise with a different count is not that part.';
        } elseif ($items > 0 && $famille === ExerciseSchemaRegistry::FAMILY_MULTI) {
            $directiveDesItems = "\n\nITEM COUNT: the blanks the student must fill MUST number EXACTLY {$items} "
                .'in total across the whole exercise — that is how many the real exam part has. '
                .'Count them before answering.';
        } elseif ($items > 0 && $famille === ExerciseSchemaRegistry::FAMILY_ORDER) {
            $directiveDesItems = "\n\nITEM COUNT: EXACTLY {$items} elements to place or reorder, as the real exam part has.";
        }

        return <<<PROMPT
Generate a {$exerciseType->name} exercise for the {$exam->name} exam at CEFR {$difficulty} level.
The exercise must be entirely in {$language} ({$languageNative}) — this is a {$language} language exam.
Skill tested: {$skillType}.{$directiveDeLaPartie}{$directiveFaiblesse}{$directiveDuCentre}{$directiveDesPoints}{$lessonDirective}{$grammarDirective}{$gapFillBan}{$comprehensionGuard}{$levelGuard}{$listeningDirective}{$speakingDirective}{$instructionDirective}{$trueFalseDirective}{$explanationDirective}{$directiveDesItems}

Return JSON with exactly this structure:
{
  {$contentStructure},
  "questions": [{$questionFormat}]
}

Important: ALL text (passage, questions, options) must be in {$language}, EXCEPT where a field above is explicitly marked to be in the student's native language (beginner speaking/instruction fields). Explanations are written in {$langueExplications}. No English unless it's an English exam or English is the explanation language.
For writing/speaking exercises, correct_answer should be null (these are scored differently).
{$consignesDeCorrection}
PROMPT;
    }



    /**
     * Remet la bonne etiquette aux questions dont la STRUCTURE est celle du type
     * demande.
     *
     * Mistral ecrivait un jeu de role parfaitement forme — scenario, role,
     * dialogue_turns — puis l'etiquetait « multiple_choice ». Le validateur ne
     * regardait que l'etiquette et jetait tout : les debats oraux du telc B1
     * echouaient a chaque essai alors que leur contenu etait bon.
     *
     * L'etiquette ne sert a rien pour l'affichage, c'est le component_key qui
     * decide. On ne corrige que ce qui est sans ambiguite ; une vraie reponse a
     * cote — un QCM de lecture pour un expose oral — n'a pas la structure
     * attendue et reste refusee.
     */
    private function rectifierLeType(array $questions, ?string $componentKey): array
    {
        $formes = [
            'role-play' => ['exiges' => ['scenario', 'role', 'dialogue_turns'], 'aRetirer' => ['options']],
            'speaking-recorder' => ['exiges' => ['text'], 'interdits' => ['options'], 'aRetirer' => []],
        ];

        if (! isset($formes[$componentKey])) {
            return $questions;
        }
        $forme = $formes[$componentKey];

        return array_map(function ($question) use ($componentKey, $forme) {
            if (! is_array($question) || ($question['type'] ?? null) === $componentKey) {
                return $question;
            }

            foreach ($forme['exiges'] as $champ) {
                if (! isset($question[$champ]) || $question[$champ] === '' || $question[$champ] === []) {
                    return $question;   // structure absente : on ne corrige rien
                }
            }
            foreach ($forme['interdits'] ?? [] as $champ) {
                if (isset($question[$champ])) {
                    return $question;   // un champ qui n'a rien a faire la : reponse a cote
                }
            }

            // Un champ surnumeraire que le composant n'affiche pas ferait echouer
            // la validation des questions a choix : il n'a pas lieu d'etre garde.
            foreach ($forme['aRetirer'] as $champ) {
                unset($question[$champ]);
            }

            $question['type'] = $componentKey;

            return $question;
        }, $questions);
    }

    /**
     * La consigne qui dit a l'IA quelle partie de l'epreuve reproduire.
     *
     * Vide quand l'apprenant ne s'entraine pas a une partie nommee : le prompt
     * reste alors celui d'un exercice de format libre.
     *
     * @param  array{name?: string, task?: string, items?: int|null}|null  $partie
     */
    private function directiveDeLaPartie(?array $partie, Exam $exam): string
    {
        if (! $partie || empty($partie['name'])) {
            return '';
        }

        $nom = $partie['name'];
        $tache = trim((string) ($partie['task'] ?? ''));
        $lignes = ["\n\nEXAM PART — reproduce «{$nom}» of the {$exam->name}, exactly as it falls in the real exam."];

        if ($tache !== '') {
            $lignes[] = "What the candidate must do: {$tache}";
        }

        if (! empty($partie['items']) && (int) $partie['items'] > 1) {
            $lignes[] = 'Produce exactly '.(int) $partie['items'].' items, as the real part does.';
        }

        $lignes[] = 'Other parts of the same section use the same answer format but a DIFFERENT '
            .'kind of input text; keep this one faithful to the part described above.';

        return implode("\n- ", $lignes);
    }

    /**
     * Les consignes de correction propres aux exercices a choix et a champs
     * multiples — vides pour les autres.
     *
     * Un expose oral n'a ni option ni grille de reponses : lui rappeler que
     * « correct_answer doit etre la lettre de l'option » l'invitait a en
     * inventer, et c'est un QCM d'ecoute qui revenait.
     */
    private function consignesDeCorrection(?string $componentKey): string
    {
        $aChoix = ['mcq', 'gap-fill', 'sentence-completion', 'matching', 'matching-headings',
            'true-false-notgiven', 'grammar-mcq', 'mcq-cloze', 'listen-choose-response',
            'insert-text', 'gapped-text'];
        $aChamps = ['summary-completion', 'note-completion', 'form-completion', 'table-completion',
            'flow-chart-completion', 'diagram-labeling', 'fill-table', 'open-cloze',
            'word-formation', 'complete-the-words', 'prose-summary'];

        $consignes = '';

        if (in_array($componentKey, $aChoix, true)) {
            $consignes .= PHP_EOL.PHP_EOL.<<<'TEXTE'
CRITICAL FOR ANY QUESTION WITH "options": correct_answer MUST be the letter of
the option that is WORD-FOR-WORD identical to one of the strings in "options".
Before answering, re-read your own "options" array and pick the letter that
matches EXACTLY — do not answer with a word that isn't literally one of the
options (e.g. do not explain "Morgen" if "Morgen" isn't in the options list).
The "explanation" must refer only to the text of the actual chosen option.
TEXTE;
        }

        if (in_array($componentKey, $aChamps, true)) {
            $consignes .= PHP_EOL.PHP_EOL.<<<'TEXTE'
CRITICAL FOR MULTI-FIELD TYPES (correct_answers is a key-value map): the keys
of correct_answers MUST exactly match the blank positions declared in the
structural field (notes/fields/steps/rows/labels) — do not invent extra keys
or omit any blank. FOR SUMMARY-COMPLETION SPECIFICALLY: every value in
correct_answers MUST be a word/phrase that appears WORD-FOR-WORD in word_list
— re-check that each correct answer is literally present in your own
word_list array before answering, since the student can only pick from it.
TEXTE;
        }

        return $consignes;
    }

    // (getFallbackContent / getFallbackQuestions supprimés : ils créaient des
    //  exercices placeholder "What is this exercise about?" montrés aux apprenants.
    //  Le générateur throw désormais en cas d'échec IA — pas de junk persisté.)

    private function calculateXpReward(string $difficulty): int
    {
        return match ($difficulty) {
            'A1' => 5,
            'A2' => 8,
            'B1' => 10,
            'B2' => 15,
            'C1' => 20,
            'C2' => 25,
            default => 10,
        };
    }
}
