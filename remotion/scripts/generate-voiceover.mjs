import { readFileSync } from 'node:fs';
import { mkdir, writeFile } from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const root = path.resolve(__dirname, '..');
const episodesDir = path.join(root, 'src', 'data');
const audioDir = path.join(root, 'public', 'audio');

// Voix premium sélectionnées sur le compte ElevenLabs Prepla pour le contenu
// éducatif réseaux sociaux (voir docs/content-studio.md).
const VOICES = {
    liam: 'TX3LPaxmHKxFdv7VOQHJ', // Anglais — Energetic, Social Media Creator
    sophie: 'BewlJwjEWiFLWoXrbGMf', // Français — Publicité et Réseaux sociaux
    anna: 'DEZHhPbmb8LVZmWufkCh', // Allemand — Excited & Journalistic
};

function loadApiKey() {
    const fromEnv = process.env.ELEVENLABS_API_KEY;
    if (fromEnv) return fromEnv;

    const envPath = path.join(root, '.env');
    const content = readFileSync(envPath, 'utf-8');
    for (const line of content.split('\n')) {
        const trimmed = line.trim();
        if (!trimmed || trimmed.startsWith('#')) continue;
        const idx = trimmed.indexOf('=');
        if (idx === -1) continue;
        if (trimmed.slice(0, idx) === 'ELEVENLABS_API_KEY') {
            return trimmed.slice(idx + 1).trim();
        }
    }
    throw new Error('ELEVENLABS_API_KEY manquante (remotion/.env ou variable d\'environnement).');
}

async function generate(episodeFile, voiceKey, outFile) {
    const apiKey = loadApiKey();
    const voiceId = VOICES[voiceKey];
    if (!voiceId) {
        throw new Error(`Voix inconnue "${voiceKey}". Choix possibles: ${Object.keys(VOICES).join(', ')}`);
    }

    const episode = JSON.parse(readFileSync(path.join(episodesDir, episodeFile), 'utf-8'));
    if (!episode.narration) {
        throw new Error(`Le fichier ${episodeFile} n'a pas de champ "narration".`);
    }

    const response = await fetch(`https://api.elevenlabs.io/v1/text-to-speech/${voiceId}`, {
        method: 'POST',
        headers: {
            'xi-api-key': apiKey,
            'Content-Type': 'application/json',
            Accept: 'audio/mpeg',
        },
        body: JSON.stringify({
            text: episode.narration,
            model_id: 'eleven_multilingual_v2',
            voice_settings: {
                stability: 0.4,
                similarity_boost: 0.8,
                style: 0.6,
                use_speaker_boost: true,
            },
        }),
    });

    if (!response.ok) {
        const errText = await response.text();
        throw new Error(`ElevenLabs TTS a échoué (${response.status}): ${errText}`);
    }

    const buffer = Buffer.from(await response.arrayBuffer());
    await mkdir(audioDir, { recursive: true });
    const outPath = path.join(audioDir, outFile);
    await writeFile(outPath, buffer);
    console.log(`Voix off générée : ${path.relative(root, outPath)} (${buffer.byteLength} octets, voix "${voiceKey}")`);
}

const [, , episodeFile, voiceKey, outFile] = process.argv;
if (!episodeFile || !voiceKey || !outFile) {
    console.error('Usage: node scripts/generate-voiceover.mjs <episode.json> <liam|sophie|anna> <sortie.mp3>');
    process.exit(1);
}

await generate(episodeFile, voiceKey, outFile);
