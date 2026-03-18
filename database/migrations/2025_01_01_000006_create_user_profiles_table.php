<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('target_exam_id')->nullable()->constrained('exams')->nullOnDelete();
            $table->integer('target_score')->nullable();
            $table->date('exam_date')->nullable();
            $table->string('current_level')->nullable(); // A1-C2
            $table->integer('xp_total')->default(0);
            $table->integer('streak_current')->default(0);
            $table->date('streak_last_date')->nullable();
            $table->timestamp('onboarding_completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_profiles');
    }
};
