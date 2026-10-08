<?php

use App\Http\Controllers\AiToolsController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\Center\AssignmentController;
use App\Http\Controllers\Center\ClassroomController;
use App\Http\Controllers\Center\ExerciseBuilderController;
use App\Http\Controllers\Center\MediaController;
use App\Http\Controllers\Center\ProgressController;
use App\Http\Controllers\Center\StudentController;
use App\Http\Controllers\ChapterSynthesisController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DictionaryController;
use App\Http\Controllers\ErrorReviewController;
use App\Http\Controllers\ExerciseController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\JoinCenterController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\LearningPreferencesController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\LevelExamController;
use App\Http\Controllers\NodeStartController;
use App\Http\Controllers\OfflinePackController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\PracticeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\ResultsController;
use App\Http\Controllers\SuperAdmin\CenterController;
use App\Http\Controllers\TtsController;
use App\Http\Controllers\VocabularyController;
use App\Http\Middleware\EnsureCenterStaff;
use App\Http\Middleware\EnsureExerciseQuota;
use App\Http\Middleware\EnsureOnboardingComplete;
use App\Http\Middleware\EnsureSuperAdmin;
use App\Services\Blog\BlogService;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', LandingController::class)->name('home');
Route::get('/offline', fn () => view('offline'))->name('offline');
// Espace hors ligne : page publique sans donnée personnelle, servie depuis le cache
// quand le réseau manque. Elle ne lit que le stockage local de l'appareil.
Route::get('/telechargements', fn () => view('offline-app'))->name('offline.app');

Route::get('/privacy', fn () => view('legal.privacy'))->name('privacy');
Route::get('/terms', fn () => view('legal.terms'))->name('terms');
Route::get('/account-deletion', fn () => view('legal.account-deletion'))->name('account-deletion');

Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');

// SEO : sitemap des seules pages publiques (le reste est Disallow dans robots.txt)
Route::get('/sitemap.xml', function () {
    $urls = [
        ['loc' => url('/'), 'priority' => '1.0'],
        ['loc' => url('/register'), 'priority' => '0.8'],
        ['loc' => url('/login'), 'priority' => '0.5'],
        ['loc' => url('/blog'), 'priority' => '0.6'],
        ['loc' => url('/privacy'), 'priority' => '0.3'],
        ['loc' => url('/terms'), 'priority' => '0.3'],
        ['loc' => url('/account-deletion'), 'priority' => '0.2'],
    ];
    foreach (BlogService::posts() as $post) {
        $urls[] = ['loc' => url('/blog/'.$post['slug']), 'priority' => '0.5'];
    }
    $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
        .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
    foreach ($urls as $u) {
        $xml .= "  <url><loc>{$u['loc']}</loc><priority>{$u['priority']}</priority></url>\n";
    }
    $xml .= '</urlset>';

    return response($xml, 200, ['Content-Type' => 'application/xml']);
})->name('sitemap');

