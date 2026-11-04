<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le decompte des eleves actifs cherche les tentatives d'un eleve dans un mois :
 * sans index sur created_at, chaque centre parcourait toute la table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_exercise_attempts', function (Blueprint $table) {
            $table->index(['user_id', 'created_at'], 'user_exercise_attempts_user_id_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('user_exercise_attempts', function (Blueprint $table) {
            $table->dropIndex('user_exercise_attempts_user_id_created_at_index');
        });
    }
};
