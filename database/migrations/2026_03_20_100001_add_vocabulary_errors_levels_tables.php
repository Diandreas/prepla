<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─── User Vocabulary (mots appris) ───
        Schema::create('user_vocabulary', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('word');
            $table->string('language_slug');
            $table->text('definition')->nullable();
            $table->string('ipa')->nullable();
            $table->string('audio_url')->nullable();
            $table->json('examples')->nullable();
            $table->string('source')->default('exercise'); // exercise, manual, dictionary
            $table->unsignedTinyInteger('mastery_level')->default(0); // 0-5 (SM-2)
            $table->decimal('ease_factor', 4, 2)->default(2.50);
            $table->unsignedInteger('interval_days')->default(1);
            $table->timestamp('next_review_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'word', 'language_slug']);
            $table->index(['user_id', 'next_review_at']);
        });

        // ─── User Errors (erreurs pour révision) ───
        Schema::create('user_errors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exercise_id')->nullable()->constrained()->nullOnDelete();
            $table->string('question_id');
            $table->text('question_text');
            $table->text('user_answer');
            $table->text('correct_answer');
            $table->string('skill_type');
            $table->string('exercise_type_slug');
            $table->string('error_category')->nullable();
            $table->unsignedInteger('reviewed_count')->default(0);
            $table->timestamp('last_reviewed_at')->nullable();
            $table->boolean('mastered')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'mastered']);
            $table->index(['user_id', 'skill_type']);
        });

        // ─── Level Assessments (historique d'avancement) ───
        Schema::create('level_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->string('assessed_level');
            $table->string('previous_level')->nullable();
            $table->string('assessment_type'); // placement, boss_test, periodic
            $table->json('score_details')->nullable();
            $table->timestamp('assessed_at');
            $table->timestamps();

            $table->index(['user_id', 'exam_id']);
        });

        // ─── Enhance learning_path_nodes ───
        Schema::table('learning_path_nodes', function (Blueprint $table) {
            $table->foreignId('blueprint_id')->nullable()->after('exam_id')
                  ->constrained('exam_blueprints')->nullOnDelete();
            $table->json('prerequisites')->nullable()->after('exercise_ids');
            $table->unsignedInteger('mastery_score')->default(70)->after('prerequisites');
            $table->unsignedInteger('exercises_count')->default(3)->after('mastery_score');
        });
    }

    public function down(): void
    {
        Schema::table('learning_path_nodes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('blueprint_id');
            $table->dropColumn(['prerequisites', 'mastery_score', 'exercises_count']);
        });

        Schema::dropIfExists('level_assessments');
        Schema::dropIfExists('user_errors');
        Schema::dropIfExists('user_vocabulary');
    }
};
