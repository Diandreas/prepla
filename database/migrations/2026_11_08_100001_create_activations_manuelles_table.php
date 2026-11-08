<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Acces PrePla Plus paye par Mobile Money et ouvert a la main. La date de fin
 * vit sur le compte (premium_until, lue par hasPremiumAccess) ; chaque
 * activation reste ecrite : qui l'a faite, pour combien, par quel moyen.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('premium_until')->nullable();
        });

        Schema::create('activations_manuelles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // Le compte qui a active : garde meme si ce compte disparait.
            $table->foreignId('activated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('email', 255);
            $table->string('formule', 20); // mensuel | trimestriel | annuel | jours
            $table->unsignedInteger('jours');
            $table->string('moyen', 20); // orange | mtn | autre
            $table->unsignedInteger('montant')->nullable(); // FCFA
            $table->string('reference', 120)->nullable();
            $table->timestamp('debut');
            $table->timestamp('fin');
            $table->timestamp('annulee_le')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activations_manuelles');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('premium_until');
        });
    }
};
