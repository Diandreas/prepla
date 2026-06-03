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
        $today = now()->toDateString();

        // Users with push subscriptions OR emails who haven't practiced today
        $users = User::with('profile')
            ->whereHas('profile', function ($q) {
                $q->whereNotNull('onboarding_completed_at');
            })
            ->get()
            ->filter(function (User $user) use ($today) {
                // Skip if already practiced today
                $lastAttempt = $user->exerciseAttempts()
                    ->whereDate('created_at', $today)
                    ->exists();

                return !$lastAttempt;
            });

        $count = 0;
        foreach ($users as $user) {
            $streak = $user->profile?->streak_current ?? 0;
            $user->notify(new PracticeReminderNotification($streak));
            $count++;
        }

        $this->info("Sent {$count} practice reminders.");
    }
}
