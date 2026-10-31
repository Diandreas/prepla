<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Une personne peut gerer PLUSIEURS centres.
 *
 * La premiere version l'interdisait par la base — un seul centre par
 * utilisateur, « le pivot permettra d'aller au multi-centres plus tard ». Un
 * responsable qui ouvrait un deuxieme etablissement devait se creer un second
 * compte, et un enseignant deja inscrit ne pouvait pas en rejoindre un autre.
 *
 * Ce qui reste interdit : appartenir DEUX FOIS au meme centre. Un eleve, lui,
 * reste dans un seul etablissement — la regle est tenue par le code d'adhesion,
 * qui le dit a l'eleve, et non plus par la base.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('center_user', function (Blueprint $table) {
            // L'index simple d'abord : la cle etrangere user_id doit rester
            // indexee une fois l'unicite retiree (MySQL le refuserait sinon).
            $table->index('user_id', 'center_user_user_id_index');
            $table->unique(['center_id', 'user_id'], 'center_user_center_id_user_id_unique');
            $table->dropUnique('center_user_user_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('center_user', function (Blueprint $table) {
            // Ne repasse qu'a condition que personne n'appartienne a deux centres.
            $table->unique('user_id', 'center_user_user_id_unique');
            $table->dropUnique('center_user_center_id_user_id_unique');
            $table->dropIndex('center_user_user_id_index');
        });
    }
};
