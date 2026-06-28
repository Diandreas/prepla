<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classrooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id')->constrained('language_centers')->cascadeOnDelete();
            $table->string('name');
            $table->string('level')->nullable(); // A1..C2
            $table->foreignId('exam_id')->nullable()->constrained('exams')->nullOnDelete();
            $table->string('invite_code')->unique();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->index('center_id');
        });

        // Students and teachers attached to a classroom.
        Schema::create('classroom_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classroom_id')->constrained('classrooms')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role_in_class')->default('student'); // student | teacher
            $table->timestamps();

            $table->unique(['classroom_id', 'user_id']);
        });

        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classroom_id')->constrained('classrooms')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('instructions')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index('classroom_id');
        });

        // Polymorphic items: an assignment bundles Exercises and/or Lessons.
        Schema::create('assignment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained('assignments')->cascadeOnDelete();
            $table->morphs('itemable'); // itemable_type + itemable_id
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignment_items');
        Schema::dropIfExists('assignments');
        Schema::dropIfExists('classroom_user');
        Schema::dropIfExists('classrooms');
    }
};
