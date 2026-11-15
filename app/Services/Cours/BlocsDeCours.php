<?php

namespace App\Services\Cours;

use App\Models\CenterMedia;
use App\Models\CourseBlock;
use App\Models\Exercise;
use App\Models\LanguageCenter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

/**
 * Les types de blocs d'une seance : leur contenu (valide et normalise a
 * l'enregistrement), ce que l'eleve en recoit, et la correction des quiz.
 *
 * Contenu par type :
 * - texte        : html (assaini)
 * - video        : source media|youtube|vimeo, media_id ou video_id, seuil_visionnage (0-100), duree_secondes
 * - audio        : media_id, texte_html, mode_texte aucun|transcription|lecture, texte_masque, ecoutes_max
 * - document     : media_id (pdf ou image), telechargeable
 * - exercice     : exercise_id (banque PrePla ou exercice publie du centre), seuil
 * - quiz         : questions [qcm|vrai_faux|trous], seuil
 * - ecrit        : consigne, mots_min, mots_max
 * - oral         : consigne, duree_max_secondes
 * - vocabulaire  : mots [{mot, traduction, exemple}]
 * - lien         : url, libelle
 * - consigne     : texte (message de l'enseignant)
 * - live         : url, date, live_class_id (si le module live existe)
 */
class BlocsDeCours
{
    public const TYPES = [
        'texte' => 'Texte',
        'video' => 'Vidéo',
        'audio' => 'Audio',
        'document' => 'Document',
        'exercice' => 'Exercice',
        'quiz' => 'Quiz',
        'ecrit' => 'Production écrite',
        'oral' => 'Production orale',
        'vocabulaire' => 'Vocabulaire',
        'lien' => 'Lien',
        'consigne' => 'Consigne',
        'live' => 'Séance live',
    ];

    /** Contenu par defaut d'un bloc qu'on vient d'ajouter. */
    public static function vide(string $type): array
    {
        return match ($type) {
            'texte' => ['html' => ''],
            'video' => ['source' => 'youtube', 'media_id' => null, 'video_id' => null, 'url' => '', 'seuil_visionnage' => 0, 'duree_secondes' => null],
            'audio' => ['media_id' => null, 'texte_html' => '', 'mode_texte' => 'aucun', 'texte_masque' => true, 'ecoutes_max' => null],
            'document' => ['media_id' => null, 'telechargeable' => true],
            'exercice' => ['exercise_id' => null, 'seuil' => null],
            'quiz' => ['questions' => [], 'seuil' => 60],
            'ecrit' => ['consigne' => '', 'mots_min' => null, 'mots_max' => null],
            'oral' => ['consigne' => '', 'duree_max_secondes' => 120],
            'vocabulaire' => ['mots' => []],
            'lien' => ['url' => '', 'libelle' => ''],
            'consigne' => ['texte' => ''],
            'live' => ['url' => '', 'date' => null, 'live_class_id' => null],
            default => [],
        };
    }

