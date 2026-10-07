// The student's library lives in their account (server). On every device where they sign in,
// the app mirrors it: the list is kept in IndexedDB and each book is saved in the app's private
// Cache Storage, so it can be read offline in the in-app reader. Logging out wipes the copies.
import { db } from './db';
import { enqueue } from './outbox';
import { MEDIA_CACHE, isSupported } from './downloader';

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content;

export async function getLibrary() {
    try {
        return (await db.get('meta', 'library')) || null;
    } catch (e) {
        return null;
    }
}

export async function isCached(url) {
    if (!('caches' in window)) return false;
    const cache = await caches.open(MEDIA_CACHE);
    return Boolean(await cache.match(url));
}

let syncing = null;

/**
 * Downloads the account's library list and any book not yet stored on this device,
 * and drops the copies of books removed from the library (on another device, for instance).
 * @param {(state: {bookId:number, loaded:number, total:number}) => void} onProgress
 */
export function syncLibrary(onProgress = () => {}) {
    if (syncing) return syncing;
    syncing = doSync(onProgress).finally(() => { syncing = null; });
    return syncing;
}

async function doSync(onProgress) {
    if (!isSupported() || !navigator.onLine) return null;
    const res = await fetch('/my-library/data', { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
    if (!res.ok || !(res.headers.get('Content-Type') || '').includes('json')) return null;
    const data = await res.json();

    const previous = await getLibrary();
    await db.put('meta', data, 'library');
    await db.put('meta', data.user_id, 'user_id');

    const cache = await caches.open(MEDIA_CACHE);
    const wanted = new Set(data.items.map(i => i.url));
    for (const old of previous?.items || []) {
        if (!wanted.has(old.url)) await cache.delete(old.url);
    }

    if (navigator.storage?.persist) {
        try { await navigator.storage.persist(); } catch (e) { /* optional */ }
    }

    for (const item of data.items) {
        if (await cache.match(item.url)) continue;
        try {
            const response = await fetch(item.url, { credentials: 'same-origin' });
            if (!response.ok) continue;
            let loaded = 0;
            const counter = new TransformStream({
                transform(chunk, controller) {
                    loaded += chunk.byteLength;
                    onProgress({ bookId: item.book_id, loaded, total: item.size });
                    controller.enqueue(chunk);
                },
            });
            const headers = new Headers({ 'Content-Type': response.headers.get('Content-Type') || item.mime || 'application/octet-stream' });
            const length = response.headers.get('Content-Length');
            if (length) headers.set('Content-Length', length);
            await cache.put(item.url, new Response(response.body.pipeThrough(counter), { status: 200, headers }));
            window.dispatchEvent(new CustomEvent('acadexxa:library-item-ready', { detail: { bookId: item.book_id } }));
        } catch (e) {
            // Network lost or storage full: retried on the next sync.
        }
    }
    window.dispatchEvent(new CustomEvent('acadexxa:library-synced', { detail: data }));
    return data;
}

/** Reading position: saved in the account (or queued while offline) and in the local copy. */
export async function savePosition(bookId, position, progress) {
    const library = await getLibrary();
    const item = library?.items.find(i => i.book_id === bookId);
    if (item) {
        item.position = position;
        item.progress = Math.max(item.progress || 0, progress || 0);
        await db.put('meta', library, 'library');
    }
    const body = { position, progress };
    try {
        if (!navigator.onLine) throw new TypeError('offline');
        const res = await fetch(`/my-library/${bookId}/position`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf() },
            body: JSON.stringify(body),
        });
        if (!res.ok) throw new TypeError('rejected');
    } catch (e) {
        await enqueue({ type: 'library_position', book_id: bookId, user_id: library?.user_id, ...body });
    }
}

let lastSaved = 0;
window.addEventListener('acadexxa:reading', event => {
    const { bookId, position, progress } = event.detail;
    // The reader reports often; the account only needs an update every few seconds.
    if (Date.now() - lastSaved < 4000) return;
    lastSaved = Date.now();
    savePosition(bookId, position, progress);
});
