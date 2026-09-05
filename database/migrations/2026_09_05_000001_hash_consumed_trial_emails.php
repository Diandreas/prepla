<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Replace retained trial email addresses with one-way anti-abuse fingerprints.
     */
    public function up(): void
    {
        DB::table('consumed_trials')
            ->orderBy('id')
            ->each(function (object $trial): void {
                $email = trim((string) $trial->email);

                if (preg_match('/^[a-f0-9]{64}$/i', $email) === 1) {
                    return;
                }

                $fingerprint = hash('sha256', mb_strtolower($email));
                $duplicate = DB::table('consumed_trials')
                    ->where('email', $fingerprint)
                    ->where('id', '!=', $trial->id)
                    ->exists();

                if ($duplicate) {
                    DB::table('consumed_trials')->where('id', $trial->id)->delete();

                    return;
                }

                DB::table('consumed_trials')
                    ->where('id', $trial->id)
                    ->update(['email' => $fingerprint]);
            });
    }

    /**
     * The original addresses cannot be reconstructed from their fingerprints.
     */
    public function down(): void
    {
        // Intentionally irreversible.
    }
};
