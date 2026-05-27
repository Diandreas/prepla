const CACHE_NAME = 'prepla-v1';
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

    // Static assets (js, css, images, fonts) → cache-first
    if (url.pathname.match(/\.(js|css|png|jpg|jpeg|gif|svg|ico|woff2?|ttf)$/)) {
        event.respondWith(
            caches.match(request).then((cached) =>
                cached || fetch(request).then((response) => {
                    const clone = response.clone();
                    caches.open(CACHE_NAME).then((cache) => cache.put(request, clone));
                    return response;
                })
            )
        );
        return;
    }

    // Navigation requests → network-first, fallback to cache then offline page
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request)
                .then((response) => {
                    const clone = response.clone();
                    caches.open(CACHE_NAME).then((cache) => cache.put(request, clone));
                    return response;
                })
                .catch(() =>
                    caches.match(request).then((cached) => cached || caches.match(OFFLINE_URL))
                )
        );
        return;
    }
});

// Listen for preload messages from the app
self.addEventListener('message', (event) => {
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
