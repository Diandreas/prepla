<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pilier 1 + 9: Lessons table — lesson content generated just-in-time.
     */
    public function up(): void
    {
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('node_id')->nullable()->constrained('learning_path_nodes')->nullOnDelete();
            $table->unsignedInteger('skeleton_objective_index')->nullable();
            $table->string('title');
            $table->string('concept')->nullable();
            $table->longText('theory_markdown');
            $table->json('key_takeaways')->nullable();     // 3-5 key points
            $table->json('common_mistakes')->nullable();    // typical traps
            $table->json('comprehension_quiz')->nullable(); // 3 QCM questions
            $table->json('based_on_errors')->nullable();     // traceability: recent errors that influenced this lesson
            $table->string('status')->default('published');  // draft|published|consolidation|remedial
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'skeleton_objective_index']);
            $table->index(['user_id', 'status']);
        });

        // Add lesson exercises: link exercises to a lesson
        Schema::table('exercises', function (Blueprint $table) {
            $table->foreignId('lesson_id')->nullable()->after('exam_id')
                  ->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('exercises', function (Blueprint $table) {
            $table->dropConstrainedForeignId('lesson_id');
        });

        Schema::dropIfExists('lessons');
    }
};
