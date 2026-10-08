<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Le numéro de téléphone, pour pouvoir joindre les apprenants et recueillir
     * leurs retours. Il est demandé à l'inscription ; ceux qui entrent par Google
     * ne voient jamais ce formulaire, on le leur demande donc une fois ensuite —
     * `phone_prompted_at` retient qu'on a posé la question, pour ne pas la reposer
     * à chaque visite.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 32)->nullable()->after('email');
            $table->timestamp('phone_prompted_at')->nullable()->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone', 'phone_prompted_at']);
        });
    }
};
