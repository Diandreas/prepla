<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Offres a prix fixe (QR code d'une campagne) : un code peut imposer le prix
 * du mois (5 000 FCFA) et le prix barre affiche a cote (7 500 FCFA), au lieu
 * d'un pourcentage de reduction.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delegues', function (Blueprint $table) {
            $table->unsignedInteger('prix_mensuel')->nullable()->after('reduction');
            $table->unsignedInteger('prix_barre')->nullable()->after('prix_mensuel');
        });
    }

    public function down(): void
    {
        Schema::table('delegues', function (Blueprint $table) {
            $table->dropColumn(['prix_mensuel', 'prix_barre']);
        });
    }
};
