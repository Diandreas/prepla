<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module « Live class » : les cours en direct d'une classe, dans Google Meet.
 *
 * PrePla n'heberge aucun flux video : la salle Meet est creee par l'API Google
 * (spaces.create) au nom de l'enseignant connecte. PrePla garde seulement le
 * lien, le planning, et l'interactivite legere autour du cours (main levee,
 * questions, sondages, reactions, ressources, chat, notes), lue par sondage HTTP.
 *
 * center_google_accounts : le compte Google d'un membre de l'equipe (jetons
 * chiffres). Le scope Meet s'ajoute au meme ecran de consentement que Drive.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('center_google_accounts')) {
            Schema::create('center_google_accounts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('center_id')->constrained('language_centers')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('google_email')->nullable();
                $table->text('access_token');               // chiffre (cast encrypted)
                $table->text('refresh_token')->nullable();  // chiffre
                $table->timestamp('expires_at')->nullable();
                $table->text('scopes')->nullable();
                $table->timestamps();
                $table->unique(['center_id', 'user_id']);
            });
        }

        Schema::create('live_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id')->constrained('language_centers')->cascadeOnDelete();
            $table->foreignId('classroom_id')->constrained('classrooms')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            // Une serie recurrente (« chaque mardi 18h ») partage cet identifiant
            // et la meme salle Meet (un espace Meet se reutilise).
            $table->string('series_uuid', 36)->nullable()->index();
            $table->string('title', 160);
            $table->text('agenda')->nullable();
            $table->timestamp('starts_at');
            $table->unsignedSmallInteger('duration_minutes')->default(60);
            $table->string('access_type', 12)->default('OPEN'); // OPEN | TRUSTED
            $table->string('meet_space_name')->nullable(); // spaces/xxxx
            $table->string('meeting_uri')->nullable();
            $table->string('meeting_code', 32)->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('recording_url', 500)->nullable();
            $table->text('summary')->nullable();
            $table->foreignId('assignment_id')->nullable()->constrained('assignments')->nullOnDelete();
            $table->boolean('replay_published')->default(false);
            // Presence lue chez Google apres le cours (conferenceRecords/participants).
            $table->json('google_attendance')->nullable();
            $table->timestamps();
            $table->index(['classroom_id', 'starts_at']);
        });

        Schema::create('live_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('live_session_id')->constrained('live_sessions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('joined_at')->nullable();    // a clique « Rejoindre »
            $table->timestamp('last_seen_at')->nullable(); // dernier sondage de la page
            $table->timestamp('hand_raised_at')->nullable();
            $table->text('notes')->nullable(); // notes personnelles de l'eleve
            $table->timestamps();
            $table->unique(['live_session_id', 'user_id']);
        });

        // Chat, questions, reactions, ressources partagees : un fil par seance.
        Schema::create('live_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('live_session_id')->constrained('live_sessions')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('kind', 12); // chat | question | reaction | resource
            $table->text('body')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->timestamps();
            $table->index(['live_session_id', 'kind', 'id']);
        });

        Schema::create('live_question_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('live_message_id')->constrained('live_messages')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['live_message_id', 'user_id']);
        });

        Schema::create('live_polls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('live_session_id')->constrained('live_sessions')->cascadeOnDelete();
            $table->string('question', 300);
            $table->json('options');
            $table->unsignedTinyInteger('correct_option')->nullable(); // quiz : la bonne reponse
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('live_poll_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('live_poll_id')->constrained('live_polls')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('option');
            $table->timestamps();
            $table->unique(['live_poll_id', 'user_id']);
        });
    }

    public function down(): void
    {
        foreach (['live_poll_answers', 'live_polls', 'live_question_votes', 'live_messages', 'live_participants', 'live_sessions'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
