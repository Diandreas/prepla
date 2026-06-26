import { useCallback } from 'react';

/**
 * Central sound + haptics for the app. Plays short SFX (from /public/sounds)
 * and fires a matching vibration on mobile so the app "feels" alive.
 *
 * Audio files are optional — if a sound file is missing the call fails silently,
 * so the haptic still fires and nothing breaks. Drop the .mp3s into
 * public/sounds and they light up automatically. (SFX are generated via the
 * ElevenLabs Sound Effects API — see scripts/generate-sfx.)
 */

export type SoundName =
    | 'correct'
    | 'incorrect'
    | 'complete'   // lesson/session finished
    | 'xp'         // xp gained / streak extended
    | 'click'      // navigation / button taps
    | 'pop';       // light UI accent

const FILES: Record<SoundName, string> = {
    correct: '/sounds/correct.mp3',
    incorrect: '/sounds/incorrect.mp3',
    complete: '/sounds/complete.mp3',
    xp: '/sounds/xp.mp3',
    click: '/sounds/click.mp3',
    pop: '/sounds/pop.mp3',
};

// Vibration pattern (ms) per sound. Kept short and subtle.
const HAPTICS: Record<SoundName, number | number[]> = {
    correct: 18,
    incorrect: [30, 40, 30],
    complete: [20, 50, 20, 50, 40],
    xp: 14,
    click: 8,
    pop: 10,
};

const STORAGE_KEY = 'prepla-sound-muted';

function isMuted(): boolean {
    try {
        return localStorage.getItem(STORAGE_KEY) === '1';
    } catch {
        return false;
    }
}

// One reusable Audio element per sound, lazily created and cached.
const cache: Partial<Record<SoundName, HTMLAudioElement>> = {};

function getAudio(name: SoundName): HTMLAudioElement | null {
    if (typeof window === 'undefined' || typeof Audio === 'undefined') return null;
    if (!cache[name]) {
        const a = new Audio(FILES[name]);
        a.preload = 'auto';
        a.volume = name === 'click' || name === 'pop' ? 0.35 : 0.6;
        cache[name] = a;
    }
    return cache[name] ?? null;
}

export function playSound(name: SoundName) {
    // Haptics first — always fires on supporting devices even without audio.
    try {
        if (typeof navigator !== 'undefined' && 'vibrate' in navigator) {
            navigator.vibrate(HAPTICS[name]);
        }
    } catch {
        /* no-op */
    }

    if (isMuted()) return;

    const audio = getAudio(name);
    if (!audio) return;
    try {
        audio.currentTime = 0;
        // play() rejects if the file is missing or autoplay is blocked — ignore.
        void audio.play().catch(() => {});
    } catch {
        /* no-op */
    }
}

export function setSoundMuted(muted: boolean) {
    try {
        localStorage.setItem(STORAGE_KEY, muted ? '1' : '0');
    } catch {
        /* no-op */
    }
}

export function useSound() {
    const play = useCallback((name: SoundName) => playSound(name), []);
    return { play };
}
