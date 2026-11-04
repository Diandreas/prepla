<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Les analyses IA d'un centre, gardees pour ne pas repayer l'IA a chaque
 * ouverture de fiche : une ligne par sujet (un eleve, une classe ou le centre).
 *
 * faits_empreinte : l'empreinte des faits envoyes. Tant qu'elle ne change pas et
 * que l'analyse a moins de 24 h, on la ressert telle quelle (App\Services\Center\AnalyseIa).
 * Table nouvelle : rien d'existant n'est touche.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('center_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id')->constrained('language_centers')->cascadeOnDelete();
            $table->string('sujet_type', 16); // eleve | classe | centre
            $table->unsignedBigInteger('sujet_id');
            $table->json('contenu');
            $table->string('source', 16); // ia | regles
            $table->string('faits_empreinte', 64);
            $table->timestamp('generated_at');
            $table->timestamps();

            $table->unique(['center_id', 'sujet_type', 'sujet_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('center_analyses');
    }
};
