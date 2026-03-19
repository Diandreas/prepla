<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_learning_progress', function (Blueprint $table) {
            $table->unsignedSmallInteger('exercises_done')->default(0)->after('status');
            $table->unsignedSmallInteger('exercises_required')->default(3)->after('exercises_done');
        });
    }

    public function down(): void
    {
        Schema::table('user_learning_progress', function (Blueprint $table) {
            $table->dropColumn(['exercises_done', 'exercises_required']);
        });
    }
};
