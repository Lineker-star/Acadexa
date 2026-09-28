/* ACADEXA service worker — version {{ $version }}
 *
 * Caches:
 *   acadexa-shell-<version>  app shell (offline page, compiled CSS/JS, icons, CDN styles)
 *   acadexa-media-v1         course videos/resources and library books downloaded by the student
 *                            (never auto-filled; wiped on logout — the library itself lives in the account)
 *
 * Strategy:
 *   - media URLs            cache first, with HTTP Range support for <video> seeking
 *   - page navigations      network first; when offline, fall back to the /offline app
 *   - static assets         stale-while-revalidate
 */
const VERSION = @json($version);
const SHELL_CACHE = 'acadexa-shell-' + VERSION;
const MEDIA_CACHE = 'acadexa-media-v1';
const OFFLINE_URL = '/offline';

const SHELL_ASSETS = {!! json_encode($assets, JSON_UNESCAPED_SLASHES) !!};

const MEDIA_PATTERN = /^\/media\/(lessons\/\d+\/video|resources\/\d+|books\/\d+)$/;

self.addEventListener('install', event => {
    event.waitUntil((async () => {
        const cache = await caches.open(SHELL_CACHE);
        // Add one by one: a single failing CDN file must not break installation.
        await Promise.all(SHELL_ASSETS.map(async url => {
            try {
                const request = new Request(url, { credentials: url.startsWith('/') ? 'same-origin' : 'omit', mode: url.startsWith('/') ? 'same-origin' : 'cors' });
                const response = await fetch(request);
                if (response.ok) await cache.put(url, response);
            } catch (e) { /* retried on next visit via stale-while-revalidate */ }
        }));
        await self.skipWaiting();
    })());
});

self.addEventListener('activate', event => {
    event.waitUntil((async () => {
        const keys = await caches.keys();
        await Promise.all(keys.filter(k => k.startsWith('acadexa-shell-') && k !== SHELL_CACHE).map(k => caches.delete(k)));
        if (self.registration.navigationPreload) {
            try { await self.registration.navigationPreload.enable(); } catch (e) { /* optional */ }
        }
        await self.clients.claim();
    })());
});

self.addEventListener('fetch', event => {
    const request = event.request;
    if (request.method !== 'GET') return;

    const url = new URL(request.url);
    const sameOrigin = url.origin === self.location.origin;

    if (sameOrigin && MEDIA_PATTERN.test(url.pathname)) {
        event.respondWith(serveMedia(request, url));
        return;
    }

    if (request.mode === 'navigate') {
        event.respondWith(serveNavigation(event));
        return;
    }

    if (isStaticAsset(url, sameOrigin)) {
        event.respondWith(staleWhileRevalidate(request));
    }
});

function isStaticAsset(url, sameOrigin) {
    if (sameOrigin) {
        return url.pathname.startsWith('/build/') || url.pathname.startsWith('/pwa/')
            || url.pathname.startsWith('/images/') || url.pathname === '/manifest.webmanifest'
            || url.pathname === '/favicon.png' || url.pathname === '/icons.svg';
    }
    return url.hostname === 'cdn.jsdelivr.net' || url.hostname === 'fonts.googleapis.com' || url.hostname === 'fonts.gstatic.com';
}

async function serveNavigation(event) {
    try {
        const preloaded = await event.preloadResponse;
        if (preloaded) return preloaded;
        return await fetch(event.request);
    } catch (e) {
        const cache = await caches.open(SHELL_CACHE);
        if (new URL(event.request.url).pathname === OFFLINE_URL) {
            return (await cache.match(OFFLINE_URL)) || offlineFallback();
        }
        // Any page while offline opens the offline app, which knows the downloaded courses.
        return (await cache.match(OFFLINE_URL)) || offlineFallback();
    }
}

function offlineFallback() {
    return new Response(
        '<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">' +
        '<title>ACADEXA</title><body style="font-family:sans-serif;text-align:center;padding:3rem">' +
        '<h1>Hors ligne / Offline</h1><p>Reconnectez-vous à Internet puis réessayez.<br>Reconnect to the Internet and try again.</p>',
        { headers: { 'Content-Type': 'text/html; charset=utf-8' } }
    );
}

async function staleWhileRevalidate(request) {
    const cache = await caches.open(SHELL_CACHE);
    const cached = await cache.match(request);
    const network = fetch(request).then(response => {
        if (response.ok || response.type === 'opaque') cache.put(request, response.clone());
        return response;
    }).catch(() => null);
    return cached || (await network) || new Response('', { status: 504 });
}

/** Downloaded media are served from the cache, including partial (Range) requests. */
async function serveMedia(request, url) {
    const cache = await caches.open(MEDIA_CACHE);
    const cached = await cache.match(url.pathname) || await cache.match(url.href);

    if (!cached) {
        // Not downloaded: stream from the network (the server checks access and handles Range).
        return fetch(request);
    }

    const range = request.headers.get('Range');
    if (!range) return cached;

    const blob = await cached.blob();
    const size = blob.size;
    const match = /bytes=(\d*)-(\d*)/.exec(range);
    if (!match) return cached;

    let start = match[1] === '' ? null : parseInt(match[1], 10);
    let end = match[2] === '' ? null : parseInt(match[2], 10);
    if (start === null) { // suffix range: last N bytes
        start = Math.max(0, size - (end || 0));
        end = size - 1;
    } else {
        end = end === null ? size - 1 : Math.min(end, size - 1);
    }
    if (start >= size || start > end) {
        return new Response(null, { status: 416, headers: { 'Content-Range': `bytes */${size}` } });
    }

    return new Response(blob.slice(start, end + 1), {
        status: 206,
        statusText: 'Partial Content',
        headers: {
            'Content-Type': cached.headers.get('Content-Type') || 'video/mp4',
            'Content-Range': `bytes ${start}-${end}/${size}`,
            'Content-Length': String(end - start + 1),
            'Accept-Ranges': 'bytes',
        },
    });
}

// Background Sync: ask an open page to replay the offline queue.
self.addEventListener('sync', event => {
    if (event.tag === 'acadexa-sync') {
        event.waitUntil(self.clients.matchAll({ type: 'window' }).then(clients => {
            clients.forEach(client => client.postMessage({ type: 'sync-now' }));
        }));
    }
});

self.addEventListener('message', event => {
    if (event.data?.type === 'skip-waiting') self.skipWaiting();
});
