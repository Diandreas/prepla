<?php

namespace App\Services\Center\Import;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Lit un fichier d'import du centre (CSV, ou JSON pour les exercices) en lignes
 * associatives aux en-tetes normalises : minuscules, sans accents, « _ ».
 *
 * Separateur « ; » ou « , » detecte sur la ligne d'en-tete (Excel francais
 * enregistre en « ; »). La marque d'ordre des octets d'Excel est retiree.
 */
class LecteurFichier
{
    /** Normalise un en-tete : « Téléphone » -> « telephone », « Bonne réponse » -> « bonne_reponse ». */
    public static function cle(string $entete): string
    {
        return Str::of(Str::ascii(trim($entete)))->lower()->replaceMatches('/[^a-z0-9]+/', '_')->trim('_')->toString();
    }

    /**
     * @return list<array<string, mixed>> Chaque ligne, avec « _ligne » : son numero dans le fichier.
     */
    public static function lignes(UploadedFile $fichier, int $maxLignes, bool $jsonPermis = false): array
    {
        $texte = (string) file_get_contents($fichier->getRealPath());
        $texte = preg_replace('/^\xEF\xBB\xBF/', '', $texte) ?? $texte;

        if (! mb_check_encoding($texte, 'UTF-8')) {
            // Un CSV enregistre par Excel en Windows-1252 : on le convertit plutot que de refuser.
            $texte = mb_convert_encoding($texte, 'UTF-8', 'Windows-1252');
        }

        $ext = strtolower($fichier->getClientOriginalExtension());
        $lignes = ($jsonPermis && ($ext === 'json' || str_starts_with(ltrim($texte), '[')))
            ? self::depuisJson($texte)
            : self::depuisCsv($texte);

        if ($lignes === []) {
            throw ValidationException::withMessages(['fichier' => 'Le fichier ne contient aucune ligne à importer.']);
        }
        if (count($lignes) > $maxLignes) {
            throw ValidationException::withMessages(['fichier' => "Le fichier dépasse la limite de {$maxLignes} lignes. Découpez-le en plusieurs fichiers."]);
        }

        return $lignes;
    }

    /** @return list<array<string, mixed>> */
    private static function depuisCsv(string $texte): array
    {
        $brutes = preg_split('/\r\n|\n|\r/', $texte) ?: [];
        // Les lignes vides ne comptent pas, mais gardent leur numero pour que
        // l'erreur designe la bonne ligne du fichier.
        $entete = null;
        $sep = ',';
        $lignes = [];
        foreach ($brutes as $i => $brute) {
            if (trim($brute) === '') {
                continue;
            }
            if ($entete === null) {
                $sep = substr_count($brute, ';') > substr_count($brute, ',') ? ';' : ',';
                $entete = array_map(fn ($e) => self::cle((string) $e), str_getcsv($brute, $sep, '"', ''));
                continue;
            }
            $valeurs = str_getcsv($brute, $sep, '"', '');
            $ligne = ['_ligne' => $i + 1];
            foreach ($entete as $k => $cle) {
                if ($cle !== '') {
                    $ligne[$cle] = trim((string) ($valeurs[$k] ?? ''));
                }
            }
            $lignes[] = $ligne;
        }

        return $lignes;
    }

    /** @return list<array<string, mixed>> */
    private static function depuisJson(string $texte): array
    {
        $donnees = json_decode($texte, true);
        if (! is_array($donnees) || ! array_is_list($donnees)) {
            throw ValidationException::withMessages(['fichier' => 'Le JSON doit être une liste d\'objets : [{"question": …}, …].']);
        }

        $lignes = [];
        foreach ($donnees as $i => $objet) {
            $ligne = ['_ligne' => $i + 1];
            if (is_array($objet) && ! array_is_list($objet)) {
                foreach ($objet as $cle => $valeur) {
                    $ligne[self::cle((string) $cle)] = is_string($valeur) ? trim($valeur) : $valeur;
                }
            }
            $lignes[] = $ligne;
        }

        return $lignes;
    }
}
