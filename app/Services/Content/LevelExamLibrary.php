<?php

namespace App\Services\Content;

use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\LearningPathNode;

/** Reviewed general English A1 checkpoint, available without an AI request. */
class LevelExamLibrary
{
    public function ensure(LearningPathNode $node, ExerciseType $type, int $order): ?Exercise
    {
        if ($node->level !== 'A1' || $node->exam->language->slug !== 'english') {
            return null;
        }
        $sets = [
            'mcq' => [
                ['Choose the correct introduction.', ['My name is Lina.', 'My name are Lina.', 'My name am Lina.', 'My name be Lina.'], 'A', 'Avec « my name », on emploie « is ».', 'introductions'],
                ['“Where do you live?” Choose the answer.', ['I am twenty.', 'I live in Douala.', 'I work at nine.', 'I like tea.'], 'B', '« Where » demande un lieu. « I live in… » indique où tu habites.', 'questions'],
                ['There are two ___ on the table.', ['book', 'a book', 'books', 'booking'], 'C', 'Après « two », le nom est au pluriel : books.', 'plurals'],
                ['What time is half past seven?', ['7:15', '6:30', '7:00', '7:30'], 'D', '« Half past » signifie trente minutes après l’heure.', 'time'],
                ['Anna is my sister. ___ is a teacher.', ['He', 'She', 'They', 'We'], 'B', 'Pour parler d’Anna, on utilise « she ».', 'pronouns'],
            ],
            'gap-fill' => [
                ['I ___ from Cameroon. (be)', 'am', 'Avec I : I am.', 'be'],
                ['She ___ English every day. (study)', 'studies', 'Avec she, study devient studies au présent.', 'present-simple'],
                ['We ___ not have a car. (do)', 'do', 'Au présent : we do not have.', 'negation'],
                ['___ you like coffee? (do)', 'Do', 'Une question au présent commence par Do avec you.', 'questions'],
                ['This is ___ apple. (a / an)', 'an', 'Devant le son voyelle de apple : an apple.', 'articles'],
            ],
            'sentence-completion' => [
                ['Complete: My brother ___ in a hospital.', ['work', 'works', 'working', 'are work'], 'B', 'My brother = he : on ajoute -s au verbe.', 'present-simple'],
                ['Complete: I can ___ French.', ['speaks', 'speaking', 'speak', 'to speak'], 'C', 'Après can, on utilise la base verbale speak.', 'can'],
                ['Complete: The keys are ___ the table. (sur)', ['under', 'between', 'behind', 'on'], 'D', 'On signifie sur.', 'prepositions'],
                ['Complete: This is Sara. It is ___ bag.', ['her', 'his', 'their', 'our'], 'A', 'Pour le sac de Sara : her bag.', 'possessives'],
                ['Complete: We go to school ___ Monday.', ['at', 'in', 'on', 'to'], 'C', 'Devant un jour de la semaine : on Monday.', 'time'],
            ],
        ];
        if (!isset($sets[$type->component_key])) {
            return null;
        }
        $questions = [];
        foreach ($sets[$type->component_key] as $index => $row) {
            $gap = $type->component_key === 'gap-fill';
            $questions[] = array_filter([
                'id' => "a1-{$order}-{$index}", 'type' => $type->component_key,
                'text' => $row[0], 'options' => $gap ? null : $row[1],
                'correct_answer' => $row[$gap ? 1 : 2],
                'explanation' => $row[$gap ? 2 : 3], 'concept' => $row[$gap ? 3 : 4],
            ], fn ($value) => $value !== null);
        }
        [$valid, $error, $questions] = ExerciseSchemaRegistry::validateQuestions($type->component_key, $questions);
        if (!$valid) {
            throw new \LogicException($error);
        }
        return Exercise::firstOrCreate(['node_id' => $node->id, 'order_in_node' => $order], [
            'exam_id' => $node->exam_id, 'exercise_type_id' => $type->id,
            'exam_section_id' => $type->section_id, 'difficulty' => 'A1',
            'is_ai_generated' => false, 'xp_reward' => 20,
            'content' => ['title' => 'Bilan anglais A1', 'instructions' => 'Mobilise tes acquis : présent, phrases du quotidien et vocabulaire. Complète les trois parties.', 'source' => 'reviewed-a1-checkpoint'],
            'questions' => $questions,
        ]);
    }
}
