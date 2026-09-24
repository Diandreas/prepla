<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Les leçons écrites pendant une panne de l'IA ont été enregistrées comme des
     * leçons définitives : texte de remplacement, aucun quiz, donc aucun moyen
     * d'avancer dans le parcours. On les repasse en brouillon pour que
     * NextLessonGenerator les régénère au prochain passage de l'apprenant.
     */
    public function up(): void
    {
        DB::table('lessons')
            ->where('status', '!=', 'draft')
            ->where('theory_markdown', 'like', '%Content is being generated%')
            ->update(['status' => 'draft']);
    }

    /**
     * Rien à défaire : ces leçons étaient déjà vides avant.
     */
    public function down(): void
    {
        //
    }
};
