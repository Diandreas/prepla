// Bump this version on every deploy that ships new front-end assets so the
// activate handler purges the previous cache. Cache-first on hashed Vite assets
// is fine, but the SW itself must not pin users to a stale bundle.
const CACHE_NAME = 'prepla-v3';
const OFFLINE_URL = '/offline';

const PRECACHE_ASSETS = [
    '/',
    '/offline',
    '/favicon.ico',
    '/manifest.json',
];

// Install: precache static assets
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => cache.addAll(PRECACHE_ASSETS))
    );
    self.skipWaiting();
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

// Fetch: network-first for navigation/API, cache-first for static assets
self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    // Skip non-GET and chrome-extension requests
    if (request.method !== 'GET' || url.protocol === 'chrome-extension:') return;

    // Skip API / Inertia XHR requests — always network
    if (request.headers.get('X-Inertia')) return;

    // Static assets (js, css, images, fonts) → stale-while-revalidate.
    // Serve the cached copy instantly for speed, but ALWAYS refetch in the
    // background and update the cache, so a new deploy is picked up on the next
    // load instead of pinning the user to an old bundle (which made icon/emoji
    // changes appear to "not change" after deploy).
    if (url.pathname.match(/\.(js|css|png|jpg|jpeg|gif|svg|ico|woff2?|ttf)$/)) {
        event.respondWith(
            caches.match(request).then((cached) => {
                const network = fetch(request).then((response) => {
                    if (response && response.ok) {
                        const clone = response.clone();
                        caches.open(CACHE_NAME).then((cache) => cache.put(request, clone));
                    }
                    return response;
                }).catch(() => cached);
                return cached || network;
            })
        );
        return;
    }

    // Navigation requests → network-first with a 3s timeout. The HTML document
    // embeds the current Vite asset hashes, so we MUST prefer the network to pick
    // up a new deploy. The timeout means a slow network falls back to cache (or the
    // offline page) instead of hanging, without pinning users to a stale document.
    if (request.mode === 'navigate') {
        event.respondWith(
            Promise.race([
                fetch(request).then((response) => {
                    const clone = response.clone();
                    caches.open(CACHE_NAME).then((cache) => cache.put(request, clone));
                    return response;
                }),
                new Promise((_, reject) => setTimeout(() => reject(new Error('nav-timeout')), 3000)),
            ]).catch(() =>
                caches.match(request).then((cached) => cached || caches.match(OFFLINE_URL))
            )
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
        const urls = event.data.urls || [];
        caches.open(CACHE_NAME).then((cache) => {
            urls.forEach((url) => {
                fetch(url, { headers: { 'X-Preload': '1' } })
                    .then((r) => { if (r.ok) cache.put(url, r); })
                    .catch(() => {});
            });
        });
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
        icon: payload.icon || '/icons/pwa-192.png',
        badge: payload.badge || '/icons/pwa-192.png',
        data: { url: payload.data?.url || '/' },
        actions: payload.actions || [],
        requireInteraction: false,
    };

    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const url = event.notification.data?.url || '/';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windowClients) => {
            for (const client of windowClients) {
                if (client.url === url && 'focus' in client) {
                    return client.focus();
                }
            }
            if (clients.openWindow) {
                return clients.openWindow(url);
            }
        })
    );
});
