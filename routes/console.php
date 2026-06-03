<?php

use App\Console\Commands\SendPracticeReminders;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Send practice reminders every day at 18h00 (local time)
Schedule::command(SendPracticeReminders::class)->dailyAt('18:00');
