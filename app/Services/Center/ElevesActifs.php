<?php

namespace App\Services\Center;

use App\Models\CenterMonthlyActivity;
use App\Models\LanguageCenter;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Les eleves actifs d'un centre sur un mois civil, et le palier qui en decoule.
 *
 * Un eleve actif : au moins une tentative d'exercice (user_exercise_attempts)
 * dans le mois, faite PENDANT une periode ou il etait eleve de ce centre
 * (center_membership_periods). Un eleve retire le 15 reste donc compte s'il a
 * travaille avant ; ce qu'il fait apres ne compte plus ; le personnel ne compte
 * jamais, meme s'il s'entraine.
 *
 * Le mois est celui de Douala : une tentative a 23 h 30 UTC le 31 octobre est
 * deja du 1er novembre (UTC+1).
 */
class ElevesActifs
{
    public const FUSEAU = 'Africa/Douala';

    /** Bornes hautes incluses des paliers ; au-dela : « reseau ». */
    public const PALIERS = ['A1' => 50, 'A2' => 150, 'A3' => 300];

    public const RESEAU = 'reseau';

    public static function palier(int $actifs): string
    {
        foreach (self::PALIERS as $nom => $plafond) {
            if ($actifs <= $plafond) {
                return $nom;
            }
        }

        return self::RESEAU;
    }

    /** Le mois civil en cours a Douala, au format YYYY-MM. */
    public static function moisCourant(): string
    {
        return CarbonImmutable::now(self::FUSEAU)->format('Y-m');
    }

    /** Le dernier mois termine a Douala : celui que releve la commande du 1er. */
    public static function moisPrecedent(): string
    {
        return CarbonImmutable::now(self::FUSEAU)->startOfMonth()->subMonth()->format('Y-m');
    }

    /** Un mois YYYY-MM valide (annee 2000 a 2099, mois 01 a 12). */
    public static function moisValide(?string $mois): bool
    {
        return is_string($mois) && preg_match('/^20\d\d-(0[1-9]|1[0-2])$/', $mois) === 1;
    }

    /** Le mois est-il entierement ecoule a Douala ? */
    public static function estTermine(string $mois): bool
    {
        return $mois < self::moisCourant();
    }

    /**
     * Debut (inclus) et fin (exclue) du mois a Douala, exprimes dans le fuseau de
     * l'application, celui dans lequel les dates sont enregistrees en base.
     *
     * @return array{0: string, 1: string}
     */
    public static function bornes(string $mois): array
    {
        if (! self::moisValide($mois)) {
            throw new InvalidArgumentException("Mois invalide : {$mois} (attendu : YYYY-MM).");
        }

        $debut = CarbonImmutable::createFromFormat('Y-m-d H:i:s', "{$mois}-01 00:00:00", self::FUSEAU);
        $fin = $debut->addMonthNoOverflow();
        $base = config('app.timezone', 'UTC');

        return [
            $debut->setTimezone($base)->format('Y-m-d H:i:s'),
            $fin->setTimezone($base)->format('Y-m-d H:i:s'),
        ];
    }

    /** Le nombre d'eleves actifs d'un centre sur un mois (calcul a la volee). */
    public function compter(int $centreId, string $mois): int
    {
        return $this->compterParCentre($mois, [$centreId])[$centreId] ?? 0;
    }

    /**
     * Le nombre d'eleves actifs de chaque centre demande (tous si null) sur un
     * mois, en une requete. Un centre sans eleve actif est absent du resultat.
     *
     * @param  array<int, int>|null  $centres
     * @return array<int, int> center_id => eleves actifs
     */
    public function compterParCentre(string $mois, ?array $centres = null): array
    {
        [$debut, $fin] = self::bornes($mois);

        return DB::table('center_membership_periods as p')
            ->join('user_exercise_attempts as a', 'a.user_id', '=', 'p.user_id')
            ->where('p.role', 'student')
            ->when($centres !== null, fn ($q) => $q->whereIn('p.center_id', $centres))
            ->where('a.created_at', '>=', $debut)
            ->where('a.created_at', '<', $fin)
            // La tentative tombe dans la periode d'appartenance : ni avant
            // l'arrivee, ni apres le depart.
            ->whereColumn('a.created_at', '>=', 'p.started_at')
            ->where(fn ($q) => $q->whereNull('p.ended_at')->orWhereColumn('a.created_at', '<', 'p.ended_at'))
            ->groupBy('p.center_id')
            ->selectRaw('p.center_id, count(distinct p.user_id) as actifs')
            ->pluck('actifs', 'p.center_id')
            ->map(fn ($n) => (int) $n)
            ->all();
    }

    /**
     * Arrete le releve d'un mois TERMINE pour tous les centres.
     *
     * Idempotent : un centre deja releve pour ce mois n'est pas recalcule, sauf
     * avec $forcer. Le mois en cours et les mois a venir sont refuses : leur
     * releve serait partiel, et le passage du 1er le croirait deja fait.
     *
     * @return array{calcules: int, ignores: int}
     */
    public function releverMois(string $mois, bool $forcer = false): array
    {
        if (! self::moisValide($mois)) {
            throw new InvalidArgumentException("Mois invalide : {$mois} (attendu : YYYY-MM).");
        }
        if (! self::estTermine($mois)) {
            throw new InvalidArgumentException("Le mois {$mois} n'est pas terminé (fuseau ".self::FUSEAU.').');
        }

        $centres = LanguageCenter::pluck('id')->all();
        $deja = CenterMonthlyActivity::where('periode', $mois)->pluck('center_id')->all();
        $aCalculer = $forcer ? $centres : array_values(array_diff($centres, $deja));

        if ($aCalculer === []) {
            return ['calcules' => 0, 'ignores' => count($centres)];
        }

        $actifs = $this->compterParCentre($mois, $aCalculer);
        $maintenant = now();

        DB::transaction(function () use ($aCalculer, $actifs, $mois, $maintenant) {
            foreach ($aCalculer as $centreId) {
                $n = $actifs[$centreId] ?? 0;
                CenterMonthlyActivity::updateOrCreate(
                    ['center_id' => $centreId, 'periode' => $mois],
                    ['active_students' => $n, 'palier' => self::palier($n), 'computed_at' => $maintenant],
                );
            }
        });

        return ['calcules' => count($aCalculer), 'ignores' => count($centres) - count($aCalculer)];
    }
}
