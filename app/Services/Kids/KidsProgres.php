<?php

namespace App\Services\Kids;

use App\Models\KidsEnfant;
use App\Models\KidsResultat;
use App\Models\User;

/**
 * PrePla Kids : les mots d'un theme dans la langue de l'enfant, ses etoiles,
 * les themes ouverts, et ce que voit le parent.
 *
 * Les etoiles d'un jeu sont les MEILLEURES obtenues : rejouer pour mieux faire
 * ne fait jamais perdre une etoile. Le total de l'enfant est la somme de ces
 * meilleures etoiles, pas le nombre de parties.
 */
class KidsProgres
{
    public const COLONNE = ['english' => 1, 'french' => 2, 'german' => 3];

    public static function themes(): array
    {
        return config('kids.themes');
    }

    public static function activites(): array
    {
        return config('kids.activites');
    }

    /** Les mots d'un theme : image, mot dans la langue apprise, et dans la langue d'aide. */
    public static function mots(string $theme, string $langue, string $aide = 'fr'): array
    {
        $col = self::COLONNE[$langue] ?? 1;
        $colAide = $aide === 'en' ? 1 : 2;

        return array_map(fn ($m) => [
            'image' => $m[0],
            // L'illustration dessinee, si elle a ete deposee ; sinon l'emoji suffit.
            'fichier' => self::fichier("mots/{$theme}/".self::cle($m[1])),
            'mot' => $m[$col],
            // La traduction n'aide que si elle est dans une autre langue.
            'aide' => $colAide === $col ? null : $m[$colAide],
        ], config("kids.themes.{$theme}.mots", []));
    }

