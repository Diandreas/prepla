<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Createur de parcours du centre : parcours -> modules -> seances -> blocs.
 *
 * - course_paths : un parcours, rattache a une classe ou modele reutilisable
 *   (classroom_id nul), duplicable vers d'autres classes du meme centre ;
 * - course_modules : chapitres ordonnes ;
 * - course_sessions : seances ordonnees, avec leurs regles de deroulement ;
 * - course_blocks : blocs ordonnes (texte, video, audio, quiz...), contenu en JSON ;
 * - course_block_progress : la completion de chaque bloc par chaque eleve.
 *
 * Reprise de l'existant : chaque classe qui avait un parcours d'etapes
 * (classroom_path_steps) recoit un parcours publie equivalent, une seance par
 * etape avec son bloc « exercice » et le meme seuil. La progression se deduisant
 * des tentatives, rien n'est perdu. classroom_path_steps n'est pas touchee.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_paths', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id')->constrained('language_centers')->cascadeOnDelete();
            $table->foreignId('classroom_id')->nullable()->constrained('classrooms')->cascadeOnDelete();
            $table->string('titre', 160);
            $table->text('description')->nullable();
            $table->string('statut', 20)->default('brouillon'); // brouillon | publie
            $table->foreignId('source_path_id')->nullable()->constrained('course_paths')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['center_id', 'classroom_id']);
        });

        Schema::create('course_modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_path_id')->constrained('course_paths')->cascadeOnDelete();
            $table->unsignedInteger('position');
            $table->string('titre', 160);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->index(['course_path_id', 'position']);
        });

        Schema::create('course_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_module_id')->constrained('course_modules')->cascadeOnDelete();
            $table->unsignedInteger('position');
            $table->string('titre', 160);
            $table->text('description')->nullable();
            $table->string('statut', 20)->default('brouillon'); // brouillon | publie
            $table->boolean('obligatoire')->default(true);
            $table->boolean('blocs_ordre_impose')->default(false);
            $table->unsignedSmallInteger('duree_minutes')->nullable();
            // libre | completion (seance precedente finie) | quiz (quiz de la precedente reussis) | date
            $table->string('deblocage', 20)->default('libre');
            $table->unsignedTinyInteger('seuil_quiz')->nullable();
            $table->dateTime('deblocage_at')->nullable();
            $table->dateTime('debut_at')->nullable();
            $table->dateTime('limite_at')->nullable();
            $table->timestamps();
            $table->index(['course_module_id', 'position']);
        });

        Schema::create('course_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_session_id')->constrained('course_sessions')->cascadeOnDelete();
            $table->unsignedInteger('position');
            $table->string('type', 30);
            $table->string('titre', 160)->nullable();
            $table->json('contenu')->nullable();
            $table->boolean('obligatoire')->default(true);
            $table->timestamps();
            $table->index(['course_session_id', 'position']);
        });

        Schema::create('course_block_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_block_id')->constrained('course_blocks')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->dateTime('termine_at')->nullable();
            $table->unsignedTinyInteger('score')->nullable(); // en %
            $table->unsignedTinyInteger('pourcentage_vu')->default(0);
            $table->unsignedSmallInteger('ecoutes')->default(0);
            $table->json('reponse')->nullable();
            $table->string('correction_statut', 20)->nullable(); // a_corriger | corrige
            $table->json('correction_ia')->nullable();
            $table->text('commentaire_enseignant')->nullable();
            $table->unsignedTinyInteger('note_enseignant')->nullable(); // sur 20
            $table->foreignId('corrige_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['course_block_id', 'user_id']);
            $table->index(['user_id', 'updated_at']);
        });

        $this->reprendreLesParcoursDEtapes();
    }

    private function reprendreLesParcoursDEtapes(): void
    {
        if (! Schema::hasTable('classroom_path_steps')) {
            return;
        }
        $maintenant = now();
        $parClasse = DB::table('classroom_path_steps')->orderBy('classroom_id')->orderBy('position')->orderBy('id')->get()->groupBy('classroom_id');

        foreach ($parClasse as $classeId => $etapes) {
            $classe = DB::table('classrooms')->where('id', $classeId)->first();
            if (! $classe) {
                continue;
            }
            $parcoursId = DB::table('course_paths')->insertGetId([
                'center_id' => $classe->center_id, 'classroom_id' => $classe->id,
                'titre' => 'Parcours de la classe', 'statut' => 'publie',
                'created_by' => $etapes->first()->created_by,
                'created_at' => $maintenant, 'updated_at' => $maintenant,
            ]);
            $moduleId = DB::table('course_modules')->insertGetId([
                'course_path_id' => $parcoursId, 'position' => 1, 'titre' => 'Module 1',
                'created_at' => $maintenant, 'updated_at' => $maintenant,
            ]);
            foreach ($etapes->values() as $i => $etape) {
                $seanceId = DB::table('course_sessions')->insertGetId([
                    'course_module_id' => $moduleId, 'position' => $i + 1,
                    'titre' => $etape->titre ?: 'Étape '.($i + 1),
                    'statut' => 'publie', 'obligatoire' => true, 'blocs_ordre_impose' => false,
                    // L'ancien parcours se suivait dans l'ordre.
                    'deblocage' => $i === 0 ? 'libre' : 'completion',
                    'created_at' => $maintenant, 'updated_at' => $maintenant,
                ]);
                $position = 1;
                if ($etape->consigne) {
                    DB::table('course_blocks')->insert([
                        'course_session_id' => $seanceId, 'position' => $position++, 'type' => 'consigne',
                        'contenu' => json_encode(['texte' => $etape->consigne]), 'obligatoire' => false,
                        'created_at' => $maintenant, 'updated_at' => $maintenant,
                    ]);
                }
                DB::table('course_blocks')->insert([
                    'course_session_id' => $seanceId, 'position' => $position, 'type' => 'exercice',
                    'contenu' => json_encode(['exercise_id' => (int) $etape->exercise_id, 'seuil' => $etape->seuil_reussite]),
                    'obligatoire' => true,
                    'created_at' => $maintenant, 'updated_at' => $maintenant,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('course_block_progress');
        Schema::dropIfExists('course_blocks');
        Schema::dropIfExists('course_sessions');
        Schema::dropIfExists('course_modules');
        Schema::dropIfExists('course_paths');
    }
};
