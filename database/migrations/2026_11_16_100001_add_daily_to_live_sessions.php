<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Live class : plusieurs fournisseurs video.
 * provider : daily (salle Daily.co integree, par defaut) | google (Meet cree
 * par l'API) | lien (lien de reunion colle). daily_room : nom de la salle Daily.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('live_sessions', function (Blueprint $table) {
            $table->string('provider', 12)->nullable()->after('access_type');
            $table->string('daily_room', 64)->nullable()->after('provider');
        });
    }

    public function down(): void
    {
        Schema::table('live_sessions', function (Blueprint $table) {
            $table->dropColumn(['provider', 'daily_room']);
        });
    }
};
