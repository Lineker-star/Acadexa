// In-app reader for PDF books, audio, video and images — online and offline.
// Files are read through the service worker, so a book or resource saved for offline
// use opens without network. Nothing is written to the device's download folder.
//   <button data-open-media="/media/books/3" data-media-kind="pdf" data-media-title="…" data-book-id="3" data-position="12">
import { icon } from './icons';

const i18n = window.ACADEXA_I18N || {};
const t = (key, vars = {}) => Object.entries(vars).reduce((s, [k, v]) => s.replaceAll(`:${k}`, v), i18n[key] || key);
const esc = str => String(str ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

let pdfjsPromise = null;
function loadPdfJs() {
    // Loaded on demand: the library is large and only needed when a PDF is opened.
    pdfjsPromise ??= Promise.all([
        import('pdfjs-dist'),
        import('pdfjs-dist/build/pdf.worker.min.mjs?url'),
    ]).then(([pdfjs, worker]) => {
        pdfjs.GlobalWorkerOptions.workerSrc = worker.default;
        return pdfjs;
    });
    return pdfjsPromise;
}

/**
 * @param {{url:string, kind:string, title?:string, startAt?:number, onPosition?:(position:number, progress:number)=>void}} options
 */
export function openMedia({ url, kind, title = '', startAt = 0, onPosition = () => {} }) {
    const viewer = document.createElement('div');
    viewer.className = 'media-viewer';
    viewer.setAttribute('data-protected', ''); // watermark + blur (content-protection.js)
    viewer.setAttribute('role', 'dialog');
    viewer.setAttribute('aria-modal', 'true');
    viewer.setAttribute('aria-label', title);
    viewer.innerHTML = `
        <div class="media-viewer-bar">
            <button type="button" class="btn btn-link p-0" data-action="close" aria-label="${esc(t('close'))}">${icon('x-lg', 'fs-5')}</button>
            <div class="title">${esc(title)}</div>
            <div class="d-flex align-items-center gap-2" data-role="tools"></div>
        </div>
        <div class="media-viewer-body" data-role="body">
            <div class="text-center py-5 w-100"><span class="spinner-border spinner-border-sm me-2"></span>${esc(t('loading'))}</div>
        </div>`;
    document.body.appendChild(viewer);
    document.body.style.overflow = 'hidden';

    const body = viewer.querySelector('[data-role=body]');
    const tools = viewer.querySelector('[data-role=tools]');
    let cleanup = () => {};

    const close = () => {
        cleanup();
        viewer.remove();
        document.body.style.overflow = '';
        document.removeEventListener('keydown', onKey);
    };
    const onKey = e => { if (e.key === 'Escape') close(); };
    document.addEventListener('keydown', onKey);
    viewer.querySelector('[data-action=close]').addEventListener('click', close);

    const fail = () => {
        body.querySelectorAll(':scope > :not(.wm-layer):not(.wm-shield)').forEach(n => n.remove());
        body.insertAdjacentHTML('afterbegin', `<div class="text-center py-5 px-3">${icon('wifi-off', 'fs-1 d-block mb-2')}${esc(navigator.onLine ? t('media_error') : t('media_not_offline'))}</div>`);
    };

    if (kind === 'pdf') {
        cleanup = renderPdf(url, body, tools, startAt, onPosition, fail);
    } else if (kind === 'audio' || kind === 'video') {
        body.classList.add('center');
        const el = document.createElement(kind);
        el.controls = true;
        el.preload = 'metadata';
        el.src = url;
        el.setAttribute('playsinline', '');
        el.setAttribute('controlsList', 'nodownload nofullscreen noremoteplayback');
        el.setAttribute('disablepictureinpicture', '');
        el.addEventListener('contextmenu', e => e.preventDefault());
        el.addEventListener('error', fail);
        el.addEventListener('loadedmetadata', () => { if (startAt && startAt < el.duration - 5) el.currentTime = startAt; }, { once: true });
        let last = 0;
        const report = () => {
            if (!el.duration) return;
            onPosition(Math.floor(el.currentTime), Math.round((el.currentTime / el.duration) * 100));
        };
        el.addEventListener('timeupdate', () => { if (Date.now() - last > 15000) { last = Date.now(); report(); } });
        el.addEventListener('pause', report);
        body.querySelector('.text-center')?.remove();
        body.prepend(el);
        cleanup = () => { report(); el.pause(); el.removeAttribute('src'); el.load(); };
    } else {
        body.classList.add('center');
        body.querySelector('.text-center')?.remove();
        body.insertAdjacentHTML('afterbegin', `<img src="${esc(url)}" alt="${esc(title)}" draggable="false">`);
        body.querySelector('img').addEventListener('error', fail);
    }

    return { close };
}

function renderPdf(url, body, tools, startAt, onPosition, fail) {
    let doc = null;
    let page = Math.max(1, Number(startAt) || 1);
    let zoom = 1;
    let rendering = null;
    let cancelled = false;

    tools.innerHTML = `
        <button type="button" class="btn btn-link p-0" data-action="prev" aria-label="${esc(t('previous'))}">${icon('chevron-left')}</button>
        <span class="page-info small" data-role="page"></span>
        <button type="button" class="btn btn-link p-0" data-action="next" aria-label="${esc(t('next'))}">${icon('chevron-right')}</button>
        <button type="button" class="btn btn-link p-0 ms-2" data-action="zoom-out" aria-label="${esc(t('zoom_out'))}">${icon('zoom-out')}</button>
        <button type="button" class="btn btn-link p-0" data-action="zoom-in" aria-label="${esc(t('zoom_in'))}">${icon('zoom-in')}</button>`;
    const pageInfo = tools.querySelector('[data-role=page]');
    const canvas = document.createElement('canvas');

    const draw = async () => {
        if (!doc || cancelled) return;
        page = Math.min(Math.max(1, page), doc.numPages);
        pageInfo.textContent = t('page_x_of_y', { x: page, y: doc.numPages });
        const pdfPage = await doc.getPage(page);
        const available = Math.min(body.clientWidth - 16, 1100);
        const base = pdfPage.getViewport({ scale: 1 });
        const scale = (available / base.width) * zoom;
        const ratio = window.devicePixelRatio || 1;
        const viewport = pdfPage.getViewport({ scale: scale * ratio });
        canvas.width = viewport.width;
        canvas.height = viewport.height;
        canvas.style.width = `${viewport.width / ratio}px`;
        canvas.style.height = `${viewport.height / ratio}px`;
        rendering?.cancel();
        rendering = pdfPage.render({ canvasContext: canvas.getContext('2d'), viewport });
        try { await rendering.promise; } catch (e) { /* cancelled by a newer render */ }
        body.scrollTop = 0;
        onPosition(page, Math.round((page / doc.numPages) * 100));
    };

    tools.addEventListener('click', e => {
        const action = e.target.closest('[data-action]')?.dataset.action;
        if (action === 'prev') { page--; draw(); }
        if (action === 'next') { page++; draw(); }
        if (action === 'zoom-in') { zoom = Math.min(3, zoom + 0.25); draw(); }
        if (action === 'zoom-out') { zoom = Math.max(0.5, zoom - 0.25); draw(); }
    });
    const onKey = e => {
        if (e.key === 'ArrowRight' || e.key === 'PageDown') { page++; draw(); }
        if (e.key === 'ArrowLeft' || e.key === 'PageUp') { page--; draw(); }
    };
    document.addEventListener('keydown', onKey);
    // Swipe left/right on phones.
    let touchX = null;
    body.addEventListener('touchstart', e => { touchX = e.touches[0].clientX; }, { passive: true });
    body.addEventListener('touchend', e => {
        if (touchX === null || zoom > 1) return;
        const dx = e.changedTouches[0].clientX - touchX;
        if (Math.abs(dx) > 60) { page += dx < 0 ? 1 : -1; draw(); }
        touchX = null;
    });
    const onResize = () => draw();
    window.addEventListener('resize', onResize);

    loadPdfJs()
        .then(pdfjs => fetch(url, { credentials: 'same-origin' }).then(res => {
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            return res.arrayBuffer();
        }).then(data => pdfjs.getDocument({ data }).promise))
        .then(loaded => {
            if (cancelled) return;
            doc = loaded;
            body.querySelector('.text-center')?.remove();
            body.prepend(canvas);
            draw();
        })
        .catch(fail);

    return () => {
        cancelled = true;
        document.removeEventListener('keydown', onKey);
        window.removeEventListener('resize', onResize);
        doc?.destroy();
    };
}

// Any [data-open-media] button opens the reader. Books report the reading position to the account.
document.addEventListener('click', e => {
    const btn = e.target.closest('[data-open-media]');
    if (!btn) return;
    e.preventDefault();
    const bookId = btn.dataset.bookId;
    openMedia({
        url: btn.dataset.openMedia,
        kind: btn.dataset.mediaKind || 'pdf',
        title: btn.dataset.mediaTitle || '',
        startAt: Number(btn.dataset.position || 0),
        onPosition: bookId ? (position, progress) => {
            btn.dataset.position = position;
            window.dispatchEvent(new CustomEvent('acadexa:reading', { detail: { bookId: Number(bookId), position, progress } }));
        } : undefined,
    });
});
