// Downloads a course for offline use: the JSON package goes to IndexedDB,
// videos and resources go to the Cache Storage used by the service worker.
import { db, deleteEverything } from './db';

// Same name as in the service worker (resources/views/pwa/sw.blade.php), former spelling kept on purpose.
export const MEDIA_CACHE = 'acadexa-media-v1';

export function isSupported() {
    return 'serviceWorker' in navigator && 'caches' in window && 'indexedDB' in window;
}

export async function getDownloaded(enrollmentId) {
    try {
        return await db.get('courses', Number(enrollmentId));
    } catch (e) {
        return null;
    }
}

export async function listDownloaded() {
    try {
        return await db.all('courses');
    } catch (e) {
        return [];
    }
}

/** { usage, quota } in bytes when the browser reports it. */
export async function storageEstimate() {
    if (navigator.storage?.estimate) {
        try { return await navigator.storage.estimate(); } catch (e) { /* ignore */ }
    }
    return null;
}

async function fetchPackage(enrollmentId) {
    const res = await fetch(`/offline/courses/${enrollmentId}/package`, {
        headers: { Accept: 'application/json' },
        credentials: 'same-origin',
    });
    if (!res.ok) {
        let message = `HTTP ${res.status}`;
        try { message = (await res.json()).message || message; } catch (e) { /* not JSON */ }
        throw new Error(message);
    }
    return res.json();
}

/** Latest package version from the server, to tell whether an update is available. */
export async function remoteVersion(enrollmentId) {
    const pkg = await fetchPackage(enrollmentId);
    return pkg.version;
}

/**
 * @param {number} enrollmentId
 * @param {(p: {loaded:number,total:number,file:number,files:number}) => void} onProgress
 * @param {AbortSignal} [signal]
 */
export async function downloadCourse(enrollmentId, onProgress = () => {}, signal) {
    if (!isSupported()) throw new Error('unsupported');

    // Ask the browser not to evict our data under storage pressure.
    if (navigator.storage?.persist) {
        try { await navigator.storage.persist(); } catch (e) { /* optional */ }
    }

    const pkg = await fetchPackage(enrollmentId);

    const estimate = await storageEstimate();
    if (estimate && estimate.quota && estimate.quota - estimate.usage < pkg.total_bytes * 1.05) {
        const err = new Error('quota');
        err.code = 'quota';
        err.needed = pkg.total_bytes;
        err.available = estimate.quota - estimate.usage;
        throw err;
    }

    const cache = await caches.open(MEDIA_CACHE);
    const total = pkg.total_bytes || 0;
    let loaded = 0;
    let index = 0;

    for (const item of pkg.media) {
        index++;
        if (signal?.aborted) throw new DOMException('Aborted', 'AbortError');

        const cached = await cache.match(item.url);
        if (cached) {
            loaded += item.size;
            onProgress({ loaded, total, file: index, files: pkg.media.length });
            continue;
        }

        const res = await fetch(item.url, { credentials: 'same-origin', signal });
        if (!res.ok) throw new Error(`HTTP ${res.status} — ${item.url}`);

        // Stream straight into the cache (no full copy in memory, which matters for
        // large videos on modest phones) while counting bytes for the progress bar.
        let received = 0;
        const base = loaded;
        const counter = new TransformStream({
            transform(chunk, controller) {
                received += chunk.byteLength;
                onProgress({ loaded: base + received, total, file: index, files: pkg.media.length });
                controller.enqueue(chunk);
            },
        });

        const headers = new Headers({
            'Content-Type': res.headers.get('Content-Type') || 'application/octet-stream',
            'X-Acadexxa-Offline': '1',
        });
        const length = res.headers.get('Content-Length');
        if (length) headers.set('Content-Length', length);
        const disposition = res.headers.get('Content-Disposition');
        if (disposition) headers.set('Content-Disposition', disposition);

        await cache.put(item.url, new Response(res.body.pipeThrough(counter), { status: 200, headers }));
        loaded += received;
    }

    await db.put('courses', {
        ...pkg,
        enrollment_id: Number(pkg.enrollment_id),
        downloaded_at: new Date().toISOString(),
    });
    await db.put('meta', pkg.user_id, 'user_id');

    return pkg;
}

export async function removeCourse(enrollmentId) {
    const pkg = await getDownloaded(enrollmentId);
    if (!pkg) return;

    // Keep media still referenced by another downloaded course.
    const others = (await listDownloaded()).filter(c => c.enrollment_id !== pkg.enrollment_id);
    const stillUsed = new Set(others.flatMap(c => c.media.map(m => m.url)));

    const cache = await caches.open(MEDIA_CACHE);
    for (const item of pkg.media) {
        if (!stillUsed.has(item.url)) await cache.delete(item.url);
    }
    await db.delete('courses', pkg.enrollment_id);
}

/** Wipes every offline copy (used on logout and when another account signs in). */
export async function removeAll() {
    try { await caches.delete(MEDIA_CACHE); } catch (e) { /* ignore */ }
    await deleteEverything();
}

export function formatBytes(bytes) {
    const units = (window.ACADEXXA_I18N || {}).byte_units || ['B', 'KB', 'MB', 'GB'];
    let i = 0;
    let v = bytes || 0;
    while (v >= 1024 && i < units.length - 1) { v /= 1024; i++; }
    return `${v.toLocaleString(document.documentElement.lang, { maximumFractionDigits: i === 0 ? 0 : 1 })} ${units[i]}`;
}
