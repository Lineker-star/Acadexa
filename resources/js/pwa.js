// ACADEXXA — Progressive Web App glue, loaded on every page.
//  - registers the service worker (/sw.js)
//  - "Install the app" button
//  - online/offline banner + automatic replay of offline actions
//  - "Download for offline" buttons ([data-offline-download])
//  - wipes offline copies on logout or when another account signs in
//  - mirrors the account's library on this device (books readable offline)
import { icon } from './icons';
import { flush, pendingCount } from './offline/outbox';
import {
    downloadCourse, formatBytes, getDownloaded, isSupported, remoteVersion, removeAll, removeCourse,
} from './offline/downloader';
import { db } from './offline/db';
import { syncLibrary } from './offline/library';
import { forgetWatermark } from './content-protection';

const i18n = window.ACADEXXA_I18N || {};
const t = (key, vars = {}) => Object.entries(vars).reduce(
    (s, [k, v]) => s.replaceAll(`:${k}`, v), i18n[key] || key,
);
const currentUserId = Number(document.body?.dataset.userId || 0) || null;

// Installed app: iOS Safari does not support the display-mode media query, it exposes navigator.standalone.
if (window.navigator.standalone === true || window.matchMedia?.('(display-mode: standalone)').matches) {
    document.documentElement.classList.add('is-standalone');
}

// ─── Service worker ───────────────────────────────────────────────────────────
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js', { scope: '/' }).catch(err => console.warn('SW registration failed', err));
    });
    navigator.serviceWorker.addEventListener('message', event => {
        if (event.data?.type === 'sync-now') flush();
    });
}

// ─── Install prompt ───────────────────────────────────────────────────────────
let deferredPrompt = null;
window.addEventListener('beforeinstallprompt', event => {
    event.preventDefault();
    deferredPrompt = event;
    document.querySelectorAll('[data-pwa-install]').forEach(btn => { btn.hidden = false; });
});
window.addEventListener('appinstalled', () => {
    deferredPrompt = null;
    document.querySelectorAll('[data-pwa-install]').forEach(btn => { btn.hidden = true; });
});
document.addEventListener('click', async event => {
    const btn = event.target.closest('[data-pwa-install]');
    if (!btn || !deferredPrompt) return;
    deferredPrompt.prompt();
    await deferredPrompt.userChoice;
    deferredPrompt = null;
    btn.hidden = true;
});

// ─── Connectivity banner ──────────────────────────────────────────────────────
function connectivityBanner() {
    let banner = document.getElementById('netStatus');
    if (!banner) {
        banner = document.createElement('div');
        banner.id = 'netStatus';
        banner.className = 'net-status';
        banner.setAttribute('role', 'status');
        document.body.appendChild(banner);
    }
    return banner;
}

async function updateConnectivity() {
    const banner = connectivityBanner();
    if (navigator.onLine) {
        banner.classList.remove('is-offline');
        const pending = await pendingCount();
        if (pending) {
            banner.textContent = t('syncing', { count: pending });
            banner.classList.add('is-visible');
            const result = await flush();
            if (result && result.sent) {
                banner.textContent = t('synced', { count: result.sent });
                setTimeout(() => banner.classList.remove('is-visible'), 3500);
            } else {
                banner.classList.remove('is-visible');
            }
        } else {
            banner.classList.remove('is-visible');
        }
    } else {
        banner.innerHTML = `${icon('wifi-off', 'me-2')}${t('offline_banner')} <a href="/offline">${t('offline_open')}</a>`;
        banner.classList.add('is-visible', 'is-offline');
    }
}

window.addEventListener('online', updateConnectivity);
window.addEventListener('offline', updateConnectivity);
document.addEventListener('DOMContentLoaded', updateConnectivity);

// ─── Account safety ───────────────────────────────────────────────────────────
// Offline copies belong to one account: clear them when someone else signs in,
// and on logout (shared family / cyber-café computers are common).
document.addEventListener('DOMContentLoaded', async () => {
    if (!currentUserId || !isSupported()) return;
    try {
        const owner = await db.get('meta', 'user_id');
        if (owner && Number(owner) !== currentUserId) await removeAll();
    } catch (e) { /* storage unavailable */ }
});

document.addEventListener('submit', event => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || !/\/logout$/.test(new URL(form.action, location.href).pathname)) return;
    if (form.dataset.offlineCleared) return;
    event.preventDefault();
    const done = () => { form.dataset.offlineCleared = '1'; form.submit(); };
    Promise.race([
        flush().catch(() => {}).then(() => { forgetWatermark(); return removeAll(); }),
        new Promise(resolve => setTimeout(resolve, 2500)),
    ]).finally(done);
}, true);

