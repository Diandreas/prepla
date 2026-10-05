<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->json('learning_preferences')->nullable();
        });
        Schema::table('lessons', function (Blueprint $table) {
            $table->json('key_vocabulary')->nullable();
        });
        Schema::table('user_word_progress', function (Blueprint $table) {
            $table->timestamp('next_review_at')->nullable()->index();
            $table->unsignedInteger('recognition_count')->default(0);
            $table->unsignedInteger('recall_count')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('user_word_progress', fn (Blueprint $table) => $table->dropColumn(['next_review_at', 'recognition_count', 'recall_count']));
        Schema::table('lessons', fn (Blueprint $table) => $table->dropColumn('key_vocabulary'));
        Schema::table('user_profiles', fn (Blueprint $table) => $table->dropColumn('learning_preferences'));
    }
};
