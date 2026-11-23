<?php

namespace App\Support;

/**
 * La marque de l'instance (config/marque.php, lue du .env) : PrePla, ou un
 * autre site servi par le meme depot (marque blanche). Sans variable
 * MARQUE_*, tout reste strictement celui de PrePla.
 *
 * La marque d'un centre (App\Services\Center\Marque) se pose par-dessus :
 * celle-ci n'est que la valeur par defaut.
 */
class MarqueInstance
{
    public const NOM_PREPLA = 'PrePla';

    public static function nom(): string
    {
        return (string) config('marque.nom', self::NOM_PREPLA);
    }

    public static function nomCourt(): string
    {
        return (string) (config('marque.nom_court') ?: self::nom());
    }

    /** Vrai quand l'instance n'est pas PrePla : le nom PrePla ne doit plus paraitre. */
    public static function personnalisee(): bool
    {
        return self::nom() !== self::NOM_PREPLA;
    }

    public static function couleurPrimaire(): string
    {
        return self::hexa(config('marque.couleur_primaire'), '#4A90E2');
    }

    public static function couleurSecondaire(): string
    {
        return self::hexa(config('marque.couleur_secondaire'), '#0B2D63');
    }

    /** Vrai quand les couleurs different de celles d'app.css. */
    public static function couleursPersonnalisees(): bool
    {
        return strtoupper(self::couleurPrimaire()) !== '#4A90E2'
            || strtoupper(self::couleurSecondaire()) !== '#0B2D63';
    }

    /** Slugs d'examens proposes par l'instance ; [] = tous. @return list<string> */
    public static function examens(): array
    {
        return array_values((array) config('marque.examens', []));
    }

    /** Remplace « PrePla » par le nom de l'instance dans un texte visible. */
    public static function texte(string $texte): string
    {
        return self::personnalisee() ? str_replace(self::NOM_PREPLA, self::nom(), $texte) : $texte;
    }

    /**
     * Ce que le front recoit (donnee partagee « instance »).
     *
     * @return array<string, mixed>
     */
    public static function pourFront(): array
    {
        return [
            'nom' => self::nom(),
            'nom_court' => self::nomCourt(),
            'slogan' => (string) config('marque.slogan'),
            'logo' => (string) config('marque.logo'),
            'logo_carre' => (string) config('marque.logo_carre'),
            'couleurs' => [
                'primaire' => self::couleurPrimaire(),
                'secondaire' => self::couleurSecondaire(),
            ],
            'email_contact' => (string) config('marque.email_contact'),
            'telephone' => (string) config('marque.telephone'),
            'site' => config('marque.site'),
            'langues_interface' => array_values((array) config('marque.langues_interface', [])),
            'langue_defaut' => config('marque.langue_defaut'),
            'examens' => self::examens(),
            'masquer_kids' => (bool) config('marque.masquer_kids'),
            'masquer_prix_publics' => (bool) config('marque.masquer_prix_publics'),
            'mascotte' => (bool) config('marque.mascotte', true),
            'personnalisee' => self::personnalisee(),
        ];
    }

    /**
     * L'image d'apercu des liens partages (og:image). Celles de public/og/ portent
     * la marque PrePla : une autre instance montre son logo carre.
     */
    public static function imageDePartage(?string $image = null): string
    {
        if (self::personnalisee()) {
            return url('/'.ltrim((string) config('marque.logo_carre'), '/'));
        }

        return url('/'.ltrim($image ?? 'og/prepla.png', '/'));
    }

    /** PrePla garde son fichier statique ; une autre marque, la route dynamique. */
    public static function urlDuManifeste(): string
    {
        return self::manifesteStatique() ? '/manifest.json?v=4' : '/manifest.webmanifest';
    }

    private static function manifesteStatique(): bool
    {
        return ! self::personnalisee() && ! self::couleursPersonnalisees()
            && config('marque.icone_192') === '/icons/pwa-192-v4.png'
            && config('marque.icone_512') === '/icons/pwa-512-v4.png'
            && config('marque.icone_maskable') === '/icons/pwa-maskable-512-v4.png';
    }

    /**
     * Le manifeste PWA de l'instance.
     *
     * @return array<string, mixed>
     */
    public static function manifeste(): array
    {
        $chemin = public_path('manifest.json');
        $base = is_file($chemin) ? (json_decode((string) file_get_contents($chemin), true) ?: []) : [];

        if (self::manifesteStatique()) {
            return $base;
        }

        $base['name'] = self::nom().(config('marque.slogan') ? ' - '.config('marque.slogan') : '');
        $base['short_name'] = self::nomCourt();
        $base['description'] = (string) config('marque.slogan');
        $base['theme_color'] = self::couleurSecondaire();
        $base['background_color'] = self::couleurSecondaire();
        if ($langue = config('marque.langue_defaut')) {
            $base['lang'] = $langue;
        }
        $base['icons'] = [
            ['src' => config('marque.icone_192'), 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
            ['src' => config('marque.icone_512'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
            ['src' => config('marque.icone_maskable'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
        ];
        // Les captures montrent PrePla : on ne les propose pas sous une autre marque.
        unset($base['screenshots']);

        return $base;
    }

    /** Fichiers publics declares par la marque. @return array<string, string> */
    public static function fichiers(): array
    {
        $cles = ['logo', 'logo_carre', 'favicon', 'icone_192', 'icone_512', 'icone_maskable', 'icone_apple'];

        return collect($cles)->mapWithKeys(fn ($c) => [$c => (string) config("marque.$c")])->all();
    }

    private static function hexa(mixed $valeur, string $defaut): string
    {
        return is_string($valeur) && preg_match('/^#[0-9A-Fa-f]{6}$/', $valeur) ? strtoupper($valeur) : $defaut;
    }
}
