<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\PracticeReminderNotification;
use Illuminate\Console\Command;

class SendPracticeReminders extends Command
{
    protected $signature = 'reminders:send';
    protected $description = 'Send daily practice reminders to users who have not practiced today';

    public function handle(): void
    {
        // Une seule requête (whereDoesntHave) au lieu de charger tous les
        // utilisateurs puis une requête exists() par utilisateur.
        $users = User::with('profile')
            ->whereHas('profile', fn ($q) => $q->whereNotNull('onboarding_completed_at'))
            ->whereDoesntHave('exerciseAttempts', fn ($q) => $q->whereDate('created_at', today()))
            ->get();

        $count = 0;
        foreach ($users as $user) {
            $streak = $user->profile?->streak_current ?? 0;
            try {
                $user->notify(new PracticeReminderNotification($streak));
                $count++;
            } catch (\Throwable $e) {
                // Un abonnement push expiré/invalide ne doit pas stopper l'envoi
                // aux utilisateurs suivants.
                \Illuminate\Support\Facades\Log::warning('Practice reminder failed', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Sent {$count} practice reminders.");
    }
}
