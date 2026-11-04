<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Acces vendus par un centre en formule « revente » a ses eleves (lot C).
     *
     * Une ligne par acces ouvert, jamais supprimee : couper un acces remplit
     * revoked_at et revoked_by. C'est aussi le journal de qui ouvre et qui coupe,
     * et la base du releve mensuel (30 % des montants ouverts dans le mois).
     */
    public function up(): void
    {
        Schema::create('center_access_grants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id')->constrained('language_centers')->cascadeOnDelete();
            // L'eleve peut supprimer son compte : la ligne reste (journal et base du
            // releve), user_id passe a null et eleve_nom garde son nom.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('eleve_nom', 255)->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            // Montant encaisse par le centre, en unite entiere (XAF n'a pas de centimes).
            $table->unsignedInteger('amount')->default(0);
            $table->string('currency', 3)->default('XAF');
            $table->string('payment_method', 20); // mobile_money | cash | autre
            $table->string('reference', 120)->nullable();
            $table->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note', 255)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'ends_at']);
            $table->index(['center_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('center_access_grants');
    }
};
