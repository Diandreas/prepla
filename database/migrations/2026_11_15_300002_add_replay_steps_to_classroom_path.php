<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le replay d'un live devient une etape possible du parcours de classe.
 *
 * Une etape porte soit un exercice (exercise_id), soit le replay d'un live de
 * la meme classe (live_session_id). exercise_id devient donc facultatif.
 * Le replay est « fait » quand l'eleve a assiste au live (joined_at) ou ouvert
 * le replay (live_participants.replay_vu_at).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classroom_path_steps', function (Blueprint $table) {
            $table->foreignId('exercise_id')->nullable()->change();
            $table->foreignId('live_session_id')->nullable()->after('exercise_id')
                ->constrained('live_sessions')->cascadeOnDelete();
            $table->unique(['classroom_id', 'live_session_id']);
        });

        Schema::table('live_participants', function (Blueprint $table) {
            $table->timestamp('replay_vu_at')->nullable()->after('joined_at');
        });
    }

    public function down(): void
    {
        Schema::table('live_participants', function (Blueprint $table) {
            $table->dropColumn('replay_vu_at');
        });
        Schema::table('classroom_path_steps', function (Blueprint $table) {
            $table->dropUnique(['classroom_id', 'live_session_id']);
            $table->dropConstrainedForeignId('live_session_id');
        });
    }
};