Route::middleware(['auth'])->group(function () {
    // Onboarding
    Route::prefix('onboarding')->name('onboarding.')->group(function () {
        Route::get('/native-language', [OnboardingController::class, 'nativeLanguage'])->name('native-language');
        Route::post('/native-language', [OnboardingController::class, 'storeNativeLanguage'])->name('native-language.store');
        Route::get('/exam', [OnboardingController::class, 'examSelect'])->name('exam');
        Route::post('/exam', [OnboardingController::class, 'storeExam'])->name('exam.store');
        Route::get('/goal', [OnboardingController::class, 'goalSetting'])->name('goal');
        Route::post('/goal', [OnboardingController::class, 'storeGoal'])->name('goal.store');
        Route::get('/placement', [OnboardingController::class, 'placementTest'])->name('placement');
        Route::post('/placement', [OnboardingController::class, 'submitPlacement'])->name('placement.store');
        Route::get('/result', [OnboardingController::class, 'result'])->name('result');
        // Async program generation — keeps the result page instant; the heavy AI
        // call runs here and the page fetches it client-side behind a loader.
        Route::get('/program', [OnboardingController::class, 'programData'])->name('program');
        Route::post('/complete', [OnboardingController::class, 'complete'])->name('complete');
    });

    // Protected by onboarding
    Route::middleware([EnsureOnboardingComplete::class])->group(function () {
        Route::get('dashboard', DashboardController::class)->name('dashboard');

        // Practice
        Route::get('practice', [PracticeController::class, 'index'])->name('practice.index');
        Route::get('practice/{exam}', [PracticeController::class, 'examDashboard'])->name('practice.exam');
        Route::get('practice-skill/{skill}', [PracticeController::class, 'skill'])->whereIn('skill', ['speaking', 'listening'])->name('practice.skill');
        Route::get('practice/{exam}/section/{section}', [PracticeController::class, 'sectionDrills'])->name('practice.section');
        // Pratiquer par type : 1 clic sur un type → 1 exercice jamais fait, sinon généré.
        // Limité comme les autres appels IA : depuis que la route génère dès que
        // l'apprenant a tout fait, un clic répété pourrait épuiser le quota du
        // fournisseur — la panne exacte qui avait bloqué des comptes.
        Route::get('practice/{exam}/drill/{exerciseType}', [PracticeController::class, 'drillByType'])->middleware('throttle:ai-calls')->name('practice.drill.type');
        Route::post('practice/{exam}/section/{section}/generate', [PracticeController::class, 'generateSection'])->middleware('throttle:ai-calls')->name('practice.section.generate');
        Route::get('practice/{exam}/simulate', [PracticeController::class, 'simulate'])->name('practice.simulate');
        Route::post('practice/{exam}/simulate', [PracticeController::class, 'submitSimulation'])->name('practice.simulate.store');

        // Le quota gratuit se vérifie à l'ENTRÉE d'un exercice, jamais à l'envoi :
        // posté sur la soumission, il renvoyait vers l'abonnement un apprenant qui
        // venait de terminer sa séance, sans la corriger ni lui compter son XP.
        Route::middleware([EnsureExerciseQuota::class])->group(function () {
            // Node start (Duolingo-style: 1 click → exercise)
            Route::get('node/{node}/start', NodeStartController::class)->name('node.start');
            Route::get('exercise/{exercise}', [ExerciseController::class, 'show'])->name('exercise.show');
        });

        // Exercises — une séance commencée va toujours jusqu'à sa correction.
        Route::post('exercise/{exercise}/submit', [ExerciseController::class, 'submit'])->name('exercise.submit');
        Route::post('node/{node}/submit', [ExerciseController::class, 'submitSession'])->name('exercise.submit_session');
        Route::get('exercise/result/{attempt}', [ExerciseController::class, 'result'])->name('exercise.result');
        Route::get('node/{node}/result', [ExerciseController::class, 'sessionResult'])->name('node.session_result');

        // Packs hors ligne : series telechargeables, puis telechargement d'un pack complet.
        Route::get('api/offline/packs', [OfflinePackController::class, 'index'])->name('offline.packs.index');
        Route::post('api/offline/packs/{exam}/{exerciseType}', [OfflinePackController::class, 'store'])->name('offline.packs.store');

        // Examen de fin de palier : il consolide le niveau et declenche la promotion.
        Route::get('niveau/{level}/examen', [LevelExamController::class, 'start'])
            ->name('level.exam');

        // Boss-level chapter synthesis
        Route::get('chapter/{chapterOrder}/synthesis', [ChapterSynthesisController::class, 'start'])
            ->whereNumber('chapterOrder')
            ->name('chapter.synthesis');

        // Dictionary
        Route::patch('/learning/preferences', [LearningPreferencesController::class, 'update'])->name('learning.preferences');
        Route::prefix('dictionary')->name('dictionary.')->group(function () {
            Route::get('/', [DictionaryController::class, 'index'])->name('index');
            Route::post('/discover', [DictionaryController::class, 'discover'])->name('discover');
            Route::get('/lookup/{language}/{word}', [DictionaryController::class, 'lookup'])->name('lookup');
            Route::post('/save', [DictionaryController::class, 'save'])->name('save');
            Route::get('/review', [DictionaryController::class, 'reviewPage'])->name('review_page');
            Route::get('/review-session', [DictionaryController::class, 'reviewSession'])->name('review_session');
            Route::post('/review-batch/submit', [DictionaryController::class, 'submitReviewBatch'])->name('submit_review_batch');
            Route::get('/audio/{word}', [DictionaryController::class, 'audio'])->name('audio');
        });

        Route::middleware('throttle:ai-calls')->group(function () {
            Route::post('api/ai/explain', [ExerciseController::class, 'explainMistake'])->name('api.ai.explain');
            Route::post('api/ai/chat', [ExerciseController::class, 'chatMistake'])->name('api.ai.chat');
            Route::post('api/exercise/verify-single', [ExerciseController::class, 'verifySingle'])->name('api.exercise.verify-single');
            // Évaluation live d'un tour de role-play (audio → transcription + correction)
            Route::post('api/exercise/evaluate-turn', [ExerciseController::class, 'evaluateTurn'])->name('api.exercise.evaluate-turn');

            // TTS API
            Route::post('api/tts/speak', [TtsController::class, 'speak'])->name('tts.speak');

            // AI Tools (routes de génération/appel IA uniquement — pas les pages GET d'affichage)
            Route::post('ai-tools/generator', [AiToolsController::class, 'generateExercise'])->name('ai-tools.generator.store');
            Route::post('ai-tools/writing-corrector', [AiToolsController::class, 'submitWriting'])->name('ai-tools.writing-corrector.store');
            Route::post('ai-tools/writing-corrector/extract', [AiToolsController::class, 'extractWritingImage'])->name('ai-tools.writing-corrector.extract');
            Route::post('ai-tools/explainer/ask', [AiToolsController::class, 'askExplainer'])->name('ai-tools.explainer.ask');
            Route::post('ai-tools/explainer/transcribe', [AiToolsController::class, 'transcribeTutor'])->name('ai-tools.explainer.transcribe');
            Route::post('ai-tools/explainer/image', [AiToolsController::class, 'extractWritingImage'])->name('ai-tools.explainer.image');
        });

        // AI Tools — pages d'affichage (pas d'appel IA au chargement)
        Route::get('ai-tools', [AiToolsController::class, 'index'])->name('ai-tools.index');
        Route::get('ai-tools/generator', [AiToolsController::class, 'generator'])->name('ai-tools.generator');
        Route::get('ai-tools/writing-corrector', [AiToolsController::class, 'writingCorrector'])->name('ai-tools.writing-corrector');
        Route::get('ai-tools/explainer', [AiToolsController::class, 'explainer'])->name('ai-tools.explainer');
        Route::get('ai-tools/recommendations', [AiToolsController::class, 'recommendations'])->name('ai-tools.recommendations');

        // Vocabulary — fusionné avec le Dictionnaire : les pages "Mon Lexique" /
        // "Session de Révision" redirigent vers le Dictionnaire (point d'entrée
        // unique). On conserve les endpoints POST utilisés par les composants.
        Route::prefix('vocabulary')->name('vocabulary.')->group(function () {
            Route::get('/', fn () => redirect()->route('dictionary.index'))->name('index');
            Route::get('/learn', fn () => redirect()->route('dictionary.index'))->name('learn');
            Route::get('/random/{languageSlug}', [VocabularyController::class, 'random'])->name('random');
            Route::get('/review', fn () => redirect()->route('dictionary.review_page'))->name('review');
            Route::post('/', [VocabularyController::class, 'store'])->name('store');
            Route::post('/{vocab}/review', [VocabularyController::class, 'submitReview'])->name('submit-review');
        });

        // Lessons (Pilier 1 + 9: Progressive adaptive lessons)
        Route::prefix('lessons')->name('lessons.')->group(function () {
            Route::get('/', [LessonController::class, 'index'])->name('index');
            Route::get('/next', [LessonController::class, 'next'])->name('next');
            Route::get('/{lesson}', [LessonController::class, 'show'])->name('show');
            Route::post('/{lesson}/quiz', [LessonController::class, 'submitQuiz'])->name('quiz');
        });

        // Error Review
        Route::prefix('errors')->name('errors.')->group(function () {
            Route::get('/', [ErrorReviewController::class, 'index'])->name('index');
            Route::get('/practice', [ErrorReviewController::class, 'practice'])->name('practice');
            Route::post('/{error}/review', [ErrorReviewController::class, 'submitReview'])->name('submit-review');
            // Un exercice neuf sur le concept raté, plutôt que la même phrase reposée.
            Route::post('/{error}/similar', [ErrorReviewController::class, 'similar'])->name('similar');
        });

        // Leaderboard
        Route::get('leaderboard', [HomeController::class, 'leaderboard'])->name('leaderboard');

        // Results
        Route::get('results', [ResultsController::class, 'index'])->name('results.index');
        Route::get('results/attempts', [ResultsController::class, 'attempts'])->name('results.attempts');
        Route::get('results/attempts/{attempt}', [ResultsController::class, 'attemptDetail'])->name('results.attempt');

        // Profile extras
        Route::get('profile/achievements', [ProfileController::class, 'achievements'])->name('profile.achievements');
        Route::get('profile/stats', [ProfileController::class, 'stats'])->name('profile.stats');

        // Test Sandbox — dev/QA tooling, not meant for regular users in prod.
        Route::middleware(EnsureSuperAdmin::class)->group(function () {
            Route::get('test/sandbox', function () {
                return Inertia::render('test/sandbox');
            })->name('test.sandbox');

            Route::get('test/exercises', function () {
                return Inertia::render('test/test-exercises');
            })->name('test.exercises');

            Route::get('test/exercises/audit', function () {
                return Inertia::render('test/audit');
            })->name('test.exercises.audit');
        });
    });
});

