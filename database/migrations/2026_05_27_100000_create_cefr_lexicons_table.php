<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Master CEFR-tagged lexicon ingested from multiple open datasets:
 *  - CEFR-J Vocabulary Profile (EN)
 *  - EFLLex (EN, UCLouvain)
 *  - FLELex TT + CRF (FR, UCLouvain)
 *  - Goethe Wortlisten (DE) — ingestion ultérieure depuis PDF
 *
 * Sert de source de vérité pour :
 *  - calibrer la difficulté des exercices générés
 *  - proposer "+ Lexique" sur les mots hors zone de l'utilisateur
 *  - estimer la couverture vocabulaire CEFR d'un texte
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('cefr_lexicons', function (Blueprint $table) {
            $table->id();

            // Langue ISO ('en', 'fr', 'de')
            $table->string('language', 5)->index();

            // Forme de surface (tel qu'écrit) — normalisée lowercase
            $table->string('word', 100)->index();

            // Lemme (forme canonique) si disponible — peut être identique à word
            $table->string('lemma', 100)->nullable();

            // Part of speech taggué (NOUN, VERB, ADJ, ADV, PREP, etc.)
            $table->string('pos', 20)->nullable();

            // Niveau CEFR (A1, A2, B1, B2, C1, C2)
            $table->string('level', 2)->index();

            // Fréquence totale (toutes occurrences confondues, par million)
            $table->float('freq_total')->nullable();

            // Fréquence par niveau CEFR (JSON: {"A1": 0.83, "B1": 12.4, ...})
            // Utile pour EFLLex/FLELex qui exposent freq par niveau
            $table->json('freq_per_level')->nullable();

            // Source du record ('cefrj', 'efllex', 'flelex_tt', 'flelex_crf', 'goethe')
            $table->string('source', 30)->index();

            $table->timestamps();

            // Un même mot peut exister dans plusieurs sources/POS (variantes morphologiques)
            $table->unique(['language', 'word', 'pos', 'source'], 'cefr_lex_uniq');
            $table->index(['language', 'level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cefr_lexicons');
    }
};
