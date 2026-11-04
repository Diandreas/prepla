<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Les periodes d'appartenance aux centres.
 *
 * Retirer un eleve SUPPRIME sa ligne center_user (StudentController::remove) :
 * sans trace de son passage, un eleve parti le 15 disparaissait du decompte
 * des eleves actifs du mois, alors qu'il avait travaille avant de partir.
 * Chaque entree ouvre une periode, chaque sortie la ferme (CenterUserObserver).
 *
 * Remplie au depart depuis center_user : une periode ouverte par appartenance
 * actuelle, depuis joined_at (ou created_at). Les departs anterieurs a cette
 * migration sont perdus : ils n'avaient laisse aucune trace.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('center_membership_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id')->constrained('language_centers')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role'); // center_admin | teacher | student
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable(); // null : toujours membre
            $table->timestamps();

            $table->index(['center_id', 'role', 'started_at']);
            $table->index(['user_id', 'center_id']);
        });

        $maintenant = now()->format('Y-m-d H:i:s');
        DB::table('center_user')->orderBy('id')->each(function ($lien) use ($maintenant) {
            DB::table('center_membership_periods')->insert([
                'center_id' => $lien->center_id,
                'user_id' => $lien->user_id,
                'role' => $lien->role,
                'started_at' => $lien->joined_at ?? $lien->created_at ?? $maintenant,
                'ended_at' => null,
                'created_at' => $maintenant,
                'updated_at' => $maintenant,
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('center_membership_periods');
    }
};
