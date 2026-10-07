// Course content protection — online player, in-app reader and offline app.
//
// A web page cannot technically forbid screenshots or screen recording (the operating system takes
// them). What this does:
//  1. the content is blurred as soon as the window loses focus (capture tools, app switching,
//     screen-sharing pickers) and when the Print Screen key is pressed (the clipboard is emptied);
//  2. right-click, copy, text selection, drag and printing are disabled on protected content.
// No watermark is shown. Protected zones carry the attribute [data-protected].

// Instructors and admins preview their own courses: no masking for them.
const STAFF = document.body?.dataset.staff === '1';

const i18n = window.ACADEXXA_I18N || {};
const t = key => i18n[key] || key;

/** Removes the name an earlier version kept on the device for its watermark. */
export function forgetWatermark() {
    try { localStorage.removeItem('acadexa.watermark'); } catch (e) { /* ignore */ }
}
forgetWatermark();

/** Adds the "content hidden" layer shown while the window is not active (idempotent). */
function decorate(zone) {
    if (STAFF || zone.dataset.protectedReady) return;
    zone.dataset.protectedReady = '1';
    if (getComputedStyle(zone).position === 'static') zone.style.position = 'relative';

    const shield = document.createElement('div');
    shield.className = 'wm-shield';
    shield.textContent = t('content_hidden');
    zone.appendChild(shield);
}

function scan(root = document) {
    root.querySelectorAll('[data-protected]').forEach(decorate);
}

// ─── Hide the content when the page is not the one being looked at ───────────
let shieldTimer = null;
function setShield(on) {
    document.documentElement.classList.toggle('content-shielded', on && !STAFF);
}
window.addEventListener('blur', () => setShield(true));
window.addEventListener('focus', () => setShield(false));
document.addEventListener('visibilitychange', () => setShield(document.hidden));

// ─── Print Screen, copy, right-click, drag, print ─────────────────────────────
const inProtected = target => target instanceof Element && target.closest('[data-protected], .media-viewer');

document.addEventListener('keyup', e => {
    if (STAFF || e.key !== 'PrintScreen') return;
    setShield(true);
    navigator.clipboard?.writeText(' ').catch(() => {});
    window.showToast?.(t('capture_blocked'), 'warning');
    clearTimeout(shieldTimer);
    shieldTimer = setTimeout(() => setShield(!document.hasFocus()), 1500);
});
document.addEventListener('keydown', e => {
    // Ctrl/Cmd + P (print) and Ctrl/Cmd + S (save page) on course pages.
    if (!STAFF && (e.ctrlKey || e.metaKey) && ['p', 's'].includes(e.key.toLowerCase()) && document.querySelector('[data-protected], .media-viewer')) {
        e.preventDefault();
        window.showToast?.(t('capture_blocked'), 'warning');
    }
});
['contextmenu', 'copy', 'cut', 'dragstart'].forEach(type => {
    document.addEventListener(type, e => { if (!STAFF && inProtected(e.target)) e.preventDefault(); });
});

// ─── Fullscreen button of the course player: the zone goes fullscreen ────────
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
