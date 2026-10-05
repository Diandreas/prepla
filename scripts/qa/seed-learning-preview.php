<?php

use App\Models\CurriculumSkeleton;
use App\Models\Exam;
use App\Models\Lesson;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserWordProgress;
use App\Services\PersonalLexiconService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Hash;

// Local-only visual QA. Never resets an existing learner or production database.
require getcwd().'/vendor/autoload.php';
$app = require getcwd().'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! $app->environment('local')) {
    throw new RuntimeException('Local preview only.');
}
$email = 'preview-'.bin2hex(random_bytes(4)).'@prepla.invalid';
$password = bin2hex(random_bytes(10));
$user = User::create(['name' => 'Aperçu PrepLa', 'email' => $email, 'password' => Hash::make($password), 'email_verified_at' => now()]);
$exam = Exam::firstOrFail();
UserProfile::create(['user_id' => $user->id, 'target_exam_id' => $exam->id, 'current_level' => 'A1', 'onboarding_completed_at' => now(), 'native_language' => 'fr', 'interface_language' => 'fr', 'trial_ends_at' => now()->addDays(7)]);
$title = 'Me présenter en quelques phrases';
CurriculumSkeleton::create(['user_id' => $user->id, 'exam_id' => $exam->id, 'objectives' => [['title' => $title, 'concept' => 'qa.introductions', 'level' => 'A1', 'status' => 'current']], 'current_objective_index' => 0]);
$lesson = Lesson::create(['user_id' => $user->id, 'skeleton_objective_index' => 0, 'title' => $title, 'concept' => 'qa.introductions', 'status' => 'ready', 'theory_markdown' => '## Se présenter simplement'."\n\n".'Commence par saluer, puis donne ton prénom. Observe les exemples : Hello! My name is Sam. I am a student.', 'key_takeaways' => ['Une courte phrase suffit pour commencer.'], 'comprehension_quiz' => [['question' => 'Comment donner son prénom ?', 'options' => ['My name is Sam.', 'Good night.'], 'correct_answer' => 'My name is Sam.']], 'key_vocabulary' => [['word' => 'hello', 'translation' => 'bonjour', 'definition' => 'A greeting', 'example' => 'Hello! My name is Sam.'], ['word' => 'name', 'translation' => 'nom', 'definition' => 'What a person is called', 'example' => 'My name is Sam.'], ['word' => 'student', 'translation' => 'étudiant', 'definition' => 'A person who studies', 'example' => 'I am a student.']]]);
$lexicon = app(PersonalLexiconService::class);
foreach ($lexicon->lessonWords($user, $lesson) as $word) {
    UserWordProgress::create(['user_id' => $user->id, 'dictionary_word_id' => $word['id'], 'status' => 'discovered']);
}
echo json_encode(['email' => $email, 'password' => $password, 'lesson_id' => $lesson->id], JSON_UNESCAPED_UNICODE).PHP_EOL;
