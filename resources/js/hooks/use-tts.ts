import { useCallback, useRef, useState } from 'react';

interface UseTtsReturn {
    speak: (text: string, lang?: string) => void;
    stop: () => void;
    isSpeaking: boolean;
    isSupported: boolean;
}

const VOICE_MAP: Record<string, string[]> = {
    en: ['en-US', 'en-GB', 'English'],
    fr: ['fr-FR', 'French'],
    de: ['de-DE', 'German'],
};

// Locale BCP-47 par défaut par langue, utilisée même quand aucune voix exacte
// n'est installée — empêche le navigateur de retomber sur l'anglais.
const DEFAULT_LOCALE: Record<string, string> = {
    en: 'en-US',
    fr: 'fr-FR',
    de: 'de-DE',
};

// Les voix du navigateur se chargent de façon asynchrone : getVoices() peut
// renvoyer [] au premier appel. On attend l'événement voiceschanged.
function ensureVoices(): Promise<void> {
    return new Promise((resolve) => {
        if (typeof window === 'undefined' || !('speechSynthesis' in window)) {
            resolve();
            return;
        }
        if (window.speechSynthesis.getVoices().length > 0) {
            resolve();
            return;
        }
        const done = () => {
            window.speechSynthesis.removeEventListener('voiceschanged', done);
            resolve();
        };
        window.speechSynthesis.addEventListener('voiceschanged', done);
        // Filet de sécurité si l'événement ne se déclenche jamais
        setTimeout(done, 1000);
    });
}

// Normalise n'importe quel identifiant de langue (slug DB "english"/"french"/"german",
// code court "en"/"fr"/"de", ou locale "fr-FR") vers un code ISO court reconnu partout.
const LANG_ALIASES: Record<string, string> = {
    english: 'en',
    french: 'fr',
    german: 'de',
    en: 'en',
    fr: 'fr',
    de: 'de',
};

function normalizeLang(lang: string): string {
    const key = (lang ?? '').toLowerCase().slice(0, 2);
    return LANG_ALIASES[(lang ?? '').toLowerCase()] ?? LANG_ALIASES[key] ?? 'en';
}

function findVoice(lang: string): SpeechSynthesisVoice | null {
    const voices = window.speechSynthesis.getVoices();
    const candidates = VOICE_MAP[lang] ?? [lang];

    for (const candidate of candidates) {
        const match = voices.find(
            (v) => v.lang.startsWith(candidate) || v.name.toLowerCase().includes(candidate.toLowerCase()),
        );
        if (match) return match;
    }

    return voices.find((v) => v.lang.startsWith(lang)) ?? null;
}

export function useTts(): UseTtsReturn {
    const [isSpeaking, setIsSpeaking] = useState(false);
    const audioRef = useRef<HTMLAudioElement | null>(null);
    const utteranceRef = useRef<SpeechSynthesisUtterance | null>(null);
    const hasBrowserTts = typeof window !== 'undefined' && 'speechSynthesis' in window;

    const stopAll = useCallback(() => {
        // Stop server audio
        if (audioRef.current) {
            audioRef.current.pause();
            audioRef.current.currentTime = 0;
            audioRef.current = null;
        }
        // Stop browser TTS
        if (hasBrowserTts) {
            window.speechSynthesis.cancel();
        }
        setIsSpeaking(false);
    }, [hasBrowserTts]);

    const speakWithBrowser = useCallback(
        (text: string, lang: string) => {
            if (!hasBrowserTts) return;

            // lang est déjà normalisé en code court ("en"/"fr"/"de") par speak().
            const code = normalizeLang(lang);

            ensureVoices().then(() => {
                window.speechSynthesis.cancel();
                const utterance = new SpeechSynthesisUtterance(text);
                utterance.rate = 0.9;
                utterance.pitch = 1;

                const voice = findVoice(code);
                if (voice) {
                    utterance.voice = voice;
                    utterance.lang = voice.lang;
                } else {
                    // Pas de voix exacte : on force au moins la bonne locale
                    // pour éviter la lecture en anglais par défaut.
                    utterance.lang = DEFAULT_LOCALE[code] ?? code;
                }

                utterance.onstart = () => setIsSpeaking(true);
                utterance.onend = () => setIsSpeaking(false);
                utterance.onerror = () => setIsSpeaking(false);

                utteranceRef.current = utterance;
                window.speechSynthesis.speak(utterance);
            });
        },
        [hasBrowserTts],
    );

    const speak = useCallback(
        (text: string, lang: string = 'en') => {
            stopAll();
            if (!text || !text.trim()) return;
            setIsSpeaking(true);

            // Normalise le slug DB ("french") / locale ("fr-FR") en code court ("fr")
            // accepté à la fois par l'API Deepgram et le fallback navigateur.
            const code = normalizeLang(lang);

            // Try Deepgram server API first
            const csrfToken = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content;

            fetch('/api/tts/speak', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ text, lang: code }),
            })
                .then((res) => {
                    if (!res.ok) throw new Error('TTS API failed');
                    return res.json();
                })
                .then((data) => {
                    if (data.audio_url) {
                        const audio = new Audio(data.audio_url);
                        audioRef.current = audio;
                        audio.onplay = () => setIsSpeaking(true);
                        audio.onended = () => setIsSpeaking(false);
                        audio.onerror = () => {
                            // Fallback to browser TTS if audio fails
                            speakWithBrowser(text, code);
                        };
                        audio.play().catch(() => speakWithBrowser(text, code));
                    } else {
                        throw new Error('No audio URL');
                    }
                })
                .catch(() => {
                    // Fallback to browser TTS
                    speakWithBrowser(text, code);
                });
        },
        [stopAll, speakWithBrowser],
    );

    return { speak, stop: stopAll, isSpeaking, isSupported: true };
}
