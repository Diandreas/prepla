import { useCallback } from 'react';

/**
 * Central sound + haptics for the app. Plays short SFX (from /public/sounds)
 * and fires a matching vibration on mobile so the app "feels" alive.
 *
 * Playback uses the Web Audio API with buffers decoded once at boot
 * (see initSounds, called from preload-assets): starting an
 * AudioBufferSourceNode is near-instant, unlike HTMLAudioElement.play()
 * which re-fetches/decodes and lags noticeably behind the user's action.
 * Falls back to a cached HTMLAudioElement when Web Audio is unavailable
 * or a buffer hasn't finished decoding yet.
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

const VOLUMES: Record<SoundName, number> = {
    correct: 0.6,
    incorrect: 0.6,
    complete: 0.6,
    xp: 0.6,
    click: 0.35,
    pop: 0.35,
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

// ─── Web Audio (primary path — zero-latency playback) ────────────────────────

let audioCtx: AudioContext | null = null;
const buffers: Partial<Record<SoundName, AudioBuffer>> = {};
let initStarted = false;

function getCtx(): AudioContext | null {
    if (typeof window === 'undefined') return null;
    const Ctor = window.AudioContext ?? (window as any).webkitAudioContext;
    if (!Ctor) return null;
    if (!audioCtx) {
        try {
            audioCtx = new Ctor();
        } catch {
            return null;
        }
    }
    return audioCtx;
}

/**
 * Fetch + decode every SFX into memory. Idempotent; cheap (~6 tiny mp3s).
 * Called at app boot from preload-assets, and defensively from playSound.
 */
export function initSounds(): void {
    if (initStarted || typeof window === 'undefined') return;
    initStarted = true;
    const ctx = getCtx();
    if (!ctx) return; // Web Audio unavailable → HTMLAudio fallback handles playback

    for (const [name, src] of Object.entries(FILES) as [SoundName, string][]) {
        fetch(src)
            .then((res) => (res.ok ? res.arrayBuffer() : Promise.reject(new Error('missing'))))
            .then((raw) => ctx.decodeAudioData(raw))
            .then((decoded) => {
                buffers[name] = decoded;
            })
            .catch(() => {
                /* sound file missing — silent, haptics still fire */
            });
    }
}

// ─── HTMLAudio fallback (Web Audio unavailable / buffer not decoded yet) ─────

const htmlCache: Partial<Record<SoundName, HTMLAudioElement>> = {};

function getFallbackAudio(name: SoundName): HTMLAudioElement | null {
    if (typeof window === 'undefined' || typeof Audio === 'undefined') return null;
    if (!htmlCache[name]) {
        const a = new Audio(FILES[name]);
        a.preload = 'auto';
        a.volume = VOLUMES[name];
        htmlCache[name] = a;
    }
    return htmlCache[name] ?? null;
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

    initSounds();

    // Primary: Web Audio buffer — instant start, no fetch/decode at play time.
    const ctx = getCtx();
    const buffer = ctx ? buffers[name] : undefined;
    if (ctx && buffer) {
        try {
            // Autoplay policy suspends the context until a user gesture;
            // playSound is called from gesture handlers, so resume here works.
            if (ctx.state === 'suspended') void ctx.resume();
            const source = ctx.createBufferSource();
            source.buffer = buffer;
            const gain = ctx.createGain();
            gain.gain.value = VOLUMES[name];
            source.connect(gain);
            gain.connect(ctx.destination);
            source.start(0);
            return;
        } catch {
            /* fall through to HTMLAudio */
        }
    }

    const audio = getFallbackAudio(name);
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
