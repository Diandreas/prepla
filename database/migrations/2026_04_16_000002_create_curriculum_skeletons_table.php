<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pilier 9: Curriculum skeleton for progressive/adaptive lesson generation.
     */
    public function up(): void
    {
        Schema::create('curriculum_skeletons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->json('objectives'); // [{order, title, concept, status: pending|current|done|skipped, priority}]
            $table->unsignedInteger('current_objective_index')->default(0);
            $table->unsignedInteger('consecutive_successes')->default(0);
            $table->unsignedInteger('consecutive_failures')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'exam_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('curriculum_skeletons');
    }
};
