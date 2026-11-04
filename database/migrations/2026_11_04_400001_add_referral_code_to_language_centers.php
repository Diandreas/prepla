<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Parrainage (lot D) : chaque centre recoit un code de 8 caracteres aleatoires,
 * celui du lien /r/{code}. Pas le slug : un code devinable permettrait de
 * rattacher des comptes a un centre qui ne les a pas amenes.
 *
 * Les centres existants (dont la demonstration) recoivent leur code ici ; les
 * suivants a leur creation (App\Models\Concerns\AParrainage).
 */
return new class extends Migration
{
    // Sans I, L, O, 0 ni 1 : un code recopie a la main ne se trompe pas de lettre.
    private const ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    public function up(): void
    {
        Schema::table('language_centers', function (Blueprint $table) {
            $table->string('referral_code', 8)->nullable()->after('slug');
        });

        $pris = [];
        foreach (DB::table('language_centers')->whereNull('referral_code')->pluck('id') as $id) {
            do {
                $code = '';
                for ($i = 0; $i < 8; $i++) {
                    $code .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
                }
            } while (isset($pris[$code]));
            $pris[$code] = true;

            DB::table('language_centers')->where('id', $id)->update(['referral_code' => $code]);
        }

        Schema::table('language_centers', function (Blueprint $table) {
            $table->unique('referral_code', 'language_centers_referral_code_unique');
        });
    }

    public function down(): void
    {
        Schema::table('language_centers', function (Blueprint $table) {
            $table->dropUnique('language_centers_referral_code_unique');
        });
        Schema::table('language_centers', function (Blueprint $table) {
            $table->dropColumn('referral_code');
        });
    }
};
