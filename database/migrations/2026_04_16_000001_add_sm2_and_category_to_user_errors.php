<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pilier 3: SM-2 columns for spaced repetition on errors
     * Pilier 4: Category + subcategory for error diagnosis
     */
    public function up(): void
    {
        Schema::table('user_errors', function (Blueprint $table) {
            // Pilier 4 — Catégorisation des erreurs
            $table->string('subcategory')->nullable()->after('error_category');

            // Pilier 3 — SM-2 spaced repetition columns
            $table->decimal('ease_factor', 4, 2)->default(2.50)->after('mastered');
            $table->unsignedInteger('interval_days')->default(1)->after('ease_factor');
            $table->timestamp('next_review_at')->nullable()->after('interval_days');

            // Index for due reviews
            $table->index(['user_id', 'next_review_at'], 'user_errors_review_due_idx');
            $table->index(['user_id', 'error_category'], 'user_errors_category_idx');
        });
    }

    public function down(): void
    {
        Schema::table('user_errors', function (Blueprint $table) {
            $table->dropIndex('user_errors_review_due_idx');
            $table->dropIndex('user_errors_category_idx');
            $table->dropColumn(['subcategory', 'ease_factor', 'interval_days', 'next_review_at']);
        });
    }
};
