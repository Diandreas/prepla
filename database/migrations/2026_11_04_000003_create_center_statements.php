<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Releves mensuels par centre (commande centres:releves).
     *
     * Une ligne par centre, mois et nature : revente (lot C, part PrePla des acces
     * vendus), commission (lot D, parrainage) et paliers (lot E, eleves actifs).
     * L'unicite rend le calcul idempotent : le relancer met a jour le brouillon,
     * et un releve envoye ou paye n'est plus jamais recalcule.
     */
    public function up(): void
    {
        Schema::create('center_statements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id')->constrained('language_centers')->cascadeOnDelete();
            $table->string('periode', 7); // YYYY-MM, mois civil a l'heure de Douala
            $table->string('nature', 20); // revente | commission | paliers
            $table->integer('montant_brut')->default(0);
            $table->integer('part_prepla')->default(0);
            $table->json('lignes')->nullable();
            $table->string('statut', 20)->default('brouillon'); // brouillon | envoye | paye
            $table->timestamp('computed_at')->nullable();
            $table->timestamps();

            $table->unique(['center_id', 'periode', 'nature']);
            $table->index(['periode', 'nature']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('center_statements');
    }
};
