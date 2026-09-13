// Bump this version on every deploy that changes the app shell. Vite assets are
// content-hashed, while this cache contains only public, non-personal assets.
const CACHE_NAME = 'prepla-shell-v16';
const OFFLINE_URL = '/offline';

const PRECACHE_ASSETS = [
    '/offline',
    '/favicon.ico',
    '/manifest.json?v=4',
    '/icons/pwa-192-v4.png',
    '/icons/pwa-512-v4.png',
];

// Install the new worker in the background. Do not call skipWaiting here: an
// immediate takeover could reload and interrupt an exercise or timed exam.
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => cache.addAll(PRECACHE_ASSETS))
    );
});

// Activate: clean old caches
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(keys.filter((k) => k !== CACHE_NAME).map((k) => caches.delete(k)))
        )
    );
    self.clients.claim();
});

// Fetch: public static assets may be cached, but authenticated HTML/Inertia
// responses must never be stored because they can contain personal user data.
self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    // Leave mutations, browser extensions and third-party resources alone.
    if (request.method !== 'GET' || url.protocol === 'chrome-extension:' || url.origin !== self.location.origin) return;

    // API/Inertia requests are always network-only.
    if (request.headers.get('X-Inertia')) return;

    // Static assets (js, css, images, fonts) → stale-while-revalidate.
    // Serve the cached copy instantly for speed, but ALWAYS refetch in the
    // background and update the cache, so a new deploy is picked up on the next
    // load instead of pinning the user to an old bundle (which made icon/emoji
    // changes appear to "not change" after deploy).
    if (url.pathname.match(/\.(js|css|png|jpg|jpeg|gif|webp|avif|svg|ico|woff2?|ttf|mp3)$/i)) {
        event.respondWith(
            caches.match(request).then((cached) => {
                const network = fetch(request).then((response) => {
                    if (response && response.ok && response.type === 'basic') {
                        const clone = response.clone();
                        caches.open(CACHE_NAME).then((cache) => cache.put(request, clone));
                    }
                    return response;
                }).catch(() => cached || Response.error());
                return cached || network;
            })
        );
        return;
    }

    // Navigations are network-first and are intentionally NOT cached. This
    // prevents a logged-out or shared device from displaying a stale dashboard
    // containing serialized Inertia props. Offline falls back to a public shell.
    if (request.mode === 'navigate') {
        event.respondWith(
            Promise.race([
                fetch(request),
                new Promise((_, reject) => setTimeout(() => reject(new Error('nav-timeout')), 8000)),
            ]).catch(() => caches.match(OFFLINE_URL))
        );
        return;
    }
});

// Listen for messages from the app
self.addEventListener('message', (event) => {
    // Activate a freshly-installed worker immediately (deploy picked up at once).
    if (event.data?.type === 'SKIP_WAITING') {
        self.skipWaiting();
        return;
    }
    if (event.data?.type === 'PRELOAD_URLS') {
        const urls = (event.data.urls || []).filter((path) => {
            try {
                return new URL(path, self.location.origin).origin === self.location.origin;
            } catch {
                return false;
            }
        });

        event.waitUntil(
            caches.open(CACHE_NAME).then((cache) =>
                Promise.allSettled(urls.map(async (path) => {
                    const response = await fetch(path, { headers: { 'X-Preload': '1' } });
                    if (response.ok && response.type === 'basic') await cache.put(path, response);
                }))
            )
        );
    }
});

// Push notifications
self.addEventListener('push', (event) => {
    if (!event.data) return;

    let payload;
    try {
        payload = event.data.json();
    } catch {
        payload = { title: 'PrePla', body: event.data.text() };
    }

    const title = payload.title || 'PrePla';
    const options = {
        body: payload.body || 'Temps de pratiquer !',
        icon: payload.icon || '/icons/pwa-192-v4.png',
        data: { url: payload.data?.url || '/' },
        actions: payload.actions || [],
        requireInteraction: false,
    };

    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    let targetUrl = new URL('/', self.location.origin).href;
    try {
        const candidate = new URL(event.notification.data?.url || '/', self.location.origin);
        if (candidate.origin === self.location.origin) targetUrl = candidate.href;
    } catch {
        // Keep the safe same-origin fallback.
    }

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windowClients) => {
            for (const client of windowClients) {
                if (client.url === targetUrl && 'focus' in client) {
                    return client.focus();
                }
            }
            if (clients.openWindow) {
                return clients.openWindow(targetUrl);
            }
        })
    );
});
