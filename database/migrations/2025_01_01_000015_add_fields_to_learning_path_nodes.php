<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('learning_path_nodes', function (Blueprint $table) {
            $table->string('description')->nullable()->after('title');
            $table->string('icon')->nullable()->after('description');
            $table->string('skill_type')->nullable()->after('icon');
            $table->string('level')->nullable()->after('skill_type');
            $table->integer('xp_reward')->default(50)->after('level');
        });
    }

    public function down(): void
    {
        Schema::table('learning_path_nodes', function (Blueprint $table) {
            $table->dropColumn(['description', 'icon', 'skill_type', 'level', 'xp_reward']);
        });
    }
};
