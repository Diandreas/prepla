<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // exam_blueprints: one row per exam+level+variant combination
        // e.g. DELF A1, DELF B2, IELTS Academic, IELTS General, JLPT N5, HSK 3...
        Schema::create('exam_blueprints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->string('level')->nullable();           // e.g. 'A1', 'N5', 'HSK 3', null for single-level exams
            $table->string('variant')->nullable();          // e.g. 'academic', 'general', 'b2-first'
            $table->string('name');                         // e.g. 'DELF A1', 'IELTS Academic', 'JLPT N5'
            $table->integer('total_duration_minutes');
            $table->json('scoring_config');                 // {type, min, max, pass_mark, ...}
            $table->json('sections_config');                // Full structure from config/exams/ for this level
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['exam_id', 'level', 'variant']);
        });

        // Enhance exam_sections with missing fields
        Schema::table('exam_sections', function (Blueprint $table) {
            $table->foreignId('blueprint_id')->nullable()->after('exam_id')
                  ->constrained('exam_blueprints')->nullOnDelete();
            $table->integer('question_count')->nullable()->after('time_limit');
            $table->integer('scoring_weight')->nullable()->after('question_count');
            $table->integer('max_score')->nullable()->after('scoring_weight');
            $table->json('parts_config')->nullable()->after('max_score');
            $table->json('rubric')->nullable()->after('parts_config');
            $table->text('description')->nullable()->after('rubric');
        });

        // Enhance exercise_types with missing fields
        Schema::table('exercise_types', function (Blueprint $table) {
            $table->text('description')->nullable()->after('component_key');
            $table->json('scoring_rubric')->nullable()->after('description');
            $table->json('generation_config')->nullable()->after('scoring_rubric');
        });
    }

    public function down(): void
    {
        Schema::table('exercise_types', function (Blueprint $table) {
            $table->dropColumn(['description', 'scoring_rubric', 'generation_config']);
        });

        Schema::table('exam_sections', function (Blueprint $table) {
            $table->dropConstrainedForeignId('blueprint_id');
            $table->dropColumn(['question_count', 'scoring_weight', 'max_score', 'parts_config', 'rubric', 'description']);
        });

        Schema::dropIfExists('exam_blueprints');
    }
};
