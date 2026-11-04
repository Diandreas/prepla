<?php

namespace App\Services\Center;

use App\Models\CenterAccessGrant;
use App\Models\CenterStatement;
use App\Models\LanguageCenter;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * Part de PrePla sur les acces vendus par les centres en formule revente.
 *
 * Base : la somme des montants des acces OUVERTS dans le mois (date de creation,
 * mois civil de Douala), coupes ou non : l'argent a ete encaisse a l'ouverture.
 * Part PrePla : 30 %, arrondie a l'unite (XAF n'a pas de centimes).
 *
 * Idempotent : un releve par centre, mois et nature (unicite en base). Relancer
 * met a jour le brouillon ; un releve envoye ou paye n'est plus touche.
 */
class ReleveRevente
{
    public const NATURE = 'revente';

    public const FUSEAU = 'Africa/Douala';

    public static function moisCourant(): string
    {
        return CarbonImmutable::now(self::FUSEAU)->format('Y-m');
    }

    public static function moisPrecedent(): string
    {
        return CarbonImmutable::now(self::FUSEAU)->startOfMonth()->subMonth()->format('Y-m');
    }

    public static function moisValide(?string $mois): bool
    {
        return is_string($mois) && preg_match('/^20\d\d-(0[1-9]|1[0-2])$/', $mois) === 1;
    }

    /**
     * Debut (inclus) et fin (exclue) du mois a Douala, dans le fuseau de
     * l'application (celui des dates en base).
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public static function bornes(string $mois): array
    {
        if (! self::moisValide($mois)) {
            throw new InvalidArgumentException("Mois invalide : {$mois} (attendu : YYYY-MM).");
        }

        $debut = CarbonImmutable::createFromFormat('Y-m-d H:i:s', "{$mois}-01 00:00:00", self::FUSEAU);

        return [
            $debut->setTimezone(config('app.timezone')),
            $debut->addMonthNoOverflow()->setTimezone(config('app.timezone')),
        ];
    }

    /** 30 % arrondi a l'unite, demi vers le haut. */
    public static function partPrepla(int $montantBrut): int
    {
        return (int) round($montantBrut * LanguageCenter::PART_PREPLA_REVENTE / 100, 0, PHP_ROUND_HALF_UP);
    }

    /** Les acces ouverts par ce centre dans le mois, du plus ancien au plus recent. */
    public function accesOuverts(int $centreId, string $mois): Collection
    {
        [$debut, $fin] = self::bornes($mois);

        return CenterAccessGrant::with(['user:id,name,email', 'grantedBy:id,name', 'revokedBy:id,name'])
            ->where('center_id', $centreId)
            ->where('created_at', '>=', $debut)
            ->where('created_at', '<', $fin)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
    }

    /**
     * Le calcul d'un centre pour un mois, sans rien ecrire.
     *
     * @return array{montant_brut: int, part_prepla: int, lignes: array<int, array<string, mixed>>}
     */
    public function calculer(int $centreId, string $mois): array
    {
        $acces = $this->accesOuverts($centreId, $mois);
        $brut = (int) $acces->sum('amount');

        return [
            'montant_brut' => $brut,
            'part_prepla' => self::partPrepla($brut),
            'lignes' => $acces->map(fn (CenterAccessGrant $g) => [
                'acces_id' => $g->id,
                'eleve_id' => $g->user_id,
                'eleve' => $g->nomEleve(),
                'ouvert_le' => $g->created_at?->toIso8601String(),
                'du' => $g->starts_at?->toIso8601String(),
                'au' => $g->ends_at?->toIso8601String(),
                'montant' => (int) $g->amount,
                'devise' => $g->currency,
                'moyen' => $g->payment_method,
                'reference' => $g->reference,
                'ouvert_par' => $g->grantedBy?->name,
                'coupe_le' => $g->revoked_at?->toIso8601String(),
                'coupe_par' => $g->revokedBy?->name,
            ])->values()->all(),
        ];
    }

    /**
     * Les centres a relever pour ce mois : ceux en formule revente, et ceux qui
     * ont ouvert des acces ce mois-la (meme s'ils ont change de formule depuis).
     *
     * @return Collection<int, LanguageCenter>
     */
    public function centresConcernes(string $mois): Collection
    {
        [$debut, $fin] = self::bornes($mois);

        $avecAcces = CenterAccessGrant::where('created_at', '>=', $debut)
            ->where('created_at', '<', $fin)
            ->distinct()
            ->pluck('center_id');

        return LanguageCenter::where('formule', LanguageCenter::FORMULE_REVENTE)
            ->orWhereIn('id', $avecAcces)
            ->orderBy('id')
            ->get()
            // Le marqueur libre-service l'emporte sur la colonne (formule()).
            ->filter(fn (LanguageCenter $c) => $c->estEnRevente() || $avecAcces->contains($c->id))
            ->values();
    }

    /**
     * Ecrit les releves d'un mois : un par centre en formule revente, et un par
     * centre qui a ouvert des acces ce mois-la (meme s'il a change de formule depuis).
     *
     * @return array{crees: int, mis_a_jour: int, figes: int}
     */
    public function releverMois(string $mois): array
    {
        $bilan = ['crees' => 0, 'mis_a_jour' => 0, 'figes' => 0];

        foreach ($this->centresConcernes($mois) as $centre) {
            $releve = CenterStatement::firstOrNew([
                'center_id' => $centre->id,
                'periode' => $mois,
                'nature' => self::NATURE,
            ]);

            if ($releve->exists && $releve->estFige()) {
                $bilan['figes']++;

                continue;
            }

            $nouveau = ! $releve->exists;
            $releve->fill($this->calculer($centre->id, $mois) + [
                'statut' => $releve->statut ?? 'brouillon',
                'computed_at' => now(),
            ])->save();

            $bilan[$nouveau ? 'crees' : 'mis_a_jour']++;
        }

        return $bilan;
    }
}