// ─── Download for offline ─────────────────────────────────────────────────────
async function renderDownloadButton(container) {
    const enrollmentId = container.dataset.offlineDownload;
    if (!isSupported()) {
        container.innerHTML = `<span class="text-muted small">${icon('exclamation-circle', 'me-1')}${t('offline_unsupported')}</span>`;
        return;
    }

    const existing = await getDownloaded(enrollmentId);
    if (existing) {
        container.innerHTML = `
            <div class="offline-state d-flex flex-wrap align-items-center gap-2">
                <span class="badge bg-success-subtle text-success border border-success-subtle">
                    ${icon('check2-circle', 'me-1')}${t('offline_available')}
                </span>
                <span class="text-muted small">${formatBytes(existing.total_bytes)}</span>
                <a href="/offline#course-${existing.enrollment_id}" class="btn btn-sm btn-outline-primary">${icon('cloud-slash', 'me-1')}${t('offline_open')}</a>
                <button type="button" class="btn btn-sm btn-link text-danger p-0" data-action="remove">${t('offline_remove')}</button>
            </div>`;
        container.querySelector('[data-action=remove]').addEventListener('click', async () => {
            if (!container.dataset.confirming) {
                container.dataset.confirming = '1';
                container.querySelector('[data-action=remove]').textContent = t('offline_remove_confirm');
                return;
            }
            await removeCourse(enrollmentId);
            delete container.dataset.confirming;
            renderDownloadButton(container);
        });

        // Tell the student when the instructor changed the course since the download.
        if (navigator.onLine) {
            remoteVersion(enrollmentId).then(version => {
                if (version !== existing.version) {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'btn btn-sm btn-warning';
                    btn.innerHTML = `${icon('arrow-repeat', 'me-1')}${t('offline_update')}`;
                    btn.addEventListener('click', () => startDownload(container, enrollmentId));
                    container.querySelector('.offline-state').appendChild(btn);
                }
            }).catch(() => {});
        }
        return;
    }

    container.innerHTML = `
        <button type="button" class="btn btn-sm btn-outline-primary" data-action="download">
            ${icon('cloud-arrow-down', 'me-1')}${t('offline_download')}
        </button>`;
    container.querySelector('[data-action=download]').addEventListener('click', () => startDownload(container, enrollmentId));
}

async function startDownload(container, enrollmentId) {
    if (!navigator.onLine) {
        window.showToast?.(t('offline_need_network'), 'warning');
        return;
    }
    const controller = new AbortController();
    container.innerHTML = `
        <div class="offline-progress">
            <div class="d-flex justify-content-between small mb-1">
                <span data-role="label">${t('offline_preparing')}</span>
                <button type="button" class="btn btn-link btn-sm p-0 text-danger" data-action="cancel">${t('cancel')}</button>
            </div>
            <div class="progress" style="height:6px;"><div class="progress-bar" style="width:0%"></div></div>
        </div>`;
    const bar = container.querySelector('.progress-bar');
    const label = container.querySelector('[data-role=label]');
    container.querySelector('[data-action=cancel]').addEventListener('click', () => controller.abort());

    try {
        await downloadCourse(enrollmentId, ({ loaded, total, file, files }) => {
            const pct = total ? Math.min(100, Math.round((loaded / total) * 100)) : 100;
            bar.style.width = pct + '%';
            label.textContent = t('offline_progress', { pct, loaded: formatBytes(loaded), total: formatBytes(total), file, files });
        }, controller.signal);
        window.showToast?.(t('offline_done'), 'success');
    } catch (err) {
        if (err.name === 'AbortError') {
            window.showToast?.(t('offline_cancelled'), 'info');
        } else if (err.code === 'quota') {
            window.showToast?.(t('offline_quota', { needed: formatBytes(err.needed), available: formatBytes(err.available) }), 'error');
        } else {
            window.showToast?.(t('offline_failed') + ' ' + err.message, 'error');
        }
    }
    renderDownloadButton(container);
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-offline-download]').forEach(renderDownloadButton);
});

// ─── Library: the same books on every device the student signs in to ─────────
document.addEventListener('DOMContentLoaded', () => {
    if (!currentUserId || !isSupported() || !navigator.onLine || !document.body.dataset.library) return;
    if (navigator.connection?.saveData) return; // the student asked the browser to save data
    try {
        const last = Number(sessionStorage.getItem('acadexxa.librarySync') || 0);
        if (Date.now() - last < 10 * 60 * 1000) return;
        sessionStorage.setItem('acadexxa.librarySync', String(Date.now()));
    } catch (e) { /* storage blocked: sync anyway */ }
    syncLibrary().catch(() => {});
});
