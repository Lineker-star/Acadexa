// "My library" page: mirrors the account's library on this device right away and shows,
// for each book, whether it can already be read offline.
import { icon } from './icons';
import { isCached, syncLibrary } from './offline/library';
import { formatBytes, isSupported } from './offline/downloader';

const i18n = window.ACADEXA_I18N || {};
const t = (key, vars = {}) => Object.entries(vars).reduce((s, [k, v]) => s.replaceAll(`:${k}`, v), i18n[key] || key);

async function refreshStates() {
    for (const card of document.querySelectorAll('[data-library-book]')) {
        const state = card.querySelector('[data-role=offline-state]');
        if (!state) continue;
        state.innerHTML = (await isCached(card.dataset.url))
            ? `<span class="text-success">${icon('check2-circle', 'me-1')}${t('offline_available')}</span>`
            : `<span>${icon('cloud-arrow-down', 'me-1')}${t('book_downloading')}</span>`;
    }
}

document.addEventListener('DOMContentLoaded', async () => {
    const status = document.getElementById('librarySyncStatus');
    if (!isSupported()) {
        status.innerHTML = `${icon('exclamation-circle', 'me-1')}${t('offline_unsupported')}`;
        return;
    }
    await refreshStates();
    if (!navigator.onLine) return;
    window.addEventListener('acadexa:library-item-ready', refreshStates);
    try {
        await syncLibrary(({ bookId, loaded, total }) => {
            const card = document.querySelector(`[data-library-book="${bookId}"] [data-role=offline-state]`);
            if (card) card.textContent = `${t('book_downloading')} ${total ? Math.min(100, Math.round((loaded / total) * 100)) + ' %' : formatBytes(loaded)}`;
        });
    } catch (e) { /* retried on the next visit */ }
    await refreshStates();
});
