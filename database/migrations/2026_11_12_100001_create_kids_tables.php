<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PrePla Kids : les enfants d'un parent, et leurs parties.
 *
 * L'enfant n'a pas de compte : ni e-mail, ni mot de passe. Il joue depuis la
 * session de son parent, qui l'a choisi sur « Qui joue ? ». On ne garde de lui
 * que son prenom, son age et son avatar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kids_enfants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->constrained('users')->cascadeOnDelete();
            $table->string('prenom', 40);
            $table->unsignedTinyInteger('age');
            $table->string('avatar', 16);
            // La langue qu'il apprend (english | french | german).
            $table->string('langue', 16);
            // La langue des consignes : celle qu'il comprend deja (fr | en).
            $table->string('langue_aide', 2)->default('fr');
            $table->unsignedInteger('etoiles')->default(0);
            $table->unsignedSmallInteger('serie')->default(0);
            $table->date('dernier_jour')->nullable();
            $table->timestamps();
        });

        Schema::create('kids_resultats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enfant_id')->constrained('kids_enfants')->cascadeOnDelete();
            $table->string('theme', 32);
            $table->string('activite', 16);
            $table->unsignedTinyInteger('score');
            $table->unsignedTinyInteger('etoiles');
            $table->unsignedSmallInteger('duree')->default(0);
            // Les mots manques : le parent voit ce qui reste a travailler.
            $table->json('mots_rates')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->index(['enfant_id', 'theme']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kids_resultats');
        Schema::dropIfExists('kids_enfants');
    }
};
