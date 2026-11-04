<?php

namespace App\Services\Center;

use App\Models\CenterDomain;
use App\Models\LanguageCenter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Les regles des adresses de centres (lot B), en un seul endroit : ce qui est
 * l'hote principal, ce qui est un sous-domaine reserve, quel centre repond a
 * quel hote, et quels hotes on accepte (FaireConfianceAuxHotes).
 *
 * Seul un domaine VERIFIE mene a un centre. Un domaine ajoute mais pas encore
 * verifie n'existe pas pour le visiteur : 404.
 */
class Domaines
{
    private const CLE_HOTE = 'centres-domaines:hote:';

    private const CLE_VERIFIES = 'centres-domaines:verifies';

    /** Un nom d'hote ASCII (apres conversion punycode), sans port ni point final. */
    private const MOTIF_HOTE = '/^(?=.{1,253}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/';

    /** Une etiquette de sous-domaine : 2 a 63 caracteres, lettres, chiffres, tirets. */
    private const MOTIF_ETIQUETTE = '/^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])$/';

    /**
     * Ramene une saisie (« HTTPS://Ecole.CM/ », « ecole.cm. », « école.cm »)
     * a un hote en minuscules, sans schema, chemin, port ni point final.
     * Null si ce n'est pas un nom d'hote utilisable.
     */
    public static function normaliser(?string $saisie): ?string
    {
        $h = mb_strtolower(trim((string) $saisie));
        if ($h === '') {
            return null;
        }

        $h = (string) preg_replace('~^[a-z][a-z0-9+.-]*://~', '', $h);
        $h = preg_split('~[/?#]~', $h)[0];
        if (str_contains($h, '@')) {
            return null;
        }
        $h = (string) preg_replace('/:\d+$/', '', $h);
        $h = rtrim($h, '.');

        if ($h !== '' && preg_match('/[^\x20-\x7e]/', $h)) {
            $h = function_exists('idn_to_ascii')
                ? (idn_to_ascii($h, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46) ?: '')
                : '';
        }

        return $h !== '' && preg_match('/^[a-z0-9.-]+$/', $h) ? $h : null;
    }

    public static function estUnHoteValide(string $hote): bool
    {
        return (bool) preg_match(self::MOTIF_HOTE, $hote);
    }

    public static function estUneEtiquetteValide(string $etiquette): bool
    {
        return (bool) preg_match(self::MOTIF_ETIQUETTE, $etiquette);
    }

    /** @return array<int, string> */
    public static function principaux(): array
    {
        return array_map('strtolower', (array) config('domaines.principaux', []));
    }

    public static function racine(): string
    {
        return strtolower((string) config('domaines.racine'));
    }

    /**
     * L'hote se comporte-t-il comme aujourd'hui ? L'hote principal, et en
     * developpement local les adresses de la machine (127.0.0.1, ::1).
     */
    public static function estPrincipal(string $hote): bool
    {
        $hote = strtolower(trim($hote, '[]'));

        if (in_array($hote, self::principaux(), true)) {
            return true;
        }

        return app()->environment('local')
            && ($hote === 'localhost' || filter_var($hote, FILTER_VALIDATE_IP) !== false);
    }

    /** L'etiquette si l'hote est un sous-domaine direct de la racine (« ecole »), sinon null. */
    public static function etiquetteSousLaRacine(string $hote): ?string
    {
        $suffixe = '.'.self::racine();
        if (! str_ends_with($hote, $suffixe)) {
            return null;
        }

        $etiquette = substr($hote, 0, -strlen($suffixe));

        return $etiquette !== '' && ! str_contains($etiquette, '.') ? $etiquette : null;
    }

    /** Sous la racine, a un niveau quelconque (a.b.racine compris). */
    public static function estSousLaRacine(string $hote): bool
    {
        return str_ends_with($hote, '.'.self::racine());
    }

    public static function estReserve(string $etiquette): bool
    {
        return in_array(strtolower($etiquette), (array) config('domaines.reserves', []), true);
    }

    /**
     * Le centre auquel mene cet hote, seulement par un domaine verifie. Le
     * centre est relu a chaque fois (sa fermeture vaut tout de suite) ; seul
     * le lien hote -> centre est garde en cache, et jamais une absence : un
     * hote inconnu ne remplit pas le cache.
     */
    public static function centreDeLHote(string $hote): ?LanguageCenter
    {
        $hote = strtolower($hote);
        $cle = self::CLE_HOTE.$hote;

        $id = Cache::get($cle);
        if ($id === null) {
            $id = CenterDomain::where('host', $hote)->whereNotNull('verified_at')->value('center_id');
            if ($id === null) {
                return null;
            }
            Cache::put($cle, (int) $id, (int) config('domaines.cache_secondes', 600));
        }

        return LanguageCenter::find($id);
    }

    /**
     * Les domaines propres verifies, que TrustHosts doit accepter. Si la base
     * ne repond pas (ou la table n'est pas encore migree), aucun : l'hote
     * principal et ses sous-domaines restent servis.
     *
     * @return array<int, string>
     */
    public static function domainesPropresVerifies(): array
    {
        try {
            return Cache::rememberForever(self::CLE_VERIFIES, fn () => CenterDomain::query()
                ->whereNotNull('verified_at')
                ->where('type', CenterDomain::DOMAINE_PROPRE)
                ->pluck('host')
                ->map(fn ($h) => strtolower((string) $h))
                ->values()
                ->all());
        } catch (Throwable $e) {
            Log::warning('domaines: liste des domaines verifies illisible', ['erreur' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * Les motifs d'hotes acceptes : les hotes principaux, un niveau sous la
     * racine (*.prepla.mirlab.cloud), et les domaines propres verifies.
     *
     * @return array<int, string>
     */
    public static function motifsDeConfiance(): array
    {
        $motifs = array_map(fn ($h) => '^'.preg_quote($h, '{').'$', self::principaux());
        $motifs[] = '^[a-z0-9-]+\.'.preg_quote(self::racine(), '{').'$';

        foreach (self::domainesPropresVerifies() as $hote) {
            $motifs[] = '^'.preg_quote($hote, '{').'$';
        }

        return array_values(array_unique($motifs));
    }

    /** Efface le cache d'un hote (et de son ancien nom) et la liste des verifies. */
    public static function oublier(?string ...$hotes): void
    {
        foreach (array_filter($hotes) as $hote) {
            Cache::forget(self::CLE_HOTE.strtolower($hote));
        }
        Cache::forget(self::CLE_VERIFIES);
    }
}
