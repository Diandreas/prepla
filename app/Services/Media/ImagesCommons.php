<?php

namespace App\Services\Media;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Les illustrations des exercices, prises dans Wikimedia Commons.
 *
 * POURQUOI PAS UNE IMAGE FABRIQUEE. Le service d'images generees qu'on
 * utilisait jusqu'ici limite le gratuit a une image toutes les quarante-cinq
 * secondes, pose son filigrane sur celles qui passent, et rend une vignette
 * « Free limit reached » au-dela. Un apprenant a vu cette vignette a la place
 * de l'image, en pleine epreuve.
 *
 * Commons est l'inverse : une photographie reelle, sans filigrane, sans clef,
 * sans quota qui nous concerne. Et c'est plus juste pour une epreuve de langue
 * — le Goethe A1 montre des photos du quotidien, pas des dessins.
 *
 * CE QU'ON DOIT EN RETOUR. La plupart de ces fichiers sont sous CC BY ou
 * CC BY-SA : ils obligent a citer l'auteur et la licence. Le credit voyage donc
 * avec l'adresse, et l'ecran l'affiche. Le domaine public passe en premier
 * quand il y en a : rien a citer, rien a oublier.
 */
class ImagesCommons
{
    private const POINT_D_ENTREE = 'https://commons.wikimedia.org/w/api.php';

    /** Les licences qui n'obligent a rien. On les prefere, a resultat egal. */
    private const SANS_OBLIGATION = ['public domain', 'cc0', 'pd', 'pdm', 'no restrictions'];

    /** Commons sert des documents : on ne veut que des photographies. */
    private const EXTENSIONS = ['jpg', 'jpeg', 'png'];

    public function __construct(private int $largeur = 640) {}

    /**
     * La meilleure illustration pour ce terme, ou null.
     *
     * @return array{url: string, titre: string, auteur: string, licence: string, page: string}|null
     */
    public function chercher(string $terme): ?array
    {
        $terme = trim(preg_replace('/\s+/', ' ', $terme));
        if ($terme === '') {
            return null;
        }

        $candidats = $this->interroger($terme);
        if ($candidats === []) {
            return null;
        }

        // La recherche de Commons classe par pertinence documentaire, pas par
        // sujet : sur « sunny blue sky » son premier resultat etait un heron
        // dans un etang, et sur « city bus stop » un panneau d'arret. Pour une
        // epreuve de langue, une image a moitie juste ne vaut rien : l'apprenant
        // doit reconnaitre la chose du premier coup d'oeil.
        //
        // On reclasse donc sur le nom du fichier, qui dit ce qu'on voit, et on
        // exige qu'il porte au moins un mot de la recherche.
        $mots = $this->motsUtiles($terme);

        $notes = [];
        foreach ($candidats as $i => $candidat) {
            $notes[$i] = $this->pertinence($candidat['titre'], $mots);
        }

        $meilleure = max($notes);
        if ($meilleure === 0) {
            return null;
        }

        $retenus = array_values(array_intersect_key($candidats, array_filter($notes, fn ($n) => $n === $meilleure)));

        // A pertinence egale, le domaine public evite d'avoir a citer quoi que ce soit.
        usort($retenus, function (array $a, array $b) {
            return $this->sansObligation($b['licence']) <=> $this->sansObligation($a['licence']);
        });

        return $retenus[0];
    }

    /** Les mots de la recherche qui portent du sens. */
    private function motsUtiles(string $terme): array
    {
        $vides = ['a', 'an', 'the', 'of', 'in', 'on', 'with', 'and', 'at', 'for'];

        return array_values(array_filter(
            preg_split('/\s+/u', mb_strtolower($terme)) ?: [],
            fn ($mot) => mb_strlen($mot) >= 3 && ! in_array($mot, $vides, true),
        ));
    }

    /** Combien de mots de la recherche le nom du fichier porte-t-il ? */
    private function pertinence(string $titre, array $mots): int
    {
        $titre = mb_strtolower($titre);
        $trouves = 0;
        foreach ($mots as $mot) {
            // Le singulier et le pluriel comptent pour le meme mot : on cherche
            // « bananas » et le fichier s'appelle « banana ».
            $racine = rtrim($mot, 's');
            if ($racine !== '' && str_contains($titre, $racine)) {
                $trouves++;
            }
        }

        return $trouves;
    }

    /** @return array<int, array{url: string, titre: string, auteur: string, licence: string, page: string}> */
    private function interroger(string $terme): array
    {
        try {
            $reponse = Http::withHeaders([
                // Commons demande qu'on se nomme ; un agent vide se fait refuser.
                'User-Agent' => 'PrePla/1.0 (https://prepla.mirlab.cloud)',
            ])->timeout(25)->get(self::POINT_D_ENTREE, [
                'action' => 'query',
                'format' => 'json',
                'generator' => 'search',
                'gsrsearch' => $terme,
                'gsrnamespace' => 6,      // l'espace des fichiers
                'gsrlimit' => 8,
                'prop' => 'imageinfo',
                'iiprop' => 'url|extmetadata|mime',
                'iiurlwidth' => $this->largeur,
            ]);

            if (! $reponse->successful()) {
                Log::warning('Commons a refuse la recherche', [
                    'terme' => $terme, 'statut' => $reponse->status(),
                ]);

                return [];
            }

            $pages = $reponse->json('query.pages') ?? [];
        } catch (\Throwable $e) {
            Log::warning('Commons injoignable', ['terme' => $terme, 'erreur' => $e->getMessage()]);

            return [];
        }

        $candidats = [];
        foreach ($pages as $page) {
            $info = $page['imageinfo'][0] ?? null;
            $url = $info['thumburl'] ?? null;
            if (! $info || ! is_string($url) || $url === '') {
                continue;
            }
            if (! in_array(strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION)), self::EXTENSIONS, true)) {
                continue;
            }

            $meta = $info['extmetadata'] ?? [];
            $candidats[] = [
                'url' => $url,
                // Le nom du fichier, pour qu'on puisse RELIRE ce qui a ete
                // choisi. La recherche de Commons n'est pas semantique : sur
                // « bread loaf » elle a rendu un pain egyptien de musee. Une
                // image d'examen se verifie, elle ne se devine pas.
                'titre' => (string) ($page['title'] ?? ''),
                'auteur' => $this->texte($meta['Artist']['value'] ?? ''),
                'licence' => $this->texte($meta['LicenseShortName']['value'] ?? ''),
                'page' => $info['descriptionurl'] ?? '',
            ];
        }

        return $candidats;
    }

    private function sansObligation(string $licence): bool
    {
        $licence = mb_strtolower($licence);
        foreach (self::SANS_OBLIGATION as $libre) {
            if (str_contains($licence, $libre)) {
                return true;
            }
        }

        return false;
    }

    /** Les metadonnees de Commons arrivent en HTML : on n'en garde que le texte. */
    private function texte(string $html): string
    {
        $propre = trim(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        return mb_substr(preg_replace('/\s+/u', ' ', $propre) ?? '', 0, 120);
    }

    /** Le credit a afficher sous l'image, ou une chaine vide si rien n'est du. */
    public function credit(array $image): string
    {
        if ($this->sansObligation($image['licence'] ?? '')) {
            return '';
        }

        $morceaux = array_filter([$image['auteur'] ?? '', $image['licence'] ?? '']);

        return $morceaux === [] ? '' : implode(' · ', $morceaux).' · Wikimedia Commons';
    }
}
