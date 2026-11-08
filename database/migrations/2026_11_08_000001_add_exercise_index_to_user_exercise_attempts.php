<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * La reussite moyenne de chaque exercice classe le vivier par difficulte (voir
 * PalierDeDifficulte). Sans index sur exercise_id, ce calcul relisait toutes les
 * tentatives pour chaque exercice : pres d'une seconde a chaque ouverture.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_exercise_attempts', function (Blueprint $table) {
            $table->index(['exercise_id', 'accuracy_percent'], 'user_exercise_attempts_exercise_id_accuracy_index');
        });
    }

    public function down(): void
    {
        Schema::table('user_exercise_attempts', function (Blueprint $table) {
            $table->dropIndex('user_exercise_attempts_exercise_id_accuracy_index');
        });
    }
};
