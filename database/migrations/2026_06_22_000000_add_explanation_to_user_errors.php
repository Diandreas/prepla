<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_errors', function (Blueprint $table) {
            // AI explanation of why the answer was wrong, shown in the review center.
            $table->text('explanation')->nullable()->after('correct_answer');
        });
    }

    public function down(): void
    {
        Schema::table('user_errors', function (Blueprint $table) {
            $table->dropColumn('explanation');
        });
    }
};
