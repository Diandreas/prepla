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

// Releves des centres, le 1er du mois, sur le mois civil ECOULE (fuseau
// Africa/Douala). A decommenter par le lot qui livre la commande :
// - centres:actifs (lot E) : eleves actifs et palier A1/A2/A3/reseau par centre ;
// - centres:releves (lot C, puis D) : part PrePla des acces vendus et commissions.
// Les deux sont idempotentes : un mois deja calcule n'est pas recalcule sans demande.
Schedule::command('centres:actifs')->monthlyOn(1, '02:00')->timezone('Africa/Douala');
Schedule::command('centres:releves')->monthlyOn(1, '03:00')->timezone('Africa/Douala');
