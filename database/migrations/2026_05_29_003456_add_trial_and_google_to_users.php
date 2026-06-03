<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('google_id')->nullable()->after('email');
            $table->string('avatar')->nullable()->after('google_id');
            $table->string('password')->nullable()->change();
        });

        Schema::table('user_profiles', function (Blueprint $table) {
            $table->timestamp('trial_ends_at')->nullable()->after('plan');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['google_id', 'avatar']);
        });

        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropColumn('trial_ends_at');
        });
    }
};