// Invitations d'enseignants : un lien a usage unique, ou la personne choisit son
// mot de passe et trouve son espace deja monte. Fabriquer un compte a sa place
// obligeait a se passer son mot de passe de main en main.
Route::middleware('auth')->group(function () {
    Route::get('invitations', [\App\Http\Controllers\TeacherInvitationController::class, 'index'])->name('teach.invitations');
    Route::post('invitations', [\App\Http\Controllers\TeacherInvitationController::class, 'store'])->name('teach.invitations.store');
});

// Ouvert sans compte : l'invite n'en a precisement pas encore.
Route::get('invitation/{token}', [\App\Http\Controllers\TeacherInvitationController::class, 'show'])->name('teach.invitation.show');
Route::post('invitation/{token}', [\App\Http\Controllers\TeacherInvitationController::class, 'accept'])->name('teach.invitation.accept');

// Numero de telephone de qui est entre par Google : ce chemin ne demande rien, et
// sans numero on ne peut joindre personne pour recueillir un retour.
Route::middleware('auth')->group(function () {
    Route::post('telephone', [\App\Http\Controllers\PhoneNumberController::class, 'store'])->name('phone.store');
    Route::post('telephone/plus-tard', [\App\Http\Controllers\PhoneNumberController::class, 'dismiss'])->name('phone.dismiss');
});

