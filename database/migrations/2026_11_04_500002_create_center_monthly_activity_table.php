<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le nombre d'eleves actifs de chaque centre, mois par mois, et son palier
 * (A1, A2, A3 ou reseau). Ecrit par la commande centres:actifs ; une ligne par
 * centre et par mois, jamais recalculee sans --forcer.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('center_monthly_activity', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id')->constrained('language_centers')->cascadeOnDelete();
            $table->string('periode', 7); // YYYY-MM, mois civil a Africa/Douala
            $table->unsignedInteger('active_students')->default(0);
            $table->string('palier', 16); // A1 | A2 | A3 | reseau
            $table->timestamp('computed_at');
            $table->timestamps();

            $table->unique(['center_id', 'periode']);
            $table->index('periode');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('center_monthly_activity');
    }
};
