<?php

namespace Database\Seeders\Syllabus;

use App\Models\Exam;
use App\Models\LearningPathNode;
use Illuminate\Database\Seeder;

class EnglishSyllabusSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedIelts();
        $this->seedToefl();
        $this->seedCambridge();
    }

    private function seedIelts(): void
    {
        $exam = Exam::where('slug', 'ielts')->first();

        if (! $exam) {
            return;
        }

        LearningPathNode::where('exam_id', $exam->id)->delete();

        // Chapter 1: Listening Fundamentals (5 nodes)
        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Listening Fundamentals',
            'chapter_order' => 1,
            'sort_order' => 1,
            'title' => 'Understanding everyday conversations',
            'description' => 'Learn to follow and understand common everyday dialogues and conversations in various social contexts.',
            'icon' => 'headphones',
            'skill_type' => 'listening',
            'level' => 'A2',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Listening Fundamentals',
            'chapter_order' => 1,
            'sort_order' => 2,
            'title' => 'Note and form completion strategies',
            'description' => 'Master strategies for completing notes and forms while listening to recorded conversations.',
            'icon' => 'pen',
            'skill_type' => 'listening',
            'level' => 'B1',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Listening Fundamentals',
            'chapter_order' => 1,
            'sort_order' => 3,
            'title' => 'Following academic lectures',
            'description' => 'Develop skills to follow and understand academic lectures on various topics.',
            'icon' => 'brain',
            'skill_type' => 'listening',
            'level' => 'B1',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Listening Fundamentals',
            'chapter_order' => 1,
            'sort_order' => 4,
            'title' => 'Map and diagram labeling skills',
            'description' => 'Practice labeling maps and diagrams based on audio descriptions and directions.',
            'icon' => 'globe',
            'skill_type' => 'listening',
            'level' => 'B1',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Listening Fundamentals',
            'chapter_order' => 1,
            'sort_order' => 5,
            'title' => 'Listening checkpoint',
            'description' => 'Test your listening fundamentals with a comprehensive practice session.',
            'icon' => 'target',
            'skill_type' => 'listening',
            'level' => 'B1',
            'node_type' => 'practice',
            'xp_reward' => 75,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        // Chapter 2: Reading Strategies (6 nodes)
        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Reading Strategies',
            'chapter_order' => 2,
            'sort_order' => 1,
            'title' => 'Skimming and scanning techniques',
            'description' => 'Learn effective skimming and scanning techniques to quickly locate key information in texts.',
            'icon' => 'book',
            'skill_type' => 'reading',
            'level' => 'B1',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Reading Strategies',
            'chapter_order' => 2,
            'sort_order' => 2,
            'title' => 'True/False/Not Given mastery',
            'description' => 'Master the art of distinguishing between True, False, and Not Given answers in IELTS reading.',
            'icon' => 'target',
            'skill_type' => 'reading',
            'level' => 'B1',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Reading Strategies',
            'chapter_order' => 2,
            'sort_order' => 3,
            'title' => 'Yes/No/Not Given vs True/False',
            'description' => 'Understand the crucial differences between Yes/No/Not Given and True/False/Not Given question types.',
            'icon' => 'brain',
            'skill_type' => 'reading',
            'level' => 'B1',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Reading Strategies',
            'chapter_order' => 2,
            'sort_order' => 4,
            'title' => 'Matching headings and information',
            'description' => 'Develop skills for matching headings to paragraphs and information to sections.',
            'icon' => 'book',
            'skill_type' => 'reading',
            'level' => 'B2',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Reading Strategies',
            'chapter_order' => 2,
            'sort_order' => 5,
            'title' => 'Summary and sentence completion',
            'description' => 'Practice completing summaries and sentences using information from reading passages.',
            'icon' => 'pen',
            'skill_type' => 'reading',
            'level' => 'B2',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Reading Strategies',
            'chapter_order' => 2,
            'sort_order' => 6,
            'title' => 'Reading checkpoint',
            'description' => 'Test your reading strategies with a comprehensive practice session.',
            'icon' => 'trophy',
            'skill_type' => 'reading',
            'level' => 'B2',
            'node_type' => 'practice',
            'xp_reward' => 75,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        // Chapter 3: Writing Task 1 - Data Description (5 nodes)
        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Writing Task 1 - Data Description',
            'chapter_order' => 3,
            'sort_order' => 1,
            'title' => 'Describing line graphs and trends',
            'description' => 'Learn to accurately describe trends, changes, and patterns in line graphs.',
            'icon' => 'pen',
            'skill_type' => 'writing',
            'level' => 'B1',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Writing Task 1 - Data Description',
            'chapter_order' => 3,
            'sort_order' => 2,
            'title' => 'Comparing bar charts and tables',
            'description' => 'Master techniques for comparing and contrasting data presented in bar charts and tables.',
            'icon' => 'pen',
            'skill_type' => 'writing',
            'level' => 'B2',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Writing Task 1 - Data Description',
            'chapter_order' => 3,
            'sort_order' => 3,
            'title' => 'Process diagrams and maps',
            'description' => 'Develop skills to describe processes, cycles, and map changes in writing.',
            'icon' => 'globe',
            'skill_type' => 'writing',
            'level' => 'B2',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Writing Task 1 - Data Description',
            'chapter_order' => 3,
            'sort_order' => 4,
            'title' => 'Advanced data description vocabulary',
            'description' => 'Build a rich vocabulary for describing data, including precise verbs and adverbs for trends.',
            'icon' => 'book',
            'skill_type' => 'writing',
            'level' => 'B2',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Writing Task 1 - Data Description',
            'chapter_order' => 3,
            'sort_order' => 5,
            'title' => 'Task 1 timed practice',
            'description' => 'Practice writing Task 1 responses under timed exam conditions.',
            'icon' => 'zap',
            'skill_type' => 'writing',
            'level' => 'B2',
            'node_type' => 'practice',
            'xp_reward' => 75,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        // Chapter 4: Writing Task 2 - Essays (5 nodes)
        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Writing Task 2 - Essays',
            'chapter_order' => 4,
            'sort_order' => 1,
            'title' => 'Essay structure and thesis statements',
            'description' => 'Learn to build well-organized essays with clear thesis statements and logical structure.',
            'icon' => 'pen',
            'skill_type' => 'writing',
            'level' => 'B2',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Writing Task 2 - Essays',
            'chapter_order' => 4,
            'sort_order' => 2,
            'title' => 'Opinion essays (agree/disagree)',
            'description' => 'Master the art of writing persuasive opinion essays with strong arguments.',
            'icon' => 'pen',
            'skill_type' => 'writing',
            'level' => 'B2',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Writing Task 2 - Essays',
            'chapter_order' => 4,
            'sort_order' => 3,
            'title' => 'Discussion and two-part essays',
            'description' => 'Develop skills for discussing multiple viewpoints and answering two-part questions.',
            'icon' => 'brain',
            'skill_type' => 'writing',
            'level' => 'C1',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Writing Task 2 - Essays',
            'chapter_order' => 4,
            'sort_order' => 4,
            'title' => 'Problem-solution essays',
            'description' => 'Learn to identify problems and propose effective solutions in essay format.',
            'icon' => 'target',
            'skill_type' => 'writing',
            'level' => 'C1',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Writing Task 2 - Essays',
            'chapter_order' => 4,
            'sort_order' => 5,
            'title' => 'Task 2 timed practice',
            'description' => 'Practice writing Task 2 essays under timed exam conditions.',
            'icon' => 'zap',
            'skill_type' => 'writing',
            'level' => 'C1',
            'node_type' => 'practice',
            'xp_reward' => 75,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        // Chapter 5: Speaking Skills (5 nodes)
        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Speaking Skills',
            'chapter_order' => 5,
            'sort_order' => 1,
            'title' => 'Part 1: Fluent personal responses',
            'description' => 'Develop fluency and confidence when answering personal questions in Part 1 of the speaking test.',
            'icon' => 'mic',
            'skill_type' => 'speaking',
            'level' => 'B1',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Speaking Skills',
            'chapter_order' => 5,
            'sort_order' => 2,
            'title' => 'Part 2: Cue card long turn technique',
            'description' => 'Master the long turn technique for speaking fluently for 1-2 minutes on a given topic.',
            'icon' => 'mic',
            'skill_type' => 'speaking',
            'level' => 'B2',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Speaking Skills',
            'chapter_order' => 5,
            'sort_order' => 3,
            'title' => 'Part 3: Abstract discussion skills',
            'description' => 'Build skills for engaging in abstract discussions and expressing complex ideas.',
            'icon' => 'brain',
            'skill_type' => 'speaking',
            'level' => 'B2',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Speaking Skills',
            'chapter_order' => 5,
            'sort_order' => 4,
            'title' => 'Pronunciation and intonation',
            'description' => 'Improve your pronunciation, stress patterns, and intonation for higher speaking scores.',
            'icon' => 'star',
            'skill_type' => 'speaking',
            'level' => 'B2',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Speaking Skills',
            'chapter_order' => 5,
            'sort_order' => 5,
            'title' => 'Speaking mock test',
            'description' => 'Complete a full speaking mock test covering all three parts.',
            'icon' => 'trophy',
            'skill_type' => 'speaking',
            'level' => 'B2',
            'node_type' => 'practice',
            'xp_reward' => 75,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        // Chapter 6: Vocabulary & Grammar (5 nodes)
        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Vocabulary & Grammar',
            'chapter_order' => 6,
            'sort_order' => 1,
            'title' => 'Academic Word List - core 100 words',
            'description' => 'Learn the 100 most important words from the Academic Word List for IELTS success.',
            'icon' => 'book',
            'skill_type' => 'reading',
            'level' => 'B1',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Vocabulary & Grammar',
            'chapter_order' => 6,
            'sort_order' => 2,
            'title' => 'Collocations and idiomatic expressions',
            'description' => 'Master common collocations and idiomatic expressions used in academic and everyday English.',
            'icon' => 'star',
            'skill_type' => 'reading',
            'level' => 'B2',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Vocabulary & Grammar',
            'chapter_order' => 6,
            'sort_order' => 3,
            'title' => 'Complex sentence structures',
            'description' => 'Learn to construct complex and compound-complex sentences for higher writing scores.',
            'icon' => 'pen',
            'skill_type' => 'writing',
            'level' => 'B2',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Vocabulary & Grammar',
            'chapter_order' => 6,
            'sort_order' => 4,
            'title' => 'Hedging and formal register',
            'description' => 'Master hedging language and formal register for academic writing contexts.',
            'icon' => 'brain',
            'skill_type' => 'writing',
            'level' => 'C1',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Vocabulary & Grammar',
            'chapter_order' => 6,
            'sort_order' => 5,
            'title' => 'Vocabulary review test',
            'description' => 'Comprehensive review test covering all vocabulary and grammar topics.',
            'icon' => 'trophy',
            'skill_type' => 'writing',
            'level' => 'C1',
            'node_type' => 'practice',
            'xp_reward' => 75,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        // Chapter 7: Advanced Techniques (5 nodes)
        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Advanced Techniques',
            'chapter_order' => 7,
            'sort_order' => 1,
            'title' => 'Speed reading for IELTS',
            'description' => 'Develop advanced speed reading skills to handle IELTS reading passages more efficiently.',
            'icon' => 'zap',
            'skill_type' => 'reading',
            'level' => 'C1',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Advanced Techniques',
            'chapter_order' => 7,
            'sort_order' => 2,
            'title' => 'Note-taking under time pressure',
            'description' => 'Master efficient note-taking techniques for the listening section under exam conditions.',
            'icon' => 'headphones',
            'skill_type' => 'listening',
            'level' => 'C1',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Advanced Techniques',
            'chapter_order' => 7,
            'sort_order' => 3,
            'title' => 'Paraphrasing mastery',
            'description' => 'Learn advanced paraphrasing techniques essential for high scores in writing and speaking.',
            'icon' => 'pen',
            'skill_type' => 'writing',
            'level' => 'C1',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Advanced Techniques',
            'chapter_order' => 7,
            'sort_order' => 4,
            'title' => 'Cohesion devices in writing',
            'description' => 'Master the use of cohesion devices and linking words for well-connected writing.',
            'icon' => 'pen',
            'skill_type' => 'writing',
            'level' => 'C1',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Advanced Techniques',
            'chapter_order' => 7,
            'sort_order' => 5,
            'title' => 'Advanced skills test',
            'description' => 'Comprehensive test of all advanced techniques covered in this chapter.',
            'icon' => 'trophy',
            'skill_type' => 'writing',
            'level' => 'C1',
            'node_type' => 'practice',
            'xp_reward' => 75,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        // Chapter 8: Exam Strategy (4 nodes)
        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Exam Strategy',
            'chapter_order' => 8,
            'sort_order' => 1,
            'title' => 'Time management across all sections',
            'description' => 'Learn optimal time allocation strategies for each section of the IELTS exam.',
            'icon' => 'zap',
            'skill_type' => 'mixed',
            'level' => 'C1',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Exam Strategy',
            'chapter_order' => 8,
            'sort_order' => 2,
            'title' => 'Common mistakes and how to avoid them',
            'description' => 'Identify the most common IELTS mistakes and learn proven strategies to avoid them.',
            'icon' => 'target',
            'skill_type' => 'mixed',
            'level' => 'C1',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Exam Strategy',
            'chapter_order' => 8,
            'sort_order' => 3,
            'title' => 'Dealing with unknown vocabulary',
            'description' => 'Develop strategies for handling unfamiliar words in reading passages without losing time.',
            'icon' => 'book',
            'skill_type' => 'reading',
            'level' => 'C1',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Exam Strategy',
            'chapter_order' => 8,
            'sort_order' => 4,
            'title' => 'Exam day preparation',
            'description' => 'Everything you need to know to be fully prepared and confident on exam day.',
            'icon' => 'star',
            'skill_type' => 'mixed',
            'level' => 'C1',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        // Chapter 9: Mock Exams (10 nodes)
        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Mock Exams',
            'chapter_order' => 9,
            'sort_order' => 1,
            'title' => 'Mock: Listening Section',
            'description' => 'Complete a full IELTS listening mock test with all four sections.',
            'icon' => 'headphones',
            'skill_type' => 'listening',
            'level' => 'B2',
            'node_type' => 'practice',
            'xp_reward' => 75,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Mock Exams',
            'chapter_order' => 9,
            'sort_order' => 2,
            'title' => 'Mock: Reading Section',
            'description' => 'Complete a full IELTS reading mock test with three passages.',
            'icon' => 'book',
            'skill_type' => 'reading',
            'level' => 'B2',
            'node_type' => 'practice',
            'xp_reward' => 75,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Mock Exams',
            'chapter_order' => 9,
            'sort_order' => 3,
            'title' => 'Mock: Writing Task 1',
            'description' => 'Practice a full Writing Task 1 under timed conditions.',
            'icon' => 'pen',
            'skill_type' => 'writing',
            'level' => 'B2',
            'node_type' => 'practice',
            'xp_reward' => 75,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Mock Exams',
            'chapter_order' => 9,
            'sort_order' => 4,
            'title' => 'Mock: Writing Task 2',
            'description' => 'Practice a full Writing Task 2 essay under timed conditions.',
            'icon' => 'pen',
            'skill_type' => 'writing',
            'level' => 'C1',
            'node_type' => 'practice',
            'xp_reward' => 75,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Mock Exams',
            'chapter_order' => 9,
            'sort_order' => 5,
            'title' => 'Mock: Speaking Full Test',
            'description' => 'Complete a full speaking mock test covering all three parts.',
            'icon' => 'mic',
            'skill_type' => 'speaking',
            'level' => 'B2',
            'node_type' => 'practice',
            'xp_reward' => 75,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Mock Exams',
            'chapter_order' => 9,
            'sort_order' => 6,
            'title' => 'Mock: Listening Advanced',
            'description' => 'Advanced listening mock with more challenging academic content.',
            'icon' => 'headphones',
            'skill_type' => 'listening',
            'level' => 'C1',
            'node_type' => 'practice',
            'xp_reward' => 75,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Mock Exams',
            'chapter_order' => 9,
            'sort_order' => 7,
            'title' => 'Mock: Reading Advanced',
            'description' => 'Advanced reading mock with more complex academic passages.',
            'icon' => 'book',
            'skill_type' => 'reading',
            'level' => 'C1',
            'node_type' => 'practice',
            'xp_reward' => 75,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Mock Exams',
            'chapter_order' => 9,
            'sort_order' => 8,
            'title' => 'Mock: Writing Advanced',
            'description' => 'Advanced writing mock requiring higher-level language and argumentation.',
            'icon' => 'pen',
            'skill_type' => 'writing',
            'level' => 'C1',
            'node_type' => 'practice',
            'xp_reward' => 75,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Mock Exams',
            'chapter_order' => 9,
            'sort_order' => 9,
            'title' => 'Mock: Speaking Advanced',
            'description' => 'Advanced speaking mock with complex abstract topics.',
            'icon' => 'mic',
            'skill_type' => 'speaking',
            'level' => 'C1',
            'node_type' => 'practice',
            'xp_reward' => 75,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Mock Exams',
            'chapter_order' => 9,
            'sort_order' => 10,
            'title' => 'IELTS Full Mock Exam',
            'description' => 'Complete a comprehensive IELTS mock exam covering all four skills under exam conditions.',
            'icon' => 'trophy',
            'skill_type' => 'mixed',
            'level' => 'C1',
            'node_type' => 'boss',
            'xp_reward' => 200,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);
    }

    private function seedToefl(): void
    {
        $exam = Exam::where('slug', 'toefl')->first();

        if (! $exam) {
            return;
        }

        LearningPathNode::where('exam_id', $exam->id)->delete();

        // Chapter 1: Reading Foundations (5 nodes)
        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Reading Foundations',
            'chapter_order' => 1,
            'sort_order' => 1,
            'title' => 'Academic vocabulary in context',
            'description' => 'Learn to identify and understand academic vocabulary using contextual clues in TOEFL passages.',
            'icon' => 'book',
            'skill_type' => 'reading',
            'level' => 'B1',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Reading Foundations',
            'chapter_order' => 1,
            'sort_order' => 2,
            'title' => 'Identifying main ideas and details',
            'description' => 'Develop skills to quickly identify main ideas, supporting details, and factual information.',
            'icon' => 'target',
            'skill_type' => 'reading',
            'level' => 'B1',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Reading Foundations',
            'chapter_order' => 1,
            'sort_order' => 3,
            'title' => 'Inference and rhetorical purpose',
            'description' => 'Master inference questions and understand the rhetorical purpose of sentences and paragraphs.',
            'icon' => 'brain',
            'skill_type' => 'reading',
            'level' => 'B2',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Reading Foundations',
            'chapter_order' => 1,
            'sort_order' => 4,
            'title' => 'Insert text and prose summary',
            'description' => 'Learn strategies for insert text questions and prose summary tasks in TOEFL reading.',
            'icon' => 'pen',
            'skill_type' => 'reading',
            'level' => 'B2',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Reading Foundations',
            'chapter_order' => 1,
            'sort_order' => 5,
            'title' => 'Reading checkpoint',
            'description' => 'Test your reading foundations with a comprehensive practice session.',
            'icon' => 'trophy',
            'skill_type' => 'reading',
            'level' => 'B2',
            'node_type' => 'practice',
            'xp_reward' => 75,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        // Chapter 2: Listening Comprehension (6 nodes)
        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Listening Comprehension',
            'chapter_order' => 2,
            'sort_order' => 1,
            'title' => 'Understanding campus conversations',
            'description' => 'Practice understanding conversations between students and university staff.',
            'icon' => 'headphones',
            'skill_type' => 'listening',
            'level' => 'B1',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Listening Comprehension',
            'chapter_order' => 2,
            'sort_order' => 2,
            'title' => 'Following academic lectures',
            'description' => 'Develop skills to follow and understand university-level academic lectures.',
            'icon' => 'brain',
            'skill_type' => 'listening',
            'level' => 'B1',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Listening Comprehension',
            'chapter_order' => 2,
            'sort_order' => 3,
            'title' => 'Identifying speaker attitude',
            'description' => 'Learn to identify the speaker\'s attitude, opinion, and degree of certainty from audio cues.',
            'icon' => 'headphones',
            'skill_type' => 'listening',
            'level' => 'B2',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Listening Comprehension',
            'chapter_order' => 2,
            'sort_order' => 4,
            'title' => 'Note-taking strategies',
            'description' => 'Master effective note-taking strategies for capturing key information during lectures.',
            'icon' => 'pen',
            'skill_type' => 'listening',
            'level' => 'B2',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Listening Comprehension',
            'chapter_order' => 2,
            'sort_order' => 5,
            'title' => 'Connecting ideas across passages',
            'description' => 'Develop the ability to connect ideas and understand relationships between different parts of a lecture.',
            'icon' => 'globe',
            'skill_type' => 'listening',
            'level' => 'B2',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Listening Comprehension',
            'chapter_order' => 2,
            'sort_order' => 6,
            'title' => 'Listening checkpoint',
            'description' => 'Test your listening comprehension skills with a comprehensive practice session.',
            'icon' => 'target',
            'skill_type' => 'listening',
            'level' => 'B2',
            'node_type' => 'practice',
            'xp_reward' => 75,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        // Chapter 3: Independent Speaking (5 nodes)
        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Independent Speaking',
            'chapter_order' => 3,
            'sort_order' => 1,
            'title' => 'Structuring your response',
            'description' => 'Learn to structure clear and coherent spoken responses within the time limit.',
            'icon' => 'mic',
            'skill_type' => 'speaking',
            'level' => 'B1',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Independent Speaking',
            'chapter_order' => 3,
            'sort_order' => 2,
            'title' => 'Supporting with examples',
            'description' => 'Develop skills to support your opinions with specific examples and personal experiences.',
            'icon' => 'star',
            'skill_type' => 'speaking',
            'level' => 'B2',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Independent Speaking',
            'chapter_order' => 3,
            'sort_order' => 3,
            'title' => 'Fluency and delivery',
            'description' => 'Improve your fluency, pacing, and overall delivery for higher speaking scores.',
            'icon' => 'mic',
            'skill_type' => 'speaking',
            'level' => 'B2',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Independent Speaking',
            'chapter_order' => 3,
            'sort_order' => 4,
            'title' => 'Time management (15/45 seconds)',
            'description' => 'Master the 15-second preparation and 45-second response time format.',
            'icon' => 'zap',
            'skill_type' => 'speaking',
            'level' => 'B2',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Independent Speaking',
            'chapter_order' => 3,
            'sort_order' => 5,
            'title' => 'Independent speaking practice',
            'description' => 'Practice independent speaking tasks under timed conditions.',
            'icon' => 'trophy',
            'skill_type' => 'speaking',
            'level' => 'B2',
            'node_type' => 'practice',
            'xp_reward' => 75,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        // Chapter 4: Integrated Speaking (5 nodes)
        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Integrated Speaking',
            'chapter_order' => 4,
            'sort_order' => 1,
            'title' => 'Read + Listen + Speak (Campus)',
            'description' => 'Practice integrated speaking tasks combining campus reading and listening materials.',
            'icon' => 'mic',
            'skill_type' => 'speaking',
            'level' => 'B2',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Integrated Speaking',
            'chapter_order' => 4,
            'sort_order' => 2,
            'title' => 'Read + Listen + Speak (Academic)',
            'description' => 'Practice integrated speaking tasks combining academic reading and lecture materials.',
            'icon' => 'brain',
            'skill_type' => 'speaking',
            'level' => 'B2',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Integrated Speaking',
            'chapter_order' => 4,
            'sort_order' => 3,
            'title' => 'Listen + Speak (Lecture summary)',
            'description' => 'Practice summarizing academic lectures in spoken form.',
            'icon' => 'headphones',
            'skill_type' => 'speaking',
            'level' => 'C1',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Integrated Speaking',
            'chapter_order' => 4,
            'sort_order' => 4,
            'title' => 'Paraphrasing and synthesizing',
            'description' => 'Master paraphrasing and synthesizing information from multiple sources in spoken responses.',
            'icon' => 'star',
            'skill_type' => 'speaking',
            'level' => 'C1',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Integrated Speaking',
            'chapter_order' => 4,
            'sort_order' => 5,
            'title' => 'Integrated speaking practice',
            'description' => 'Complete integrated speaking tasks under timed exam conditions.',
            'icon' => 'trophy',
            'skill_type' => 'speaking',
            'level' => 'C1',
            'node_type' => 'practice',
            'xp_reward' => 75,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        // Chapter 5: Integrated Writing (5 nodes)
        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Integrated Writing',
            'chapter_order' => 5,
            'sort_order' => 1,
            'title' => 'Reading academic passages effectively',
            'description' => 'Learn to quickly extract key points from academic reading passages for writing tasks.',
            'icon' => 'book',
            'skill_type' => 'writing',
            'level' => 'B2',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Integrated Writing',
            'chapter_order' => 5,
            'sort_order' => 2,
            'title' => 'Listening for counterarguments',
            'description' => 'Develop skills to identify how the lecture challenges or supports the reading passage.',
            'icon' => 'headphones',
            'skill_type' => 'writing',
            'level' => 'B2',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Integrated Writing',
            'chapter_order' => 5,
            'sort_order' => 3,
            'title' => 'Structuring integrated responses',
            'description' => 'Master the structure and organization of integrated writing responses.',
            'icon' => 'pen',
            'skill_type' => 'writing',
            'level' => 'B2',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Integrated Writing',
            'chapter_order' => 5,
            'sort_order' => 4,
            'title' => 'Paraphrasing without plagiarizing',
            'description' => 'Learn to effectively paraphrase source material while maintaining accuracy.',
            'icon' => 'brain',
            'skill_type' => 'writing',
            'level' => 'C1',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Integrated Writing',
            'chapter_order' => 5,
            'sort_order' => 5,
            'title' => 'Integrated writing practice',
            'description' => 'Complete integrated writing tasks under timed exam conditions.',
            'icon' => 'zap',
            'skill_type' => 'writing',
            'level' => 'C1',
            'node_type' => 'practice',
            'xp_reward' => 75,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        // Chapter 6: Academic Discussion (5 nodes)
        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Academic Discussion',
            'chapter_order' => 6,
            'sort_order' => 1,
            'title' => 'Understanding the discussion format',
            'description' => 'Learn the format and expectations of the TOEFL Academic Discussion writing task.',
            'icon' => 'book',
            'skill_type' => 'writing',
            'level' => 'B2',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Academic Discussion',
            'chapter_order' => 6,
            'sort_order' => 2,
            'title' => 'Formulating clear opinions',
            'description' => 'Develop skills to formulate and express clear, well-supported opinions in writing.',
            'icon' => 'pen',
            'skill_type' => 'writing',
            'level' => 'B2',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Academic Discussion',
            'chapter_order' => 6,
            'sort_order' => 3,
            'title' => 'Responding to student posts',
            'description' => 'Practice reading and responding to other students\' contributions in academic discussions.',
            'icon' => 'globe',
            'skill_type' => 'writing',
            'level' => 'B2',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Academic Discussion',
            'chapter_order' => 6,
            'sort_order' => 4,
            'title' => 'Academic register and precision',
            'description' => 'Master the use of academic register, precise vocabulary, and formal tone in discussions.',
            'icon' => 'star',
            'skill_type' => 'writing',
            'level' => 'C1',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Academic Discussion',
            'chapter_order' => 6,
            'sort_order' => 5,
            'title' => 'Academic discussion practice',
            'description' => 'Complete academic discussion writing tasks under timed conditions.',
            'icon' => 'trophy',
            'skill_type' => 'writing',
            'level' => 'C1',
            'node_type' => 'practice',
            'xp_reward' => 75,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        // Chapter 7: Vocabulary & Grammar (5 nodes)
        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Vocabulary & Grammar',
            'chapter_order' => 7,
            'sort_order' => 1,
            'title' => 'TOEFL essential vocabulary',
            'description' => 'Learn the most important vocabulary words that frequently appear in TOEFL exams.',
            'icon' => 'book',
            'skill_type' => 'reading',
            'level' => 'B1',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Vocabulary & Grammar',
            'chapter_order' => 7,
            'sort_order' => 2,
            'title' => 'Academic collocations',
            'description' => 'Master common academic collocations and word combinations for higher scores.',
            'icon' => 'star',
            'skill_type' => 'reading',
            'level' => 'B2',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Vocabulary & Grammar',
            'chapter_order' => 7,
            'sort_order' => 3,
            'title' => 'Transition words and connectors',
            'description' => 'Learn to use transition words and connectors effectively in writing and speaking.',
            'icon' => 'pen',
            'skill_type' => 'writing',
            'level' => 'B2',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Vocabulary & Grammar',
            'chapter_order' => 7,
            'sort_order' => 4,
            'title' => 'Complex grammar for high scores',
            'description' => 'Master complex grammatical structures that demonstrate high-level language proficiency.',
            'icon' => 'brain',
            'skill_type' => 'writing',
            'level' => 'C1',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Vocabulary & Grammar',
            'chapter_order' => 7,
            'sort_order' => 5,
            'title' => 'Vocabulary & grammar test',
            'description' => 'Comprehensive test covering all TOEFL vocabulary and grammar topics.',
            'icon' => 'trophy',
            'skill_type' => 'writing',
            'level' => 'C1',
            'node_type' => 'practice',
            'xp_reward' => 75,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        // Chapter 8: Test Strategy (4 nodes)
        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Test Strategy',
            'chapter_order' => 8,
            'sort_order' => 1,
            'title' => 'Time management for each section',
            'description' => 'Learn optimal time allocation strategies for each section of the TOEFL exam.',
            'icon' => 'zap',
            'skill_type' => 'mixed',
            'level' => 'C1',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Test Strategy',
            'chapter_order' => 8,
            'sort_order' => 2,
            'title' => 'Dealing with difficult questions',
            'description' => 'Develop strategies for handling the most challenging question types across all sections.',
            'icon' => 'target',
            'skill_type' => 'mixed',
            'level' => 'C1',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Test Strategy',
            'chapter_order' => 8,
            'sort_order' => 3,
            'title' => 'Score optimization strategies',
            'description' => 'Learn proven strategies to maximize your score across all TOEFL sections.',
            'icon' => 'star',
            'skill_type' => 'mixed',
            'level' => 'C1',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Test Strategy',
            'chapter_order' => 8,
            'sort_order' => 4,
            'title' => 'Pre-test routine and confidence',
            'description' => 'Build a pre-test routine and mental strategies for peak performance on test day.',
            'icon' => 'trophy',
            'skill_type' => 'mixed',
            'level' => 'C1',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        // Chapter 9: Mock Exams (10 nodes)
        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Mock Exams',
            'chapter_order' => 9,
            'sort_order' => 1,
            'title' => 'Mock: Reading Section',
            'description' => 'Complete a full TOEFL reading mock test with academic passages.',
            'icon' => 'book',
            'skill_type' => 'reading',
            'level' => 'B2',
            'node_type' => 'practice',
            'xp_reward' => 75,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Mock Exams',
            'chapter_order' => 9,
            'sort_order' => 2,
            'title' => 'Mock: Listening Section',
            'description' => 'Complete a full TOEFL listening mock test with conversations and lectures.',
            'icon' => 'headphones',
            'skill_type' => 'listening',
            'level' => 'B2',
            'node_type' => 'practice',
            'xp_reward' => 75,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Mock Exams',
            'chapter_order' => 9,
            'sort_order' => 3,
            'title' => 'Mock: Independent Speaking',
            'description' => 'Practice independent speaking tasks under timed conditions.',
            'icon' => 'mic',
            'skill_type' => 'speaking',
            'level' => 'B2',
            'node_type' => 'practice',
            'xp_reward' => 75,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Mock Exams',
            'chapter_order' => 9,
            'sort_order' => 4,
            'title' => 'Mock: Integrated Speaking',
            'description' => 'Practice integrated speaking tasks combining reading, listening, and speaking.',
            'icon' => 'mic',
            'skill_type' => 'speaking',
            'level' => 'C1',
            'node_type' => 'practice',
            'xp_reward' => 75,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Mock Exams',
            'chapter_order' => 9,
            'sort_order' => 5,
            'title' => 'Mock: Integrated Writing',
            'description' => 'Practice integrated writing tasks combining reading and listening sources.',
            'icon' => 'pen',
            'skill_type' => 'writing',
            'level' => 'C1',
            'node_type' => 'practice',
            'xp_reward' => 75,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Mock Exams',
            'chapter_order' => 9,
            'sort_order' => 6,
            'title' => 'Mock: Academic Discussion',
            'description' => 'Practice academic discussion writing tasks under timed conditions.',
            'icon' => 'pen',
            'skill_type' => 'writing',
            'level' => 'C1',
            'node_type' => 'practice',
            'xp_reward' => 75,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Mock Exams',
            'chapter_order' => 9,
            'sort_order' => 7,
            'title' => 'Mock: Reading Advanced',
            'description' => 'Advanced reading mock with more complex academic passages and question types.',
            'icon' => 'book',
            'skill_type' => 'reading',
            'level' => 'C1',
            'node_type' => 'practice',
            'xp_reward' => 75,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Mock Exams',
            'chapter_order' => 9,
            'sort_order' => 8,
            'title' => 'Mock: Listening Advanced',
            'description' => 'Advanced listening mock with more challenging lectures and conversations.',
            'icon' => 'headphones',
            'skill_type' => 'listening',
            'level' => 'C1',
            'node_type' => 'practice',
            'xp_reward' => 75,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Mock Exams',
            'chapter_order' => 9,
            'sort_order' => 9,
            'title' => 'Mock: Full Speaking',
            'description' => 'Complete all speaking tasks in a full mock test format.',
            'icon' => 'mic',
            'skill_type' => 'speaking',
            'level' => 'C1',
            'node_type' => 'practice',
            'xp_reward' => 75,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Mock Exams',
            'chapter_order' => 9,
            'sort_order' => 10,
            'title' => 'TOEFL Full Mock Exam',
            'description' => 'Complete a comprehensive TOEFL mock exam covering all four sections under exam conditions.',
            'icon' => 'trophy',
            'skill_type' => 'mixed',
            'level' => 'C1',
            'node_type' => 'boss',
            'xp_reward' => 200,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);
    }

    private function seedCambridge(): void
    {
        $exam = Exam::where('slug', 'cambridge')->first();

        if (! $exam) {
            return;
        }

        LearningPathNode::where('exam_id', $exam->id)->delete();

        // Chapter 1: Use of English Fundamentals (6 nodes)
        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Use of English Fundamentals',
            'chapter_order' => 1,
            'sort_order' => 1,
            'title' => 'Multiple-choice cloze technique',
            'description' => 'Learn strategies for answering multiple-choice cloze questions in the Use of English paper.',
            'icon' => 'book',
            'skill_type' => 'reading',
            'level' => 'B1',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Use of English Fundamentals',
            'chapter_order' => 1,
            'sort_order' => 2,
            'title' => 'Open cloze strategies',
            'description' => 'Master strategies for completing open cloze exercises without answer choices.',
            'icon' => 'pen',
            'skill_type' => 'reading',
            'level' => 'B2',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Use of English Fundamentals',
            'chapter_order' => 1,
            'sort_order' => 3,
            'title' => 'Word formation patterns',
            'description' => 'Learn common word formation patterns including prefixes, suffixes, and word families.',
            'icon' => 'brain',
            'skill_type' => 'reading',
            'level' => 'B2',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Use of English Fundamentals',
            'chapter_order' => 1,
            'sort_order' => 4,
            'title' => 'Key word transformations',
            'description' => 'Master the technique of rewriting sentences using a given key word.',
            'icon' => 'star',
            'skill_type' => 'reading',
            'level' => 'B2',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Use of English Fundamentals',
            'chapter_order' => 1,
            'sort_order' => 5,
            'title' => 'Use of English combined practice',
            'description' => 'Practice all Use of English question types in a combined exercise session.',
            'icon' => 'target',
            'skill_type' => 'reading',
            'level' => 'B2',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Use of English Fundamentals',
            'chapter_order' => 1,
            'sort_order' => 6,
            'title' => 'Use of English checkpoint',
            'description' => 'Test your Use of English skills with a comprehensive practice session.',
            'icon' => 'trophy',
            'skill_type' => 'reading',
            'level' => 'B2',
            'node_type' => 'practice',
            'xp_reward' => 75,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        // Chapter 2: Reading Comprehension (5 nodes)
        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Reading Comprehension',
            'chapter_order' => 2,
            'sort_order' => 1,
            'title' => 'Multiple choice reading',
            'description' => 'Develop strategies for answering multiple choice questions in Cambridge reading papers.',
            'icon' => 'book',
            'skill_type' => 'reading',
            'level' => 'B2',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Reading Comprehension',
            'chapter_order' => 2,
            'sort_order' => 2,
            'title' => 'Gapped text (paragraph insertion)',
            'description' => 'Master the technique of inserting missing paragraphs into a gapped text.',
            'icon' => 'pen',
            'skill_type' => 'reading',
            'level' => 'B2',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Reading Comprehension',
            'chapter_order' => 2,
            'sort_order' => 3,
            'title' => 'Multiple matching technique',
            'description' => 'Learn to match statements to the correct section of a long text efficiently.',
            'icon' => 'target',
            'skill_type' => 'reading',
            'level' => 'B2',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Reading Comprehension',
            'chapter_order' => 2,
            'sort_order' => 4,
            'title' => 'Speed and accuracy balance',
            'description' => 'Develop the ability to read quickly while maintaining accuracy in comprehension.',
            'icon' => 'zap',
            'skill_type' => 'reading',
            'level' => 'B2',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Reading Comprehension',
            'chapter_order' => 2,
            'sort_order' => 5,
            'title' => 'Reading checkpoint',
            'description' => 'Test your reading comprehension skills with a comprehensive practice session.',
            'icon' => 'trophy',
            'skill_type' => 'reading',
            'level' => 'B2',
            'node_type' => 'practice',
            'xp_reward' => 75,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        // Chapter 3: Writing Part 1 - Essays (5 nodes)
        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Writing Part 1 - Essays',
            'chapter_order' => 3,
            'sort_order' => 1,
            'title' => 'Essay structure for Cambridge',
            'description' => 'Learn the expected essay structure and format for Cambridge writing exams.',
            'icon' => 'pen',
            'skill_type' => 'writing',
            'level' => 'B2',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Writing Part 1 - Essays',
            'chapter_order' => 3,
            'sort_order' => 2,
            'title' => 'Using the input material effectively',
            'description' => 'Master techniques for incorporating input material into your essay effectively.',
            'icon' => 'book',
            'skill_type' => 'writing',
            'level' => 'B2',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Writing Part 1 - Essays',
            'chapter_order' => 3,
            'sort_order' => 3,
            'title' => 'Argumentation and persuasion',
            'description' => 'Develop strong argumentation and persuasion skills for essay writing.',
            'icon' => 'brain',
            'skill_type' => 'writing',
            'level' => 'B2',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Writing Part 1 - Essays',
            'chapter_order' => 3,
            'sort_order' => 4,
            'title' => 'Register and style control',
            'description' => 'Master the control of register and style appropriate for different essay types.',
            'icon' => 'star',
            'skill_type' => 'writing',
            'level' => 'C1',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Writing Part 1 - Essays',
            'chapter_order' => 3,
            'sort_order' => 5,
            'title' => 'Essay timed practice',
            'description' => 'Practice writing Cambridge essays under timed exam conditions.',
            'icon' => 'zap',
            'skill_type' => 'writing',
            'level' => 'C1',
            'node_type' => 'practice',
            'xp_reward' => 75,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        // Chapter 4: Writing Part 2 - Choice Tasks (5 nodes)
        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Writing Part 2 - Choice Tasks',
            'chapter_order' => 4,
            'sort_order' => 1,
            'title' => 'Article writing technique',
            'description' => 'Learn the format, style, and techniques for writing engaging articles.',
            'icon' => 'pen',
            'skill_type' => 'writing',
            'level' => 'B2',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Writing Part 2 - Choice Tasks',
            'chapter_order' => 4,
            'sort_order' => 2,
            'title' => 'Formal and informal letters/emails',
            'description' => 'Master the conventions of formal and informal letters and emails.',
            'icon' => 'globe',
            'skill_type' => 'writing',
            'level' => 'B2',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Writing Part 2 - Choice Tasks',
            'chapter_order' => 4,
            'sort_order' => 3,
            'title' => 'Report writing skills',
            'description' => 'Develop skills for writing clear, well-structured reports with recommendations.',
            'icon' => 'book',
            'skill_type' => 'writing',
            'level' => 'B2',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Writing Part 2 - Choice Tasks',
            'chapter_order' => 4,
            'sort_order' => 4,
            'title' => 'Review writing technique',
            'description' => 'Learn to write engaging reviews with clear opinions and recommendations.',
            'icon' => 'star',
            'skill_type' => 'writing',
            'level' => 'B2',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Writing Part 2 - Choice Tasks',
            'chapter_order' => 4,
            'sort_order' => 5,
            'title' => 'Writing Part 2 practice',
            'description' => 'Practice various Writing Part 2 task types under timed conditions.',
            'icon' => 'trophy',
            'skill_type' => 'writing',
            'level' => 'B2',
            'node_type' => 'practice',
            'xp_reward' => 75,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        // Chapter 5: Listening Skills (5 nodes)
        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Listening Skills',
            'chapter_order' => 5,
            'sort_order' => 1,
            'title' => 'Short extract multiple choice',
            'description' => 'Practice answering multiple choice questions based on short audio extracts.',
            'icon' => 'headphones',
            'skill_type' => 'listening',
            'level' => 'B1',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Listening Skills',
            'chapter_order' => 5,
            'sort_order' => 2,
            'title' => 'Sentence completion from audio',
            'description' => 'Develop skills for completing sentences based on longer audio recordings.',
            'icon' => 'pen',
            'skill_type' => 'listening',
            'level' => 'B2',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Listening Skills',
            'chapter_order' => 5,
            'sort_order' => 3,
            'title' => 'Multiple matching (speakers)',
            'description' => 'Practice matching statements to the correct speaker in multi-speaker recordings.',
            'icon' => 'target',
            'skill_type' => 'listening',
            'level' => 'B2',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Listening Skills',
            'chapter_order' => 5,
            'sort_order' => 4,
            'title' => 'Extended listening multiple choice',
            'description' => 'Master multiple choice questions based on extended interviews and discussions.',
            'icon' => 'headphones',
            'skill_type' => 'listening',
            'level' => 'B2',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Listening Skills',
            'chapter_order' => 5,
            'sort_order' => 5,
            'title' => 'Listening checkpoint',
            'description' => 'Test your listening skills with a comprehensive practice session.',
            'icon' => 'trophy',
            'skill_type' => 'listening',
            'level' => 'B2',
            'node_type' => 'practice',
            'xp_reward' => 75,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        // Chapter 6: Speaking Exam (5 nodes)
        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Speaking Exam',
            'chapter_order' => 6,
            'sort_order' => 1,
            'title' => 'Part 1: Interview techniques',
            'description' => 'Develop confidence and fluency for the interview section of the speaking exam.',
            'icon' => 'mic',
            'skill_type' => 'speaking',
            'level' => 'B1',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Speaking Exam',
            'chapter_order' => 6,
            'sort_order' => 2,
            'title' => 'Part 2: Comparing photographs',
            'description' => 'Master the technique of comparing and contrasting photographs in the speaking exam.',
            'icon' => 'mic',
            'skill_type' => 'speaking',
            'level' => 'B2',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Speaking Exam',
            'chapter_order' => 6,
            'sort_order' => 3,
            'title' => 'Part 3: Collaborative discussion',
            'description' => 'Practice collaborative discussion skills with a partner using visual prompts.',
            'icon' => 'globe',
            'skill_type' => 'speaking',
            'level' => 'B2',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Speaking Exam',
            'chapter_order' => 6,
            'sort_order' => 4,
            'title' => 'Part 4: Extended discussion',
            'description' => 'Build skills for extended discussion on topics related to the collaborative task.',
            'icon' => 'brain',
            'skill_type' => 'speaking',
            'level' => 'B2',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Speaking Exam',
            'chapter_order' => 6,
            'sort_order' => 5,
            'title' => 'Speaking mock test',
            'description' => 'Complete a full Cambridge speaking mock test covering all four parts.',
            'icon' => 'trophy',
            'skill_type' => 'speaking',
            'level' => 'B2',
            'node_type' => 'practice',
            'xp_reward' => 75,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        // Chapter 7: Vocabulary & Grammar (5 nodes)
        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Vocabulary & Grammar',
            'chapter_order' => 7,
            'sort_order' => 1,
            'title' => 'Phrasal verbs and idioms',
            'description' => 'Learn essential phrasal verbs and idioms commonly tested in Cambridge exams.',
            'icon' => 'book',
            'skill_type' => 'reading',
            'level' => 'B2',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Vocabulary & Grammar',
            'chapter_order' => 7,
            'sort_order' => 2,
            'title' => 'Collocations for Cambridge',
            'description' => 'Master common collocations that frequently appear in Cambridge exam papers.',
            'icon' => 'star',
            'skill_type' => 'reading',
            'level' => 'B2',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Vocabulary & Grammar',
            'chapter_order' => 7,
            'sort_order' => 3,
            'title' => 'Grammar for Use of English',
            'description' => 'Focus on grammar structures most commonly tested in the Use of English paper.',
            'icon' => 'brain',
            'skill_type' => 'reading',
            'level' => 'B2',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Vocabulary & Grammar',
            'chapter_order' => 7,
            'sort_order' => 4,
            'title' => 'Advanced vocabulary C1 level',
            'description' => 'Expand your vocabulary to C1 level with advanced words and expressions.',
            'icon' => 'zap',
            'skill_type' => 'reading',
            'level' => 'C1',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Vocabulary & Grammar',
            'chapter_order' => 7,
            'sort_order' => 5,
            'title' => 'Vocabulary & grammar test',
            'description' => 'Comprehensive test covering all Cambridge vocabulary and grammar topics.',
            'icon' => 'trophy',
            'skill_type' => 'reading',
            'level' => 'C1',
            'node_type' => 'practice',
            'xp_reward' => 75,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        // Chapter 8: Exam Strategy (4 nodes)
        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Exam Strategy',
            'chapter_order' => 8,
            'sort_order' => 1,
            'title' => 'Time allocation per paper',
            'description' => 'Learn optimal time allocation strategies for each Cambridge exam paper.',
            'icon' => 'zap',
            'skill_type' => 'mixed',
            'level' => 'B2',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Exam Strategy',
            'chapter_order' => 8,
            'sort_order' => 2,
            'title' => 'Common errors and traps',
            'description' => 'Identify common errors and traps in Cambridge exams and learn how to avoid them.',
            'icon' => 'target',
            'skill_type' => 'mixed',
            'level' => 'B2',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Exam Strategy',
            'chapter_order' => 8,
            'sort_order' => 3,
            'title' => 'Maximizing your Cambridge Scale score',
            'description' => 'Learn strategies to maximize your score on the Cambridge English Scale.',
            'icon' => 'star',
            'skill_type' => 'mixed',
            'level' => 'C1',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Exam Strategy',
            'chapter_order' => 8,
            'sort_order' => 4,
            'title' => 'Day before the exam: final review',
            'description' => 'A complete guide for your final review and preparation the day before the exam.',
            'icon' => 'trophy',
            'skill_type' => 'mixed',
            'level' => 'C1',
            'node_type' => 'lesson',
            'xp_reward' => 50,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        // Chapter 9: Mock Exams (10 nodes)
        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Mock Exams',
            'chapter_order' => 9,
            'sort_order' => 1,
            'title' => 'Mock: Use of English Parts 1-4',
            'description' => 'Complete a full Use of English mock covering all four parts.',
            'icon' => 'book',
            'skill_type' => 'reading',
            'level' => 'B2',
            'node_type' => 'practice',
            'xp_reward' => 75,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Mock Exams',
            'chapter_order' => 9,
            'sort_order' => 2,
            'title' => 'Mock: Reading Parts 5-7',
            'description' => 'Complete a full reading mock covering Parts 5, 6, and 7.',
            'icon' => 'book',
            'skill_type' => 'reading',
            'level' => 'B2',
            'node_type' => 'practice',
            'xp_reward' => 75,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Mock Exams',
            'chapter_order' => 9,
            'sort_order' => 3,
            'title' => 'Mock: Writing Part 1',
            'description' => 'Practice a full Writing Part 1 essay under timed conditions.',
            'icon' => 'pen',
            'skill_type' => 'writing',
            'level' => 'B2',
            'node_type' => 'practice',
            'xp_reward' => 75,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Mock Exams',
            'chapter_order' => 9,
            'sort_order' => 4,
            'title' => 'Mock: Writing Part 2',
            'description' => 'Practice a full Writing Part 2 choice task under timed conditions.',
            'icon' => 'pen',
            'skill_type' => 'writing',
            'level' => 'B2',
            'node_type' => 'practice',
            'xp_reward' => 75,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Mock Exams',
            'chapter_order' => 9,
            'sort_order' => 5,
            'title' => 'Mock: Listening Full Paper',
            'description' => 'Complete a full Cambridge listening mock test with all four parts.',
            'icon' => 'headphones',
            'skill_type' => 'listening',
            'level' => 'B2',
            'node_type' => 'practice',
            'xp_reward' => 75,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Mock Exams',
            'chapter_order' => 9,
            'sort_order' => 6,
            'title' => 'Mock: Speaking Full Test',
            'description' => 'Complete a full Cambridge speaking mock test covering all four parts.',
            'icon' => 'mic',
            'skill_type' => 'speaking',
            'level' => 'B2',
            'node_type' => 'practice',
            'xp_reward' => 75,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Mock Exams',
            'chapter_order' => 9,
            'sort_order' => 7,
            'title' => 'Mock: Use of English Advanced',
            'description' => 'Advanced Use of English mock with C1-level difficulty.',
            'icon' => 'brain',
            'skill_type' => 'reading',
            'level' => 'C1',
            'node_type' => 'practice',
            'xp_reward' => 75,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Mock Exams',
            'chapter_order' => 9,
            'sort_order' => 8,
            'title' => 'Mock: Reading Advanced',
            'description' => 'Advanced reading mock with more complex texts and question types.',
            'icon' => 'book',
            'skill_type' => 'reading',
            'level' => 'C1',
            'node_type' => 'practice',
            'xp_reward' => 75,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Mock Exams',
            'chapter_order' => 9,
            'sort_order' => 9,
            'title' => 'Mock: Writing Advanced',
            'description' => 'Advanced writing mock requiring higher-level language and task completion.',
            'icon' => 'pen',
            'skill_type' => 'writing',
            'level' => 'C1',
            'node_type' => 'practice',
            'xp_reward' => 75,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);

        LearningPathNode::create([
            'exam_id' => $exam->id,
            'chapter_name' => 'Mock Exams',
            'chapter_order' => 9,
            'sort_order' => 10,
            'title' => 'Cambridge Full Mock Exam',
            'description' => 'Complete a comprehensive Cambridge mock exam covering all papers under exam conditions.',
            'icon' => 'trophy',
            'skill_type' => 'mixed',
            'level' => 'C1',
            'node_type' => 'boss',
            'xp_reward' => 200,
            'mastery_score' => 70,
            'exercises_count' => 3,
        ]);
    }
}