// Un professeur ouvre son espace lui-meme : classes, codes d'invitation et suivi.
// La creation d'un centre etait reservee au super-administrateur, donc un enseignant
// devait nous ecrire et attendre avant de pouvoir suivre le moindre eleve.
Route::middleware('auth')->group(function () {
    Route::get('enseigner', [\App\Http\Controllers\TeacherSpaceController::class, 'show'])->name('teach.open');
    Route::post('enseigner', [\App\Http\Controllers\TeacherSpaceController::class, 'store'])->name('teach.open.store');
});

// ───────────────────────── B2B « Centre de langue » ─────────────────────────
// Super-admin : création/gestion manuelle des centres (réservé role super_admin).
Route::middleware(['auth', EnsureSuperAdmin::class])
    ->prefix('admin')->name('admin.')->group(function () {
        Route::get('centers', [CenterController::class, 'index'])->name('centers.index');
        Route::get('centers/create', [CenterController::class, 'create'])->name('centers.create');
        Route::post('centers', [CenterController::class, 'store'])->name('centers.store');
        Route::get('centers/{center}', [CenterController::class, 'show'])->name('centers.show');
        Route::patch('centers/{center}', [CenterController::class, 'update'])->name('centers.update');
        Route::delete('centers/{center}', [CenterController::class, 'destroy'])->name('centers.destroy');
    });

