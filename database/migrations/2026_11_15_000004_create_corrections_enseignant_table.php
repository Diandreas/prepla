<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Correction humaine par le centre : un enseignant relit une redaction ou une
 * prise de parole, donne sa note selon le bareme de l'examen et un commentaire.
 * Les audios des prises de parole sont desormais conserves pour qu'il puisse
 * les ecouter (ils etaient transcrits puis oublies).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_exercise_attempts', function (Blueprint $table) {
            // question_id => chemin du fichier audio sur le disque prive.
            $table->json('audios')->nullable()->after('feedback');
        });

        Schema::create('corrections_enseignant', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_id')->constrained('user_exercise_attempts')->cascadeOnDelete();
            $table->foreignId('center_id')->constrained('language_centers')->cascadeOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained('users')->nullOnDelete();
            // Les quatre dimensions communes, sur 9 (comme la correction IA).
            $table->json('sous_notes');
            $table->decimal('note', 4, 2);
            // La note traduite dans l'echelle de l'examen (GrilleOfficielleEcrit).
            $table->json('grille')->nullable();
            $table->text('commentaire')->nullable();
            $table->timestamps();

            // Un eleve inscrit dans deux centres peut etre corrige par chacun :
            // une correction par centre, jamais ecrasee par l'autre.
            $table->unique(['attempt_id', 'center_id']);
            $table->index(['center_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('corrections_enseignant');
        Schema::table('user_exercise_attempts', function (Blueprint $table) {
            $table->dropColumn('audios');
        });
    }
};
