<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tracks which emails have already been granted a free trial. Kept
     * independent of the `users`/`user_profiles` rows so a user cannot get
     * a fresh trial by deleting their account and re-registering with the
     * same email (account deletion cascades those tables, this one is never
     * touched by it).
     */
    public function up(): void
    {
        Schema::create('consumed_trials', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->timestamp('trial_granted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consumed_trials');
    }
};
