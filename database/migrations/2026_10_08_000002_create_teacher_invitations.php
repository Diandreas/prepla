<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Invitations d'enseignants.
     *
     * Faire entrer un professeur demandait soit qu'il s'inscrive et tâtonne, soit
     * qu'on lui fabrique un compte et qu'on se passe son mot de passe de main en
     * main. Un lien à usage unique règle les deux : il clique, il choisit SON mot de
     * passe, et son espace plus sa première classe existent déjà à l'arrivée.
     *
     * Le jeton n'est stocké que haché : une fuite de la base ne donne aucun lien
     * utilisable.
     */
    public function up(): void
    {
        Schema::create('teacher_invitations', function (Blueprint $table) {
            $table->id();
            $table->string('token_hash', 64)->unique();
            $table->string('name');
            $table->string('email');
            // Nouvel espace à créer, ou espace existant à rejoindre comme professeur.
            $table->foreignId('center_id')->nullable()->constrained('language_centers')->nullOnDelete();
            $table->string('role', 20)->default('center_admin');
            $table->string('space_name')->nullable();
            $table->string('classroom_name')->nullable();
            $table->string('level', 10)->nullable();
            $table->foreignId('exam_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('invited_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();

            $table->index(['email', 'accepted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_invitations');
    }
};
