<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La salle et l'horaire d'une classe : un centre range ses groupes par salle
 * (« Salle 2 ») et par creneau (« Lun et mer 17 h – 19 h »). Texte libre,
 * facultatif : rien ne change pour les classes existantes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classrooms', function (Blueprint $table) {
            $table->string('room', 60)->nullable()->after('level');
            $table->string('schedule', 120)->nullable()->after('room');
        });
    }

    public function down(): void
    {
        Schema::table('classrooms', function (Blueprint $table) {
            $table->dropColumn(['room', 'schedule']);
        });
    }
};
