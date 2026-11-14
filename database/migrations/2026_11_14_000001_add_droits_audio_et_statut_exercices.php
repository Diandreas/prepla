<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Audios d'examen du centre et banque de sujets.
 *
 * - center_media : un nom de piste lisible (« TCF CO piste 12 »), la source et
 *   les droits du fichier, et la date ou quelqu'un a certifie pouvoir le
 *   diffuser aux eleves. Un centre qui met en ligne un sujet sous licence doit
 *   pouvoir dire d'ou il vient.
 * - exercises : un statut brouillon / valide pour les exercices du centre
 *   (null pour le catalogue PrePla), et l'exercice d'origine d'une copie.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('center_media', function (Blueprint $table) {
            $table->string('title', 160)->nullable()->after('original_name');
            $table->string('source_rights', 255)->nullable()->after('title');
            $table->timestamp('rights_certified_at')->nullable()->after('source_rights');
        });

        Schema::table('exercises', function (Blueprint $table) {
            $table->string('center_status', 16)->nullable()->after('center_id');
            $table->unsignedBigInteger('copied_from_id')->nullable()->after('center_status');
        });

        // Les exercices deja crees par les centres etaient tous en service : ils
        // restent valides, rien ne disparait des devoirs.
        DB::table('exercises')->whereNotNull('center_id')->update(['center_status' => 'valide']);
    }

    public function down(): void
    {
        Schema::table('exercises', function (Blueprint $table) {
            $table->dropColumn(['center_status', 'copied_from_id']);
        });
        Schema::table('center_media', function (Blueprint $table) {
            $table->dropColumn(['title', 'source_rights', 'rights_certified_at']);
        });
    }
};