    /** « school bag » -> « school-bag », « T-shirt » -> « t-shirt » : le nom du fichier d'un mot. */
    public static function cle(string $anglais): string
    {
        return trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($anglais)), '-');
    }

    /**
     * Une illustration de public/illustrations/prepla-kids/{chemin}.webp, si elle
     * existe (les PNG sources, trop lourds pour un telephone, ne sont pas servis :
     * scripts/optimiser-images-kids.py en fait des WebP de 512 px).
     * Les images arrivent au fur et a mesure : tant qu'il en manque une,
     * l'ecran garde l'emoji, sans trou ni image cassee.
     */
    public static function fichier(string $chemin): ?string
    {
        return is_file(public_path("illustrations/prepla-kids/{$chemin}.webp"))
            ? "/illustrations/prepla-kids/{$chemin}.webp"
            : null;
    }

    /** Etoiles a partir d'un score sur 100. */
    public static function etoiles(int $score): int
    {
        return $score >= 90 ? 3 : ($score >= 70 ? 2 : ($score >= 40 ? 1 : 0));
    }

    /** Meilleures etoiles par theme et par jeu : [theme => [activite => etoiles]]. */
    public static function meilleures(KidsEnfant $enfant): array
    {
        $out = [];
        KidsResultat::where('enfant_id', $enfant->id)
            ->selectRaw('theme, activite, max(etoiles) e')
            ->groupBy('theme', 'activite')->get()
            ->each(function ($r) use (&$out) { $out[$r->theme][$r->activite] = (int) $r->e; });

        return $out;
    }

    /** Un theme est-il paye (au-dela des premiers gratuits) ? */
    public static function payant(string $theme): bool
    {
        return array_search($theme, array_keys(self::themes()), true) >= (int) config('kids.gratuits', 3);
    }

    /**
     * La carte de l'enfant : chaque theme, ses etoiles, et s'il est ouvert.
     * Un theme s'ouvre quand le precedent a assez d'etoiles ; un theme payant
     * demande en plus l'acces PrePla Plus du parent.
     */
    public static function carte(KidsEnfant $enfant, ?User $parent = null): array
    {
        $meilleures = self::meilleures($enfant);
        $seuil = (int) config('kids.etoiles_pour_debloquer', 4);
        $premium = $parent?->hasPremiumAccess() ?? false;
        $precedentOk = true;
        $carte = [];

        foreach (self::themes() as $slug => $t) {
            $etoiles = array_sum($meilleures[$slug] ?? []);
            $payant = self::payant($slug);
            $carte[] = [
                'slug' => $slug,
                'nom' => $enfant->langue_aide === 'en' ? $t['en'] : $t['fr'],
                'icone' => $t['icone'],
                'illustration' => self::fichier("themes/{$slug}"),
                'couleur' => $t['couleur'],
                'etoiles' => $etoiles,
                'max' => count(self::activites()) * 3,
                'ouvert' => $precedentOk && (! $payant || $premium),
                'verrou' => ! $precedentOk ? 'etoiles' : ($payant && ! $premium ? 'plus' : null),
            ];
            $precedentOk = $etoiles >= $seuil;
        }

        return $carte;
    }

    public static function themeOuvert(KidsEnfant $enfant, string $theme, ?User $parent): bool
    {
        foreach (self::carte($enfant, $parent) as $t) {
            if ($t['slug'] === $theme) {
                return $t['ouvert'];
            }
        }

        return false;
    }

    /**
     * Enregistre une partie : le resultat, la serie de jours, et le total
     * d'etoiles (qui ne compte que les progres sur le meilleur score du jeu).
     */
    public static function enregistrer(KidsEnfant $enfant, string $theme, string $activite, int $score, int $duree, array $motsRates): array
    {
        $score = max(0, min(100, $score));
        $etoiles = self::etoiles($score);
        $avant = (int) KidsResultat::where('enfant_id', $enfant->id)->where('theme', $theme)->where('activite', $activite)->max('etoiles');

        KidsResultat::create([
            'enfant_id' => $enfant->id, 'theme' => $theme, 'activite' => $activite,
            'score' => $score, 'etoiles' => $etoiles, 'duree' => min(3600, max(0, $duree)),
            'mots_rates' => array_values(array_slice(array_unique($motsRates), 0, 20)),
        ]);

        $gagnees = max(0, $etoiles - $avant);
        $aujourdhui = now('Africa/Douala')->startOfDay();
        $dernier = $enfant->dernier_jour;
        $serie = match (true) {
            $dernier && $dernier->isSameDay($aujourdhui) => $enfant->serie,
            $dernier && $dernier->isSameDay($aujourdhui->copy()->subDay()) => $enfant->serie + 1,
            default => 1,
        };
        $enfant->forceFill(['etoiles' => $enfant->etoiles + $gagnees, 'serie' => $serie, 'dernier_jour' => $aujourdhui->toDateString()])->save();

        return ['etoiles' => $etoiles, 'gagnees' => $gagnees, 'total' => $enfant->etoiles, 'serie' => $serie, 'record' => $etoiles > $avant];
    }

    /** Ce que le parent voit de son enfant. */
    public static function bilan(KidsEnfant $enfant): array
    {
        $resultats = KidsResultat::where('enfant_id', $enfant->id);
        $semaine = (clone $resultats)->where('created_at', '>=', now()->subDays(7));
        $meilleures = self::meilleures($enfant);
        $themes = self::themes();

        // Mots appris : ceux des themes ou « Ecoute » ou « Lis » a 2 etoiles ou plus.
        $appris = 0;
        foreach ($meilleures as $theme => $jeux) {
            if (max($jeux['ecoute'] ?? 0, $jeux['lis'] ?? 0) >= 2) {
                $appris += count($themes[$theme]['mots'] ?? []);
            }
        }

        // Les mots le plus souvent manques ces 30 derniers jours.
        $rates = [];
        (clone $resultats)->where('created_at', '>=', now()->subDays(30))->pluck('mots_rates')
            ->each(function ($liste) use (&$rates) { foreach ((array) $liste as $m) { $rates[$m] = ($rates[$m] ?? 0) + 1; } });
        arsort($rates);

        return [
            'etoiles' => $enfant->etoiles,
            'serie' => $enfant->serie,
            'mots_appris' => $appris,
            'mots_total' => array_sum(array_map(fn ($t) => count($t['mots']), $themes)),
            'themes_finis' => count(array_filter($meilleures, fn ($j) => array_sum($j) >= (int) config('kids.etoiles_pour_debloquer', 4))),
            'parties_semaine' => (clone $semaine)->count(),
            'minutes_semaine' => (int) round((clone $semaine)->sum('duree') / 60),
            'derniere' => (clone $resultats)->max('created_at'),
            'a_revoir' => array_slice(array_keys($rates), 0, 6),
        ];
    }
}
