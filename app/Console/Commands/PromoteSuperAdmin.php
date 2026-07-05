<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class PromoteSuperAdmin extends Command
{
    protected $signature = 'user:make-super-admin {email}';

    protected $description = 'Promote a user to the global super_admin role (B2B back-office access).';

    public function handle(): int
    {
        $email = $this->argument('email');
        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("Aucun utilisateur avec l'email {$email}.");
            return self::FAILURE;
        }

        // role is intentionally not mass-assignable (privilege escalation
        // risk if a controller ever does fill($request->all())) — set directly.
        $user->role = 'super_admin';
        $user->save();
        $this->info("{$email} est maintenant super_admin.");

        return self::SUCCESS;
    }
}
