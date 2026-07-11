/**
 * Warm the cache with the app's static assets (sounds, animations, common icons)
 * on first launch, so they're ready instantly when needed — no fetch latency mid-
 * exercise (sounds arriving late, GIFs popping in after the page, etc.).
 *
 * Two layers:
 *  - tell the service worker to cache them (survives reloads / offline)
 *  - warm the browser memory cache directly (Image()/Audio()) for this session
 */

import { initSounds } from '@/hooks/use-sound';

const SOUNDS = [
    '/sounds/correct.mp3',
    '/sounds/incorrect.mp3',
    '/sounds/complete.mp3',
    '/sounds/xp.mp3',
    '/sounds/click.mp3',
    '/sounds/pop.mp3',
];

const ANIMATIONS = [
    '/animation/loading.gif',
    '/animation/star.gif',
    '/animation/winner.gif',
    '/animation/big-trophy.gif',
    '/animation/Trophy.gif',
    '/animation/Fire.gif',
    '/animation/rocket-launch.gif',
];

const ICONS = [
    '/icons/sparkles.png', '/icons/book.png', '/icons/headphones.png',
    '/icons/writing.png', '/icons/speaking.png', '/icons/target.png',
    '/icons/trophy.png', '/icons/flame.png', '/icons/home.png',
    '/icons/puzzle.png', '/icons/statistics.png', '/icons/profile.png',
    '/icons/check-circle.png', '/icons/clock.png', '/icons/trending-up.png',
    '/icons/volume-1.png', '/icons/volume-2.png',
];

let done = false;

export function preloadAssets() {
    if (done || typeof window === 'undefined') return;
    done = true;

    const all = [...SOUNDS, ...ANIMATIONS, ...ICONS];

    // 1) Ask the service worker to cache everything (persistent).
    try {
        navigator.serviceWorker?.ready.then((reg) => {
            reg.active?.postMessage({ type: 'PRELOAD_URLS', urls: all });
        }).catch(() => {});
    } catch { /* no-op */ }

    // 2) Warm the browser cache now (this session), idle to not block first paint.
    const warm = () => {
        for (const src of [...ANIMATIONS, ...ICONS]) {
            const img = new Image();
            img.src = src;
        }
        // SFX: decode into Web Audio buffers once — playback is then instant
        // (no fetch/decode lag at click time). See use-sound.ts.
        initSounds();
    };
    if ('requestIdleCallback' in window) {
        (window as any).requestIdleCallback(warm, { timeout: 3000 });
    } else {
        setTimeout(warm, 1200);
    }
}
