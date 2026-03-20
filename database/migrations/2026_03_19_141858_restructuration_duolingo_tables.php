<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Restructuration de LearningPathNode (Syllabus statique)
        Schema::table('learning_path_nodes', function (Blueprint $table) {
            if (!Schema::hasColumn('learning_path_nodes', 'chapter_name')) {
                $table->string('chapter_name')->nullable()->after('exam_id');
                $table->integer('chapter_order')->default(1)->after('chapter_name');
            }
        });

        // 2. Ajout de node_id à Exercises (Liaison statique)
        Schema::table('exercises', function (Blueprint $table) {
            if (!Schema::hasColumn('exercises', 'node_id')) {
                $table->foreignId('node_id')->nullable()->constrained('learning_path_nodes')->nullOnDelete();
                $table->integer('order_in_node')->default(1); // 1: Théorie, 2: Pratique, 3: Test
            }
        });

        // 3. Ajout de scheduled_for à UserLearningProgress (Algorithme Jour J)
        Schema::table('user_learning_progress', function (Blueprint $table) {
            if (!Schema::hasColumn('user_learning_progress', 'scheduled_for')) {
                $table->date('scheduled_for')->nullable()->after('status');
                $table->integer('score')->nullable()->after('scheduled_for'); // Score moyen sur la session
            }
        });
    }

    public function down(): void
    {
        Schema::table('learning_path_nodes', function (Blueprint $table) {
            $table->dropColumn(['chapter_name', 'chapter_order']);
        });

        Schema::table('exercises', function (Blueprint $table) {
            $table->dropForeign(['node_id']);
            $table->dropColumn(['node_id', 'order_in_node']);
        });

        Schema::table('user_learning_progress', function (Blueprint $table) {
            $table->dropColumn(['scheduled_for', 'score']);
        });
    }
};
