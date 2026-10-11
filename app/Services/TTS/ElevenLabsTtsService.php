<?php

namespace App\Services\TTS;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Synthèse vocale ElevenLabs, pour les DIALOGUES.
 *
 * Deepgram lit très bien un monologue, et il fait tout le volume du catalogue.
 * Mais à l'examen, l'écoute est presque toujours une conversation : au Goethe,
 * au TCF, au TOEFL, deux personnes se parlent. Une seule voix qui lit les deux
 * interlocuteurs d'un bout à l'autre est le défaut le plus audible de la
 * simulation — l'apprenant ne peut pas distinguer qui dit quoi, ce qui est
 * précisément une des compétences évaluées.
 *
 * Ce service découpe un passage en répliques et donne une voix à chaque
 * locuteur, puis recolle le tout en un seul fichier.
 *
 * Il n'est utilisé que là où il change le résultat : le quota ElevenLabs se
 * compte en caractères, et le catalogue entier en demande bien plus qu'un
 * forfait mensuel courant.
 */
class ElevenLabsTtsService
{
    private string $cle;

    private string $base = 'https://api.elevenlabs.io/v1';

    /**
     * Les voix, par langue et par rang de locuteur. Ce sont des voix « premade »,
     * disponibles sur tous les forfaits. Le modèle multilingue leur permet de
     * parler les trois langues ; une voix native sonne toutefois plus juste, et
     * c'est pourquoi la deuxième voix allemande reste à choisir.
     *
     * @var array<string, list<string>>
     */
    public const VOIX = [
        'fr' => ['CqTrL0ThT2GJVJEIiLcY', 'onwK4e9ZLuTAKqWW03F9'],   // Océane, Daniel
        'de' => ['DEZHhPbmb8LVZmWufkCh', 'JBFqnCBsd6RMkjVDRZzb'],   // Anna, George
        'en' => ['Xb7hH8MSUJpSbSDYk0k2', 'onwK4e9ZLuTAKqWW03F9'],   // Alice, Daniel
    ];

    /** Le modèle multilingue : la même voix sait lire le français, l'anglais et l'allemand. */
    private const MODELE = 'eleven_multilingual_v2';

    public function __construct()
    {
        // Même piège que Mistral et Deepgram : la clé est présente mais nulle
        // quand la variable d'environnement n'est pas posée, donc la valeur par
        // défaut de config() ne s'applique jamais.
        $this->cle = config('services.elevenlabs.api_key') ?? '';
    }

    public function disponible(): bool
    {
        return $this->cle !== '';
    }

    /**
     * Les répliques d'un passage, dans l'ordre, avec le rang de leur locuteur.
     *
     * Reconnaît les deux écritures que produit le générateur : « A: … / B: … »
     * et les tirets de dialogue. Un passage sans marque de locuteur revient en
     * une seule réplique — c'est un monologue, et il n'a rien à faire ici.
     *
     * @return list<array{locuteur: int, texte: string}>
     */
    public function repliques(string $passage): array
    {
        $lignes = preg_split('/\R+/u', trim($passage)) ?: [];
        $repliques = [];
        $locuteurs = [];

        foreach ($lignes as $ligne) {
            $ligne = trim($ligne);
            if ($ligne === '') {
                continue;
            }

            // « A: », « Anna: », « — », « - »
            if (preg_match('/^([\p{Lu}][\p{L}]*)\s*:\s*(.+)$/u', $ligne, $trouve)) {
                $nom = $trouve[1];
                $texte = trim($trouve[2]);
            } elseif (preg_match('/^[-—–]\s*(.+)$/u', $ligne, $trouve)) {
                // Les tirets alternent : premier tiret au locuteur 0, puis 1, puis 0…
                $nom = 'tiret-'.(count($repliques) % 2);
                $texte = trim($trouve[1]);
            } else {
                // Suite de la réplique précédente, ou monologue.
                if ($repliques !== []) {
                    $repliques[count($repliques) - 1]['texte'] .= ' '.$ligne;

                    continue;
                }
                $nom = 'seul';
                $texte = $ligne;
            }

            if (! isset($locuteurs[$nom])) {
                $locuteurs[$nom] = count($locuteurs);
            }

            $repliques[] = ['locuteur' => $locuteurs[$nom], 'texte' => $texte];
        }

        return $repliques;
    }

    /** Ce passage est-il un dialogue, c'est-à-dire vaut-il une voix par locuteur ? */
    public function estUnDialogue(string $passage): bool
    {
        $repliques = $this->repliques($passage);

        return count(array_unique(array_column($repliques, 'locuteur'))) > 1;
    }

    /**
     * Fabrique l'enregistrement du passage et renvoie son adresse publique.
     *
     * Un monologue est rendu d'une seule voix ; un dialogue reçoit une voix par
     * locuteur, et les répliques sont recollées dans l'ordre.
     */
    public function parler(string $passage, string $langue = 'fr'): ?string
    {
        if (! $this->disponible() || trim($passage) === '') {
            return null;
        }

        $voix = self::VOIX[$langue] ?? self::VOIX['en'];
        $nom = 'tts/el-'.md5($passage.$langue.implode(',', $voix)).'.mp3';

        if (Storage::disk('public')->exists($nom)) {
            return Storage::url($nom);
        }

        $morceaux = [];
        foreach ($this->repliques($passage) as $replique) {
            $audio = $this->uneReplique($replique['texte'], $voix[$replique['locuteur'] % count($voix)]);
            if ($audio === null) {
                return null;   // un dialogue amputé d'une réplique n'est pas servable
            }
            $morceaux[] = $audio;
        }

        if ($morceaux === []) {
            return null;
        }

        // Le MP3 se concatène tel quel : les trames se suivent sans en-tête global.
        Storage::disk('public')->put($nom, implode('', $morceaux));

        return Storage::url($nom);
    }

    private function uneReplique(string $texte, string $voix): ?string
    {
        try {
            $reponse = Http::withHeaders([
                'xi-api-key' => $this->cle,
                'Accept' => 'audio/mpeg',
            ])
                ->timeout(60)
                ->post("{$this->base}/text-to-speech/{$voix}", [
                    'text' => $texte,
                    'model_id' => self::MODELE,
                    'voice_settings' => ['stability' => 0.5, 'similarity_boost' => 0.75],
                ]);

            if ($reponse->successful() && strlen($reponse->body()) > 500) {
                return $reponse->body();
            }

            Log::warning('ElevenLabs a refuse une replique', [
                'statut' => $reponse->status(),
                'corps' => substr($reponse->body(), 0, 200),
            ]);
        } catch (\Throwable $e) {
            Log::error('ElevenLabs injoignable', ['erreur' => $e->getMessage()]);
        }

        return null;
    }
}