    /**
     * Valide et normalise le contenu envoye par l'editeur.
     *
     * @throws ValidationException
     */
    public function normaliser(string $type, array $c, LanguageCenter $centre): array
    {
        $erreur = fn (string $m) => throw ValidationException::withMessages(['contenu' => $m]);
        $entier = fn ($v, int $min, int $max) => ($v === null || $v === '') ? null : max($min, min($max, (int) $v));
        $texte = fn ($v, int $max) => mb_substr(trim((string) ($v ?? '')), 0, $max);

        switch ($type) {
            case 'texte':
                return ['html' => HtmlAssaini::nettoyer($texte($c['html'] ?? '', 100000))];

            case 'video':
                $source = in_array($c['source'] ?? '', ['media', 'youtube', 'vimeo'], true) ? $c['source'] : 'youtube';
                $sortie = ['source' => $source, 'media_id' => null, 'video_id' => null, 'url' => '',
                    'seuil_visionnage' => $entier($c['seuil_visionnage'] ?? 0, 0, 100) ?? 0,
                    'duree_secondes' => $entier($c['duree_secondes'] ?? null, 1, 36000)];
                if ($source === 'media') {
                    $sortie['media_id'] = $this->media($c['media_id'] ?? null, $centre, ['video'], $erreur);
                } else {
                    $url = $texte($c['url'] ?? '', 500);
                    $sortie['url'] = $url;
                    $sortie['video_id'] = $url === '' ? null : (self::idVideo($source, $url) ?? $erreur('Lien vidéo non reconnu.'));
                }

                return $sortie;

            case 'audio':
                $mode = in_array($c['mode_texte'] ?? '', ['aucun', 'transcription', 'lecture'], true) ? $c['mode_texte'] : 'aucun';

                return [
                    'media_id' => $this->media($c['media_id'] ?? null, $centre, ['audio'], $erreur),
                    'texte_html' => HtmlAssaini::nettoyer($texte($c['texte_html'] ?? '', 100000)),
                    'mode_texte' => $mode,
                    'texte_masque' => (bool) ($c['texte_masque'] ?? true),
                    'ecoutes_max' => $entier($c['ecoutes_max'] ?? null, 1, 20),
                ];

            case 'document':
                return [
                    'media_id' => $this->media($c['media_id'] ?? null, $centre, ['document', 'image'], $erreur),
                    'telechargeable' => (bool) ($c['telechargeable'] ?? true),
                ];

            case 'exercice':
                $id = $entier($c['exercise_id'] ?? null, 1, PHP_INT_MAX);
                if ($id !== null && ! $this->exerciceAutorise($id, $centre)) {
                    $erreur('Cet exercice ne peut pas entrer dans un parcours : brouillon ou exercice d’un autre centre.');
                }

                return ['exercise_id' => $id, 'seuil' => $entier($c['seuil'] ?? null, 1, 100)];

            case 'quiz':
                $questions = [];
                foreach (array_slice((array) ($c['questions'] ?? []), 0, 50) as $q) {
                    $q = (array) $q;
                    $sorte = $q['type'] ?? 'qcm';
                    if ($sorte === 'qcm') {
                        $choix = array_values(array_filter(array_map(fn ($x) => $texte($x, 300), array_slice((array) ($q['choix'] ?? []), 0, 8)), fn ($x) => $x !== ''));
                        $questions[] = ['type' => 'qcm', 'enonce' => $texte($q['enonce'] ?? '', 1000), 'choix' => $choix,
                            'bonne' => max(0, min(max(count($choix) - 1, 0), (int) ($q['bonne'] ?? 0)))];
                    } elseif ($sorte === 'vrai_faux') {
                        $questions[] = ['type' => 'vrai_faux', 'enonce' => $texte($q['enonce'] ?? '', 1000), 'reponse' => (bool) ($q['reponse'] ?? true)];
                    } elseif ($sorte === 'trous') {
                        $questions[] = ['type' => 'trous', 'texte' => $texte($q['texte'] ?? '', 2000)];
                    }
                }

                return ['questions' => $questions, 'seuil' => $entier($c['seuil'] ?? 60, 1, 100) ?? 60];

            case 'ecrit':
                return ['consigne' => $texte($c['consigne'] ?? '', 4000), 'mots_min' => $entier($c['mots_min'] ?? null, 1, 5000),
                    'mots_max' => $entier($c['mots_max'] ?? null, 1, 5000)];

            case 'oral':
                return ['consigne' => $texte($c['consigne'] ?? '', 4000), 'duree_max_secondes' => $entier($c['duree_max_secondes'] ?? 120, 10, 900) ?? 120];

            case 'vocabulaire':
                $mots = [];
                foreach (array_slice((array) ($c['mots'] ?? []), 0, 200) as $m) {
                    $m = (array) $m;
                    if ($texte($m['mot'] ?? '', 200) === '') {
                        continue;
                    }
                    $mots[] = ['mot' => $texte($m['mot'], 200), 'traduction' => $texte($m['traduction'] ?? '', 200), 'exemple' => $texte($m['exemple'] ?? '', 500)];
                }

                return ['mots' => $mots];

            case 'lien':
                $url = $texte($c['url'] ?? '', 1000);
                if ($url !== '' && ! preg_match('#^https?://#i', $url)) {
                    $erreur('Le lien doit commencer par https://');
                }

                return ['url' => $url, 'libelle' => $texte($c['libelle'] ?? '', 200)];

            case 'consigne':
                return ['texte' => $texte($c['texte'] ?? '', 4000)];

            case 'live':
                $url = $texte($c['url'] ?? '', 1000);
                if ($url !== '' && ! preg_match('#^https://#i', $url)) {
                    $erreur('Le lien de la séance live doit commencer par https://');
                }
                $live = $entier($c['live_class_id'] ?? null, 1, PHP_INT_MAX);
                if ($live !== null && (! Schema::hasTable('live_classes')
                    || ! DB::table('live_classes')->where('id', $live)->where('center_id', $centre->id)->exists())) {
                    $live = null;
                }
                $date = $c['date'] ?? null;

                return ['url' => $url, 'date' => $date ? date('Y-m-d\TH:i', strtotime((string) $date) ?: time()) : null, 'live_class_id' => $live];
        }

        $erreur('Type de bloc inconnu.');
    }

