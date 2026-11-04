<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le parcours d'une classe, construit par son centre : une suite ordonnee
 * d'exercices (ceux du centre, ou ceux du vivier PrePla de l'examen de la classe).
 *
 * La progression de l'eleve n'a pas de table : elle se deduit de ses tentatives
 * (user_exercise_attempts). Une etape est faite quand l'eleve a tente son
 * exercice, avec au moins seuil_reussite % de reussite si un seuil est fixe.
 * Table nouvelle : rien d'existant n'est touche.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classroom_path_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classroom_id')->constrained('classrooms')->cascadeOnDelete();
            $table->unsignedInteger('position');
            $table->foreignId('exercise_id')->constrained('exercises')->cascadeOnDelete();
            $table->string('titre', 120)->nullable();
            $table->text('consigne')->nullable();
            $table->unsignedTinyInteger('seuil_reussite')->nullable(); // en %, de 1 a 100
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['classroom_id', 'position']);
            // Un exercice une seule fois par parcours : la progression se lit
            // par exercice, deux etapes identiques seraient validees ensemble.
            $table->unique(['classroom_id', 'exercise_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classroom_path_steps');
    }
};