// Staff du centre (center_admin + teacher). Le middleware injecte le centre courant.
Route::middleware(['auth', EnsureCenterStaff::class])
    ->prefix('center')->name('center.')->group(function () {
        Route::get('/', App\Http\Controllers\Center\DashboardController::class)->name('dashboard');

        // Classes
        Route::get('classes', [ClassroomController::class, 'index'])->name('classes.index');
        Route::post('classes', [ClassroomController::class, 'store'])->name('classes.store');
        Route::get('classes/{classroom}', [ClassroomController::class, 'show'])->name('classes.show');
        Route::patch('classes/{classroom}', [ClassroomController::class, 'update'])->name('classes.update');
        Route::post('classes/{classroom}/regenerate-code', [ClassroomController::class, 'regenerateCode'])->name('classes.regenerate-code');
        Route::get('classes/{classroom}/export.csv', [ProgressController::class, 'exportCsv'])->name('classes.export');
        Route::delete('classes/{classroom}', [ClassroomController::class, 'archive'])->name('classes.archive');
        Route::delete('classes/{classroom}/students/{user}', [StudentController::class, 'remove'])->name('classes.students.remove');

        // Élèves
        Route::get('students', [StudentController::class, 'index'])->name('students.index');
        Route::get('students/{user}', [StudentController::class, 'show'])->name('students.show');

        // Médiathèque (upload images / audio en local)
        Route::get('media', [MediaController::class, 'index'])->name('media.index');
        Route::post('media', [MediaController::class, 'store'])->name('media.store');
        Route::delete('media/{medium}', [MediaController::class, 'destroy'])->name('media.destroy');

        // Builder de contenu (manuel + IA assistée)
        Route::get('exercises', [ExerciseBuilderController::class, 'index'])->name('exercises.index');
        Route::get('exercises/create', [ExerciseBuilderController::class, 'create'])->name('exercises.create');
        Route::post('exercises/ai-draft', [ExerciseBuilderController::class, 'aiDraft'])->name('exercises.ai-draft');
        Route::post('exercises', [ExerciseBuilderController::class, 'store'])->name('exercises.store');
        Route::get('exercises/{exercise}/edit', [ExerciseBuilderController::class, 'edit'])->name('exercises.edit');
        Route::patch('exercises/{exercise}', [ExerciseBuilderController::class, 'update'])->name('exercises.update');

        // Devoirs / assignations
        Route::get('assignments', [AssignmentController::class, 'index'])->name('assignments.index');
        Route::get('assignments/create', [AssignmentController::class, 'create'])->name('assignments.create');
        Route::post('assignments', [AssignmentController::class, 'store'])->name('assignments.store');
        Route::get('assignments/{assignment}', [AssignmentController::class, 'show'])->name('assignments.show');
        Route::delete('assignments/{assignment}', [AssignmentController::class, 'destroy'])->name('assignments.destroy');
    });

// Élève — rejoindre un centre via code d'invitation.
Route::middleware('auth')->group(function () {
    Route::get('join', [JoinCenterController::class, 'show'])->name('center.join');
    Route::post('join', [JoinCenterController::class, 'store'])->name('center.join.store');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';

// Web Push subscription management
Route::middleware('auth')->group(function () {
    Route::post('push/subscribe', [PushSubscriptionController::class, 'store'])->name('push.subscribe');
    Route::delete('push/unsubscribe', [PushSubscriptionController::class, 'destroy'])->name('push.unsubscribe');
});
