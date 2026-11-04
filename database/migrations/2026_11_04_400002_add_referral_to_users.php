<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Parrainage (lot D) : le centre qui a amene ce compte, et quand.
 *
 * Pose une seule fois, a la creation du compte. Si le centre est supprime, le
 * compte reste, sans parrain. Hors de $fillable : aucun formulaire ne doit
 * pouvoir le choisir.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('referred_by_center_id')->nullable()
                ->constrained('language_centers')->nullOnDelete();
            $table->timestamp('referred_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('referred_by_center_id');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('referred_at');
        });
    }
};
