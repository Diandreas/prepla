<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exercise_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained('exam_sections')->cascadeOnDelete();
            $table->string('slug');
            $table->string('name');
            $table->string('skill_type'); // reading, listening, writing, speaking
            $table->string('component_key'); // maps to React component
            $table->timestamps();

            $table->unique(['section_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exercise_types');
    }
};
