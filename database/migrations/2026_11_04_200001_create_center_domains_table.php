<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sous-domaines et domaines propres des centres (lot B).
 *
 * host est UNIQUE et toujours en minuscules (App\Services\Center\Domaines::normaliser) :
 * deux centres ne peuvent jamais revendiquer la meme adresse. Un hote n'est
 * resolu qu'une fois verified_at rempli ; un sous-domaine sous notre racine
 * l'est des sa creation, un domaine propre apres verification DNS (TXT
 * _prepla.<hote> portant verification_token, ou CNAME vers la cible).
 *
 * Table nouvelle : aucun centre existant n'est touche.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('center_domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id')->constrained('language_centers')->cascadeOnDelete();
            $table->string('host', 253)->unique();
            $table->string('type', 16); // subdomain | custom
            $table->string('verification_token', 64);
            $table->timestamp('verified_at')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->index('center_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('center_domains');
    }
};
