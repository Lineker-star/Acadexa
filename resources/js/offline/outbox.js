// Actions made offline (lesson completed, watch time, quiz answers) are queued here
// and replayed against /offline/sync once the network and the session are back.
import { db } from './db';

let flushing = null;

function uid() {
    return (crypto.randomUUID ? crypto.randomUUID() : Date.now().toString(36) + Math.random().toString(36).slice(2));
}

export async function enqueue(event) {
    const entry = { id: uid(), at: new Date().toISOString(), ...event };
    await db.put('outbox', entry);
    requestBackgroundSync();
    return entry;
}

export async function pendingCount() {
    try {
        return (await db.all('outbox')).length;
    } catch (e) {
        return 0;
    }
}

function requestBackgroundSync() {
    if ('serviceWorker' in navigator && 'SyncManager' in window) {
        navigator.serviceWorker.ready.then(reg => reg.sync.register('acadexxa-sync')).catch(() => {});
    }
}

/**
 * Sends queued events. Resolves with { sent, failed, results } or null when nothing
 * could be sent (offline / logged out). Safe to call often: concurrent calls share one run.
 */
export function flush() {
    if (flushing) return flushing;
    flushing = doFlush().finally(() => { flushing = null; });
    return flushing;
}

async function doFlush() {
    if (!navigator.onLine) return null;

    let events;
    try {
        events = (await db.all('outbox')).sort((a, b) => a.at.localeCompare(b.at));
    } catch (e) {
        return null;
    }
    if (!events.length) return { sent: 0, failed: 0, results: [] };

    // Fresh CSRF token + check the session still belongs to the user who queued the events.
    let session;
    try {
        const res = await fetch('/offline/session', { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
        if (!res.ok || res.redirected) return null;
        session = await res.json();
    } catch (e) {
        return null;
    }

    const mine = events.filter(e => !e.user_id || e.user_id === session.user_id);
    // Events of another account are never sent under this session; drop them.
    for (const e of events.filter(e => e.user_id && e.user_id !== session.user_id)) {
        await db.delete('outbox', e.id);
    }

    let sent = 0;
    let failed = 0;
    const allResults = [];

    for (let i = 0; i < mine.length; i += 50) {
        const batch = mine.slice(i, i + 50);
        let payload;
        try {
            const res = await fetch('/offline/sync', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': session.csrf },
                body: JSON.stringify({ events: batch }),
            });
            if (!res.ok) return { sent, failed, results: allResults };
            payload = await res.json();
        } catch (e) {
            return { sent, failed, results: allResults };
        }

        for (const result of payload.results || []) {
            // Success, or a permanent refusal (4xx): either way the event must not be retried.
            if (result.ok || (result.status >= 400 && result.status < 500)) {
                await db.delete('outbox', result.id);
                result.ok ? sent++ : failed++;
                allResults.push({ ...result, event: batch.find(e => e.id === result.id) });
            }
        }
    }

    window.dispatchEvent(new CustomEvent('acadexxa:synced', { detail: { sent, failed, results: allResults } }));
    return { sent, failed, results: allResults };
}
