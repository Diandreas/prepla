<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * La marque d'un centre (lot A) : nom affiche, logo, deux couleurs, et la
     * marque blanche. Toutes facultatives : un centre qui n'en remplit aucune
     * garde la marque PrePla, exactement comme avant (centres existants compris).
     */
    public function up(): void
    {
        Schema::table('language_centers', function (Blueprint $table) {
            $table->string('display_name', 80)->nullable();
            // Chemin sur le disque public, nom genere (UUID) : jamais celui envoye.
            $table->string('logo_path')->nullable();
            // Couleurs hexadecimales « #RRGGBB », contraste verifie a l'enregistrement.
            $table->string('brand_primary', 7)->nullable();
            $table->string('brand_accent', 7)->nullable();
            $table->boolean('hide_prepla_branding')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('language_centers', function (Blueprint $table) {
            $table->dropColumn(['display_name', 'logo_path', 'brand_primary', 'brand_accent', 'hide_prepla_branding']);
        });
    }
};
