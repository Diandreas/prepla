<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * La formule commerciale d'un centre (lot C).
     *
     * - paliers : le centre paie PrePla selon ses eleves actifs, ses eleves sont
     *   illimites. C'est le cas de tous les centres existants et de ceux crees par
     *   le super administrateur : rien ne change pour eux.
     * - revente : ses eleves suivent le quota gratuit, sauf ceux a qui le centre a
     *   ouvert un acces (center_access_grants). Activee par le super administrateur.
     * - libre_service : espace ouvert seul sur /enseigner. Deja marque par
     *   settings.libre_service ; la colonne le recopie, la regle ne change pas.
     *
     * Montants en XAF, entiers (pas de centimes).
     */
    public function up(): void
    {
        Schema::table('language_centers', function (Blueprint $table) {
            $table->string('formule', 20)->default('paliers');
            $table->unsignedInteger('prix_acces')->nullable();
            $table->unsignedSmallInteger('duree_acces_jours')->nullable();
        });

        // Les espaces libre-service existants : lus en PHP plutot qu'en JSON SQL,
        // pour ne dependre d'aucun moteur.
        DB::table('language_centers')->select(['id', 'settings'])->orderBy('id')->each(function ($centre) {
            $settings = json_decode((string) $centre->settings, true);
            if (is_array($settings) && ! empty($settings['libre_service'])) {
                DB::table('language_centers')->where('id', $centre->id)->update(['formule' => 'libre_service']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('language_centers', function (Blueprint $table) {
            $table->dropColumn(['formule', 'prix_acces', 'duree_acces_jours']);
        });
    }
};
