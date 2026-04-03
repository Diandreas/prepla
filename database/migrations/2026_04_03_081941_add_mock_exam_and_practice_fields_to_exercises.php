<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('exercises', function (Blueprint $table) {
            $table->foreignId('mock_exam_id')->nullable()->after('exam_id')
                  ->constrained('mock_exams')->cascadeOnDelete();
            $table->foreignId('exam_section_id')->nullable()->after('mock_exam_id')
                  ->constrained('exam_sections')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exercises', function (Blueprint $table) {
            $table->dropConstrainedForeignId('exam_section_id');
            $table->dropConstrainedForeignId('mock_exam_id');
        });
    }
};
