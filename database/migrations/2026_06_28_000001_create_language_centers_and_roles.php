<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Global role — only distinguishes super_admin from everyone else.
        // All existing accounts default to 'student' (no behaviour change).
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('student')->index()->after('email');
        });

        Schema::create('language_centers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('owner_email')->nullable();
            $table->unsignedInteger('seats_limit')->default(50);
            $table->foreignId('default_exam_id')->nullable()->constrained('exams')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        // Membership + center-scoped role. One center per user in this lot
        // (unique user_id), but the pivot lets us go multi-center later.
        Schema::create('center_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id')->constrained('language_centers')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role'); // center_admin | teacher | student
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();

            $table->unique('user_id');
            $table->index(['center_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('center_user');
        Schema::dropIfExists('language_centers');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
