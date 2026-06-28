<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // center_id != null  → private content owned by a center.
        // center_id == null  → existing global catalogue (unchanged).
        Schema::table('exercises', function (Blueprint $table) {
            $table->foreignId('center_id')->nullable()->after('exam_id')
                ->constrained('language_centers')->nullOnDelete();
            $table->foreignId('creator_id')->nullable()->after('center_id')
                ->constrained('users')->nullOnDelete();
            $table->index('center_id');
        });

        Schema::table('lessons', function (Blueprint $table) {
            $table->foreignId('center_id')->nullable()->after('user_id')
                ->constrained('language_centers')->nullOnDelete();
            $table->foreignId('creator_id')->nullable()->after('center_id')
                ->constrained('users')->nullOnDelete();
            $table->index('center_id');
        });
    }

    public function down(): void
    {
        Schema::table('exercises', function (Blueprint $table) {
            $table->dropForeign(['center_id']);
            $table->dropForeign(['creator_id']);
            $table->dropColumn(['center_id', 'creator_id']);
        });

        Schema::table('lessons', function (Blueprint $table) {
            $table->dropForeign(['center_id']);
            $table->dropForeign(['creator_id']);
            $table->dropColumn(['center_id', 'creator_id']);
        });
    }
};
