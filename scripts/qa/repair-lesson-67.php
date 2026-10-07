<?php
require getcwd().'/vendor/autoload.php';
$app = require getcwd().'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
Illuminate\Support\Facades\DB::transaction(function () {
    $lesson = App\Models\Lesson::lockForUpdate()->findOrFail(67);
    if ($lesson->concept !== 'grammar.tense.past_simple.past_continuous') throw new RuntimeException('Unexpected lesson.');
    $backup = 'qa-backups/lesson-67-'.now()->format('Ymd-His').'.json';
    if (! Illuminate\Support\Facades\Storage::disk('local')->put($backup, $lesson->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) throw new RuntimeException('Backup failed.');
    $lesson->update([
        'title' => 'Raconter une action en cours et un événement passé',
        'theory_markdown' => <<<'MD'
## 1. Situer les actions dans le passé
Le **Past Simple** présente un événement passé comme un tout : *The phone rang.* (Le téléphone a sonné.) Le **Past Continuous** montre une action en cours à un moment passé : *She was cooking dinner.* (Elle préparait le dîner.)

La durée seule ne décide pas du temps : *She worked all day yesterday.* (Elle a travaillé toute la journée hier.) décrit aussi une action longue, présentée comme terminée.

## 2. Construire les deux formes
Au Past Simple, un verbe régulier prend généralement **-ed** : *She finished her homework.* (Elle a terminé ses devoirs.) Les verbes irréguliers ont une forme à apprendre : **ring → rang**, **see → saw**, **know → knew**.

Le Past Continuous se construit avec **was / were + verbe en -ing** : *I was driving.* (Je conduisais.) Avec you, we et they, on utilise **were** : *They were playing.* (Ils jouaient.) Il faut un sujet : **She was cooking**, pas simplement **was cooking** dans une phrase indépendante.

## 3. Relier les événements avec when et while
Pour montrer un événement qui survient pendant une activité, on peut écrire : *She was cooking dinner when the phone rang.* (Elle préparait le dîner quand le téléphone a sonné.) La sonnerie survient pendant la préparation ; cela ne signifie pas forcément qu'elle arrête de cuisiner.

**While** introduit souvent une activité en cours : *While I was driving to work, I saw an accident.* (Pendant que je conduisais vers mon travail, j'ai vu un accident.) Deux activités peuvent aussi se dérouler en parallèle : *She was cooking while he was reading.* (Elle cuisinait pendant qu'il lisait.)

Ne transforme pas ces exemples en règle absolue : **when** et **while** ne déterminent pas automatiquement le temps. Le choix dépend du sens et du contexte.

## 4. Vérifier puis récapituler
Pour un état comme **know**, on emploie normalement la forme simple dans ce contexte : *She knew the answer.* (Elle connaissait la réponse.) **She was knew** est incorrect : was ne se combine pas avec la forme knew.

Avant de répondre, vérifie : quel est le sujet ? Quelle action est présentée en cours ? Quel événement survient ? Ai-je écrit **was/were + -ing** pour l'action en cours ?
MD,
        'key_takeaways' => ['Past Simple : événement présenté comme un tout.', 'Past Continuous : was/were + -ing pour une action en cours.', 'Le contexte guide le choix ; when et while ne sont pas des règles automatiques.'],
        'comprehension_quiz' => [
            ['type' => 'mcq', 'question' => 'Tu étais en train de réviser quand ton ami a appelé. Quelle phrase présente la révision comme déjà en cours ?', 'options' => ['I was studying when my friend called me.', 'I started studying after my friend called me.', 'I had finished studying before my friend called me.', 'I will study when my friend calls me.'], 'correct_answer' => 'I was studying when my friend called me.', 'explanation' => 'Was studying décrit l’activité en cours ; called situe l’appel qui survient pendant cette activité.'],
            ['type' => 'recall', 'question' => 'Complète avec la forme passée en cours de drive : While I ___ to work, I saw an accident.', 'options' => [], 'correct_answer' => 'was driving', 'explanation' => 'Avec I : was + driving. Le trajet était en cours au moment où la personne a vu l’accident.'],
            ['type' => 'sentence-order', 'question' => 'Construis la phrase : elle préparait le dîner quand le téléphone a sonné.', 'options' => [], 'words' => ['phone', 'She', 'when', 'cooking', 'rang', 'the', 'was', 'dinner'], 'correct_answer' => 'She was cooking dinner when the phone rang.', 'explanation' => 'She est le sujet ; was cooking décrit la préparation en cours ; rang décrit la sonnerie.'],
            ['type' => 'recall', 'question' => 'Réécris toute la phrase correctement : She was knew the answer to the question.', 'options' => [], 'correct_answer' => 'She knew the answer to the question.', 'explanation' => 'Knew est déjà la forme passée de know. Ici, on décrit un état : supprime was.'],
        ],
    ]);
    if (! app(App\Services\Content\LessonQuizQuality::class)->valid($lesson->comprehension_quiz)) throw new RuntimeException('Repair failed quality check.');
    echo "Lesson 67 repaired; backup {$backup}; attempts and progression unchanged.\n";
});
