<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_exercise_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exercise_id')->constrained()->cascadeOnDelete();
            $table->json('answers');
            $table->decimal('score', 5, 2)->nullable();
            $table->decimal('accuracy_percent', 5, 2)->nullable();
            $table->integer('time_spent')->nullable(); // in seconds
            $table->integer('xp_earned')->default(0);
            $table->json('feedback')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_exercise_attempts');
    }
};
