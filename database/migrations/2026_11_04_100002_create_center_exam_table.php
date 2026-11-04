<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Les examens qu'un centre propose (lot A). Aucune ligne pour un centre :
     * tous les examens proposables (langue active), comme avant.
     *
     * Une table plutot qu'une liste dans settings : les cles etrangeres retirent
     * d'elles-memes un examen supprime. Chaque examen garde sa propre structure :
     * on filtre les listes, on ne fusionne rien.
     */
    public function up(): void
    {
        Schema::create('center_exam', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id')->constrained('language_centers')->cascadeOnDelete();
            $table->foreignId('exam_id')->constrained('exams')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['center_id', 'exam_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('center_exam');
    }
};
