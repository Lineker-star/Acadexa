// Course content protection — online player, in-app reader and offline app.
//
// A web page cannot technically forbid screenshots or screen recording (the operating system takes
// them). What this does, like the big e-learning platforms:
//  1. a moving watermark (student name, e-mail, id, date) over videos, lessons and documents,
//     also in fullscreen, so that any leaked capture identifies its author;
//  2. the content is blurred as soon as the window loses focus (capture tools, app switching,
//     screen-sharing pickers) and when the Print Screen key is pressed (the clipboard is emptied);
//  3. right-click, copy, text selection, drag and printing are disabled on protected content.
// Protected zones carry the attribute [data-protected].

const i18n = window.ACADEXA_I18N || {};
const t = key => i18n[key] || key;
const STORE_KEY = 'acadexa.watermark';

function watermarkText() {
    const fromPage = document.body?.dataset.watermark;
    try {
        if (fromPage) localStorage.setItem(STORE_KEY, fromPage); // reused by the offline app
        return fromPage || localStorage.getItem(STORE_KEY) || '';
    } catch (e) {
        return fromPage || '';
    }
}

export function forgetWatermark() {
    try { localStorage.removeItem(STORE_KEY); } catch (e) { /* ignore */ }
}

const text = () => `${watermarkText()} · ${new Date().toLocaleDateString(document.documentElement.lang || undefined)}`;

/** Adds the moving watermark layer to a protected zone (idempotent). */
function decorate(zone) {
    if (zone.dataset.protectedReady) return;
    zone.dataset.protectedReady = '1';
    if (getComputedStyle(zone).position === 'static') zone.style.position = 'relative';

    const layer = document.createElement('div');
    layer.className = 'wm-layer';
    layer.setAttribute('aria-hidden', 'true');
    // A tiled faint mark everywhere + one clearer mark that moves.
    layer.innerHTML = `<div class="wm-tiles"></div><div class="wm-moving"></div>`;
    zone.appendChild(layer);

    const fill = () => {
        const label = text();
        layer.querySelector('.wm-tiles').innerHTML = Array.from({ length: 24 }, () => `<span>${escapeHtml(label)}</span>`).join('');
        const moving = layer.querySelector('.wm-moving');
        moving.textContent = label;
        moving.style.top = `${8 + Math.random() * 78}%`;
        moving.style.left = `${4 + Math.random() * 60}%`;
    };
    fill();
    setInterval(fill, 12000);

    const shield = document.createElement('div');
    shield.className = 'wm-shield';
    shield.textContent = t('content_hidden');
    zone.appendChild(shield);
}

function escapeHtml(str) {
    return String(str).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}

function scan(root = document) {
    root.querySelectorAll('[data-protected]').forEach(decorate);
}

// ─── Hide the content when the page is not the one being looked at ───────────
let shieldTimer = null;
function setShield(on) {
    document.documentElement.classList.toggle('content-shielded', on);
}
window.addEventListener('blur', () => setShield(true));
window.addEventListener('focus', () => setShield(false));
document.addEventListener('visibilitychange', () => setShield(document.hidden));

// ─── Print Screen, copy, right-click, drag, print ─────────────────────────────
const inProtected = target => target instanceof Element && target.closest('[data-protected], .media-viewer');

document.addEventListener('keyup', e => {
    if (e.key !== 'PrintScreen') return;
    setShield(true);
    navigator.clipboard?.writeText(' ').catch(() => {});
    window.showToast?.(t('capture_blocked'), 'warning');
    clearTimeout(shieldTimer);
    shieldTimer = setTimeout(() => setShield(!document.hasFocus()), 1500);
});
document.addEventListener('keydown', e => {
    // Ctrl/Cmd + P (print) and Ctrl/Cmd + S (save page) on course pages.
    if ((e.ctrlKey || e.metaKey) && ['p', 's'].includes(e.key.toLowerCase()) && document.querySelector('[data-protected], .media-viewer')) {
        e.preventDefault();
        window.showToast?.(t('capture_blocked'), 'warning');
    }
});
['contextmenu', 'copy', 'cut', 'dragstart'].forEach(type => {
    document.addEventListener(type, e => { if (inProtected(e.target)) e.preventDefault(); });
});

// ─── Fullscreen keeps the watermark: the zone goes fullscreen, not the bare <video> ─
document.addEventListener('click', e => {
    const btn = e.target.closest('[data-protected-fullscreen]');
    if (!btn) return;
    const zone = btn.closest('[data-protected]');
    if (document.fullscreenElement) document.exitFullscreen?.();
    else zone?.requestFullscreen?.().catch(() => {});
});

document.addEventListener('DOMContentLoaded', () => scan());
// Zones added later (offline app screens, in-app reader).
new MutationObserver(records => {
    for (const r of records) r.addedNodes.forEach(n => { if (n instanceof Element) { if (n.matches('[data-protected]')) decorate(n); scan(n); } });
}).observe(document.documentElement, { childList: true, subtree: true });
