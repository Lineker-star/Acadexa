// Minimal promise wrapper around IndexedDB for the offline mode.
// Stores:
//   courses  — downloaded course packages, key = enrollment_id
//   outbox   — actions made offline, replayed on reconnection, key = id
//   meta     — small key/value pairs (current user id…)

// Former spelling of the platform's name, kept on purpose: this database already exists on students'
// devices (downloaded courses, answers waiting to be sent) and another name would start from an empty one.
const DB_NAME = 'acadexa-offline';
const DB_VERSION = 1;

let dbPromise = null;

export function openDb() {
    if (!('indexedDB' in window)) {
        return Promise.reject(new Error('IndexedDB unavailable'));
    }
    if (!dbPromise) {
        dbPromise = new Promise((resolve, reject) => {
            const req = indexedDB.open(DB_NAME, DB_VERSION);
            req.onupgradeneeded = () => {
                const db = req.result;
                if (!db.objectStoreNames.contains('courses')) db.createObjectStore('courses', { keyPath: 'enrollment_id' });
                if (!db.objectStoreNames.contains('outbox')) db.createObjectStore('outbox', { keyPath: 'id' });
                if (!db.objectStoreNames.contains('meta')) db.createObjectStore('meta');
            };
            req.onsuccess = () => resolve(req.result);
            req.onerror = () => reject(req.error);
        });
    }
    return dbPromise;
}

function run(store, mode, fn) {
    return openDb().then(db => new Promise((resolve, reject) => {
        const tx = db.transaction(store, mode);
        const result = fn(tx.objectStore(store));
        tx.oncomplete = () => resolve(result && 'result' in result ? result.result : result);
        tx.onerror = () => reject(tx.error);
        tx.onabort = () => reject(tx.error);
    }));
}

export const db = {
    get: (store, key) => run(store, 'readonly', s => s.get(key)),
    all: (store) => run(store, 'readonly', s => s.getAll()),
    put: (store, value, key) => run(store, 'readwrite', s => (key === undefined ? s.put(value) : s.put(value, key))),
    delete: (store, key) => run(store, 'readwrite', s => s.delete(key)),
    clear: (store) => run(store, 'readwrite', s => s.clear()),
};

export async function deleteEverything() {
    try {
        const conn = await openDb();
        conn.close();
    } catch (e) { /* not opened */ }
    dbPromise = null;
    await new Promise(resolve => {
        const req = indexedDB.deleteDatabase(DB_NAME);
        req.onsuccess = req.onerror = req.onblocked = () => resolve();
    });
}
