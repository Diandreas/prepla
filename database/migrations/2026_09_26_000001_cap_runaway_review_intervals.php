<?php

use App\Services\ErrorSpacedRepetitionService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Les intervalles de révision ont grandi sans plafond : certaines erreurs étaient
     * planifiées en l'an 8243, d'autres au-delà de ce que PHP sait relire — la fin de
     * séance répondait alors 500 à chaque tentative. On ramène ces lignes à un an.
     *
     * Requêtes brutes uniquement : passer par Eloquent ferait analyser ces dates
     * impossibles par Carbon, et la migration échouerait elle aussi.
     */
    public function up(): void
    {
        $max = ErrorSpacedRepetitionService::MAX_INTERVAL_DAYS;

        DB::table('user_errors')
            ->where('interval_days', '>', $max)
            // Une année à cinq chiffres dépasse les 19 caractères d'une date normale.
            ->orWhereRaw('length(next_review_at) > 19')
            ->orWhereRaw("substr(next_review_at, 1, 4) > '2100'")
            ->update([
                'interval_days' => $max,
                'next_review_at' => now()->addDays($max),
            ]);
    }

    /**
     * Rien à défaire : les valeurs d'origine étaient inexploitables.
     */
    public function down(): void
    {
        //
    }
};