    /** Un exercice que ce centre peut mettre dans un parcours : jamais un brouillon. */
    public function exerciceAutorise(int $id, LanguageCenter $centre): bool
    {
        return Exercise::query()->whereKey($id)->horsBrouillon()
            ->where(fn ($q) => $q->where('center_id', $centre->id)->orWhere(fn ($b) => $b->whereNull('center_id')
                ->whereNull('lesson_id')->whereNull('node_id')->whereNull('mock_exam_id')))
            ->exists();
    }

    /** @param  list<string>  $types */
    private function media($id, LanguageCenter $centre, array $types, callable $erreur): ?int
    {
        if ($id === null || $id === '') {
            return null;
        }
        $media = CenterMedia::where('center_id', $centre->id)->whereKey((int) $id)->first();
        if (! $media || ! in_array($media->type, $types, true)) {
            $erreur('Ce fichier n’est pas dans la médiathèque du centre.');
        }

        return $media->id;
    }

    public static function idVideo(string $source, string $url): ?string
    {
        if ($source === 'youtube' && preg_match('#(?:youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/)|youtu\.be/)([A-Za-z0-9_-]{11})#', $url, $m)) {
            return $m[1];
        }
        if ($source === 'vimeo' && preg_match('#vimeo\.com/(?:video/)?(\d{5,12})#', $url, $m)) {
            return $m[1];
        }

        return null;
    }

    /**
     * Un media tel que le lecteur le recoit. Un fichier venu du Google Drive du
     * centre (provider = google_drive, quand ce module est installe) se lit
     * dans le lecteur integre de Drive.
     */
    public static function ficheMedia(?CenterMedia $m): ?array
    {
        if (! $m) {
            return null;
        }
        $drive = ($m->getAttribute('provider') ?? null) === 'google_drive' && $m->getAttribute('drive_file_id');

        return [
            'id' => $m->id,
            'type' => $m->type,
            'titre' => $m->title ?: $m->original_name,
            'mime' => $m->mime,
            'drive' => (bool) $drive,
            'url' => $drive ? 'https://drive.google.com/file/d/'.rawurlencode((string) $m->getAttribute('drive_file_id')).'/preview' : $m->url,
        ];
    }

