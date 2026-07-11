/**
 * Client-side TTS prefetch + memory cache.
 *
 * The server caches generated MP3s by hash(text+lang), so calling /api/tts/speak
 * ahead of time makes the later "real" call instant. This module goes one step
 * further: it remembers the returned audio URL in memory and warms the MP3 into
 * the browser cache, so pressing "Écouter" plays with ZERO network round-trip.
 *
 * Used by the exercise players (session + drill) to prefetch every listening
 * text of the session at mount, before the learner reaches the question.
 */

const urlCache = new Map<string, string>();
const inflight = new Map<string, Promise<string | null>>();
const warmedUrls = new Set<string>();

/** Normalise any language identifier (slug "english", code "fr", locale "de-DE") to en/fr/de. */
export function normalizeTtsLang(lang: string | undefined | null): string {
    const aliases: Record<string, string> = {
        english: 'en', french: 'fr', german: 'de',
        en: 'en', fr: 'fr', de: 'de',
    };
    const raw = (lang ?? '').toLowerCase();
    return aliases[raw] ?? aliases[raw.slice(0, 2)] ?? 'en';
}

function cacheKey(text: string, lang: string): string {
    return `${normalizeTtsLang(lang)}|${text.trim()}`;
}

function csrfToken(): string {
    const cookie = document.cookie.split('; ').find((c) => c.startsWith('XSRF-TOKEN='));
    if (cookie) return decodeURIComponent(cookie.split('=')[1]);
    return document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';
}

/** Warm an audio file into the browser cache so the first play() is instant. */
export function warmAudioUrl(url: string | null | undefined): void {
    if (!url || warmedUrls.has(url) || typeof Audio === 'undefined') return;
    warmedUrls.add(url);
    const a = new Audio();
    a.preload = 'auto';
    a.src = url;
}

/** Synchronous lookup — returns the cached MP3 URL for this text, or null. */
export function getCachedTtsUrl(text: string, lang: string): string | null {
    return urlCache.get(cacheKey(text, lang)) ?? null;
}

/** Record a URL obtained elsewhere (e.g. a live speak() call) so replays are instant. */
export function rememberTtsUrl(text: string, lang: string, url: string): void {
    urlCache.set(cacheKey(text, lang), url);
    warmAudioUrl(url);
}

async function fetchTtsUrl(text: string, lang: string): Promise<string | null> {
    try {
        const res = await fetch('/api/tts/speak', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-XSRF-TOKEN': csrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ text, lang: normalizeTtsLang(lang) }),
        });
        if (!res.ok) return null;
        const data = await res.json();
        return typeof data.audio_url === 'string' ? data.audio_url : null;
    } catch {
        return null;
    }
}

export interface TtsPrefetchItem {
    text?: string | null;
    lang: string;
}

/**
 * Prefetch a batch of texts (deduplicated, max `max` items, 3 concurrent calls —
 * the endpoint is rate-limited to 20/min per user, so we stay well under it).
 * Silent on failure: the player falls back to the live call as before.
 */
export async function prefetchTts(items: TtsPrefetchItem[], max = 12): Promise<void> {
    if (typeof window === 'undefined') return;

    const todo: { text: string; lang: string; key: string }[] = [];
    const seen = new Set<string>();
    for (const item of items) {
        const text = (item.text ?? '').trim();
        if (!text) continue;
        const key = cacheKey(text, item.lang);
        if (seen.has(key) || urlCache.has(key) || inflight.has(key)) continue;
        seen.add(key);
        todo.push({ text, lang: item.lang, key });
        if (todo.length >= max) break;
    }
    if (todo.length === 0) return;

    const queue = [...todo];
    const worker = async () => {
        for (let job = queue.shift(); job; job = queue.shift()) {
            const { text, lang, key } = job;
            const promise = fetchTtsUrl(text, lang).then((url) => {
                if (url) {
                    urlCache.set(key, url);
                    warmAudioUrl(url);
                }
                inflight.delete(key);
                return url;
            });
            inflight.set(key, promise);
            await promise.catch(() => {});
        }
    };
    await Promise.all(Array.from({ length: Math.min(3, queue.length) }, worker));
}

/**
 * Collect every prefetchable audio source from a list of exercises (session player
 * shape or legacy drill shape) and prefetch/warm them. Shared by both players.
 */
export function prefetchExercisesAudio(
    exercises: Array<{
        exercise_type?: { skill_type?: string | null } | null;
        content?: Record<string, unknown> | null;
        questions?: Array<Record<string, unknown>> | null;
    }>,
    lang: string,
): void {
    const items: TtsPrefetchItem[] = [];

    for (const ex of exercises) {
        const content = (ex?.content ?? {}) as Record<string, unknown>;
        const isListening = (ex?.exercise_type?.skill_type ?? '') === 'listening';

        if (typeof content.audio_url === 'string' && content.audio_url) {
            warmAudioUrl(content.audio_url);
        } else if (isListening) {
            const contentText = (content.audio_text ?? content.passage) as string | undefined;
            if (typeof contentText === 'string') items.push({ text: contentText, lang });
        }

        for (const q of ex?.questions ?? []) {
            if (typeof q.audio_url === 'string' && q.audio_url) {
                warmAudioUrl(q.audio_url);
                continue;
            }
            if (typeof q.audio_text === 'string') {
                items.push({ text: q.audio_text, lang: (q.audio_lang as string) || lang });
            }
            // Role-play: pre-generate the examiner's spoken lines.
            if (Array.isArray(q.dialogue_turns)) {
                for (const turn of q.dialogue_turns as Array<{ text?: string }>) {
                    if (turn?.text) items.push({ text: turn.text, lang });
                }
            }
        }
    }

    if (items.length) void prefetchTts(items);
}
