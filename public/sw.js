/* ZuraEdu SGE — Service Worker v1 */
const CACHE_NAME   = 'zuraedu-v1';
const OFFLINE_URL  = '/offline';

const STATIC_EXTS = /\.(css|js|woff2?|ttf|otf|eot|svg|png|jpg|jpeg|ico|webp|gif)(\?.*)?$/i;

// Assets to pre-cache on install
const PRECACHE = [
    OFFLINE_URL,
];

self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => cache.addAll(PRECACHE))
            .then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(keys =>
            Promise.all(
                keys
                    .filter(k => k !== CACHE_NAME)
                    .map(k => caches.delete(k))
            )
        ).then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', event => {
    const req = event.request;

    // Solo GET
    if (req.method !== 'GET') return;

    const url = new URL(req.url);

    // No interceptar API, WebSockets ni rutas de admin en tiempo real
    if (url.pathname.startsWith('/api/')
        || url.pathname.startsWith('/broadcasting/')
        || url.pathname.startsWith('/webhook/')
    ) return;

    // Archivos estáticos → cache-first
    if (STATIC_EXTS.test(url.pathname)) {
        event.respondWith(cacheFirst(req));
        return;
    }

    // Páginas HTML → network-first con fallback offline
    if (req.headers.get('Accept')?.includes('text/html')) {
        event.respondWith(networkFirstWithOffline(req));
        return;
    }
});

// ── Estrategias ──────────────────────────────────────────────────────────────

async function cacheFirst(req) {
    const cached = await caches.match(req);
    if (cached) return cached;

    try {
        const response = await fetch(req);
        if (response.ok) {
            const cache = await caches.open(CACHE_NAME);
            cache.put(req, response.clone());
        }
        return response;
    } catch {
        return new Response('', { status: 503 });
    }
}

async function networkFirstWithOffline(req) {
    try {
        const response = await fetch(req);
        if (response.ok) {
            const cache = await caches.open(CACHE_NAME);
            cache.put(req, response.clone());
        }
        return response;
    } catch {
        const cached = await caches.match(req);
        if (cached) return cached;

        const offline = await caches.match(OFFLINE_URL);
        return offline || new Response('<h1>Sin conexión</h1>', {
            status: 503,
            headers: { 'Content-Type': 'text/html' },
        });
    }
}