    /**
     * Ce que l'eleve recoit d'un bloc : jamais les bonnes reponses d'un quiz.
     *
     * @param  array<int, CenterMedia>  $medias
     */
    public function pourEleve(CourseBlock $b, array $medias): array
    {
        $c = $b->contenu ?? [];
        $media = fn () => self::ficheMedia($medias[$c['media_id'] ?? 0] ?? null);

        $contenu = match ($b->type) {
            'video' => ['source' => $c['source'] ?? 'youtube', 'video_id' => $c['video_id'] ?? null,
                'media' => ($c['source'] ?? '') === 'media' ? $media() : null,
                'seuil_visionnage' => (int) ($c['seuil_visionnage'] ?? 0), 'duree_secondes' => $c['duree_secondes'] ?? null],
            'audio' => ['media' => $media(), 'texte_html' => $c['texte_html'] ?? '', 'mode_texte' => $c['mode_texte'] ?? 'aucun',
                'texte_masque' => (bool) ($c['texte_masque'] ?? true), 'ecoutes_max' => $c['ecoutes_max'] ?? null],
            'document' => ['media' => $media(), 'telechargeable' => (bool) ($c['telechargeable'] ?? true)],
            'quiz' => ['seuil' => (int) ($c['seuil'] ?? 60), 'questions' => array_map(fn ($q) => match ($q['type']) {
                'qcm' => ['type' => 'qcm', 'enonce' => $q['enonce'], 'choix' => $q['choix']],
                'vrai_faux' => ['type' => 'vrai_faux', 'enonce' => $q['enonce']],
                'trous' => ['type' => 'trous', 'segments' => self::segmentsTrous($q['texte'])],
                default => [],
            }, $c['questions'] ?? [])],
            default => $c,
        };

        return ['id' => $b->id, 'type' => $b->type, 'titre' => $b->titre, 'obligatoire' => $b->obligatoire, 'contenu' => $contenu];
    }

    /** « Ich [bin] müde » -> ['Ich ', null, ' müde'] : null marque un trou. */
    public static function segmentsTrous(string $texte): array
    {
        $segments = [];
        foreach (preg_split('/(\[[^\]]+\])/u', $texte, -1, PREG_SPLIT_DELIM_CAPTURE) as $morceau) {
            if ($morceau === '') {
                continue;
            }
            $segments[] = preg_match('/^\[[^\]]+\]$/u', $morceau) ? null : $morceau;
        }

        return $segments;
    }

    /**
     * Corrige un quiz : % de bonnes reponses (chaque trou compte pour un point).
     *
     * @param  array<int, mixed>  $reponses  une reponse par question (un tableau pour un texte a trous)
     * @return array{score: int, details: list<bool>}
     */
    public function corrigerQuiz(array $contenu, array $reponses): array
    {
        $points = 0;
        $total = 0;
        $details = [];
        foreach (($contenu['questions'] ?? []) as $i => $q) {
            $r = $reponses[$i] ?? null;
            if ($q['type'] === 'trous') {
                preg_match_all('/\[([^\]]+)\]/u', $q['texte'], $m);
                foreach ($m[1] as $j => $attendu) {
                    $total++;
                    $possibles = array_map(fn ($x) => self::comparable($x), explode('|', $attendu));
                    $ok = in_array(self::comparable((string) (((array) $r)[$j] ?? '')), $possibles, true);
                    $points += $ok ? 1 : 0;
                    $details[] = $ok;
                }

                continue;
            }
            $total++;
            $ok = $q['type'] === 'qcm'
                ? ($r !== null && $r !== '' && (int) $r === (int) $q['bonne'])
                : ($r !== null && filter_var($r, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) === (bool) $q['reponse']);
            $points += $ok ? 1 : 0;
            $details[] = $ok;
        }

        return ['score' => $total === 0 ? 100 : (int) round($points * 100 / $total), 'details' => $details];
    }

    private static function comparable(string $s): string
    {
        return mb_strtolower(preg_replace('/\s+/u', ' ', trim($s)));
    }
}
