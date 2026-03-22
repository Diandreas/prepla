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

            window.speechSynthesis.cancel();
            const utterance = new SpeechSynthesisUtterance(text);
            utterance.rate = 0.9;
            utterance.pitch = 1;

            const voice = findVoice(lang);
            if (voice) {
                utterance.voice = voice;
                utterance.lang = voice.lang;
            } else {
                utterance.lang = lang;
            }

            utterance.onstart = () => setIsSpeaking(true);
            utterance.onend = () => setIsSpeaking(false);
            utterance.onerror = () => setIsSpeaking(false);

            utteranceRef.current = utterance;
            window.speechSynthesis.speak(utterance);
        },
        [hasBrowserTts],
    );

    const speak = useCallback(
        (text: string, lang: string = 'en') => {
            stopAll();
            setIsSpeaking(true);

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
                body: JSON.stringify({ text, lang }),
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
                            speakWithBrowser(text, lang);
                        };
                        audio.play().catch(() => speakWithBrowser(text, lang));
                    } else {
                        throw new Error('No audio URL');
                    }
                })
                .catch(() => {
                    // Fallback to browser TTS
                    speakWithBrowser(text, lang);
                });
        },
        [stopAll, speakWithBrowser],
    );

    return { speak, stop: stopAll, isSpeaking, isSupported: true };
}
