<?php

namespace App\Services\Content;

use App\Models\Lesson;

class IntroductionLesson
{
    /** Upgrade the local preview's one-question lesson without changing progress. */
    public function strengthen(Lesson $lesson): void
    {
        if ($lesson->concept !== 'qa.introductions' || count($lesson->comprehension_quiz ?? []) >= 4) {
            return;
        }
        $lesson->update([
            'key_takeaways' => ['Prénom : My name is…', 'Origine : I am from…', 'Activité : I am a student. Avec I, utilise am.'],
            'theory_markdown' => "## Donner son prénom\nPour te présenter, dis **My name is Sam.** (Je m’appelle Sam.) Tu peux aussi dire **I am Sam.** (Je suis Sam.) L’ordre est : sujet, verbe, information.\n\n## Dire d’où tu viens\n**I am from Cameroon.** signifie « Je viens du Cameroun ». Avec I, utilise **am**. Avec she ou he, utilise **is** : **She is from France.** (Elle vient de France.)\n\n## Parler de ton activité\n**I am a student.** signifie « Je suis étudiant ». Garde le petit mot **a** devant student. **He is a teacher.** signifie « Il est enseignant ».\n\n## Relier tes idées\n**My name is Sam. I am from Cameroon. I am a student.** Tu peux maintenant associer trois informations : prénom, pays et activité. Relis la première phrase, puis essaie de la reconstruire sans la regarder.",
            'comprehension_quiz' => [
                ['type' => 'mcq', 'question' => 'Quelle phrase présente correctement Sam ?', 'options' => ['My name are Sam.', 'My name is Sam.', 'My name am Sam.', 'My is name Sam.'], 'correct_answer' => 'My name is Sam.', 'explanation' => 'Le sujet est « my name », puis vient « is », puis le prénom.'],
                ['type' => 'recall', 'question' => 'Complète avec le verbe : I ___ from Cameroon.', 'options' => [], 'correct_answer' => 'am', 'explanation' => 'Avec le sujet I, be devient am : I am from Cameroon.'],
                ['type' => 'sentence-order', 'question' => 'Reconstruis la phrase qui indique le prénom de Sam.', 'words' => ['Sam', 'name', 'My', 'is'], 'options' => [], 'correct_answer' => 'My name is Sam.', 'explanation' => 'On commence par le sujet My name, puis le verbe is, puis Sam.'],
                ['type' => 'recall', 'question' => 'Corrige cette phrase : I is a student.', 'options' => [], 'correct_answer' => 'I am a student.', 'accepted_answers' => ["I'm a student."], 'explanation' => 'I demande am, pas is. La contraction I’m est également correcte.'],
            ],
        ]);
    }
}
