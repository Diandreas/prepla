<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Examen blanc compose par un centre.
 *
 * Les epreuves blanches PrePla possedent leurs exercices (exercises.mock_exam_id).
 * Un centre, lui, puise aussi dans le catalogue partage : il ne peut pas
 * s'approprier un exercice PrePla. Ses choix vivent donc dans une table a part
 * (mock_exam_items), et chaque passage d'un eleve garde son score par section
 * (mock_exam_passages) pour que le centre voie les resultats.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mock_exams', function (Blueprint $table) {
            // null = epreuve PrePla, partagee par tous.
            $table->foreignId('center_id')->nullable()->after('blueprint_id')
                ->constrained('language_centers')->cascadeOnDelete();
            $table->foreignId('creator_id')->nullable()->after('center_id')
                ->constrained('users')->nullOnDelete();
            $table->string('level', 8)->nullable()->after('creator_id');
            // { "slug de section": minutes } : le minutage regle par le centre.
            $table->json('durees_sections')->nullable()->after('description');
        });

        Schema::create('mock_exam_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mock_exam_id')->constrained('mock_exams')->cascadeOnDelete();
            $table->foreignId('exercise_id')->constrained('exercises')->cascadeOnDelete();
            $table->string('section_slug');
            $table->string('partie')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('mock_exam_passages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mock_exam_id')->constrained('mock_exams')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assignment_id')->nullable()->constrained('assignments')->nullOnDelete();
            $table->unsignedInteger('temps_passe')->default(0);
            $table->unsignedTinyInteger('score')->default(0);
            // { "slug de section": pourcentage }
            $table->json('scores_sections')->nullable();
            $table->timestamps();
            $table->index(['mock_exam_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mock_exam_passages');
        Schema::dropIfExists('mock_exam_items');
        Schema::table('mock_exams', function (Blueprint $table) {
            $table->dropConstrainedForeignId('center_id');
            $table->dropConstrainedForeignId('creator_id');
            $table->dropColumn(['level', 'durees_sections']);
        });
    }
};
