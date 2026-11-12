<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Delegues : des personnes (delegues de salle, ambassadeurs) qui font connaitre
 * PrePla et touchent un pourcentage sur les abonnements des etudiants qu'elles
 * amenent. Leur code donne aussi une reduction a l'etudiant.
 *
 * Le rattachement d'un compte a un delegue est pose une seule fois ; chaque
 * paiement Mobile Money de ce compte ecrit ensuite une commission, versee a la
 * main et marquee comme telle.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delegues', function (Blueprint $table) {
            $table->id();
            // Son propre compte PrePla, s'il en a un : il y suit ses resultats.
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('nom');
            $table->string('telephone', 30)->nullable();
            $table->string('code', 20)->unique();
            // Salle, classe, etablissement : ce qui permet de le reconnaitre.
            $table->string('salle')->nullable();
            $table->unsignedTinyInteger('taux')->default(20);
            $table->unsignedTinyInteger('reduction')->default(10);
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('delegue_id')->nullable()->constrained('delegues')->nullOnDelete();
            $table->timestamp('delegue_at')->nullable();
        });

        Schema::create('commissions_delegues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delegue_id')->constrained('delegues')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            // Une activation ne rapporte qu'une fois : la base y veille.
            $table->foreignId('activation_manuelle_id')->nullable()->unique()->constrained('activations_manuelles')->nullOnDelete();
            $table->unsignedInteger('montant');
            $table->unsignedTinyInteger('taux');
            $table->unsignedInteger('commission');
            $table->string('periode', 7);
            $table->timestamp('versee_le')->nullable();
            $table->timestamp('annulee_le')->nullable();
            $table->timestamps();
            $table->index(['delegue_id', 'versee_le']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commissions_delegues');
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('delegue_id');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('delegue_at');
        });
        Schema::dropIfExists('delegues');
    }
};
