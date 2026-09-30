// Instructor course builder: curriculum drag & drop, module/lesson modals,
// lesson editor (rich text, video source, resumable chunked upload) and quiz builder.
import { icon } from './icons';

const csrf = () => document.querySelector('meta[name="csrf-token"]').content;
const i18n = window.ACADEXA_I18N || {};
const t = (key, vars = {}) => Object.entries(vars).reduce((s, [k, v]) => s.replaceAll(`:${k}`, v), i18n[key] || key);
const toast = (msg, type = 'info') => (window.showToast ? window.showToast(msg, type) : console.log(msg));

async function postJson(url, body) {
    const res = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf() },
        body: JSON.stringify(body),
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error(data.message || `HTTP ${res.status}`);
    return data;
}

// ─── Curriculum page ──────────────────────────────────────────────────────────
function initCurriculum() {
    const root = document.getElementById('curriculum');
    if (!root || !window.Sortable || root.dataset.locked === '1') return;

    const saveModules = () => {
        const ids = [...root.querySelectorAll(':scope > [data-module-id]')].map(el => Number(el.dataset.moduleId));
        postJson(root.dataset.modulesReorder, { ids })
            .then(data => {
                toast(data.message, 'success');
                root.querySelectorAll(':scope > [data-module-id] .module-index').forEach((el, i) => {
                    el.textContent = el.textContent.replace(/\d+$/, i + 1);
                });
            })
            .catch(err => toast(err.message, 'error'));
    };

    const saveLessons = () => {
        const modules = [...root.querySelectorAll('[data-lessons-of]')].map(list => ({
            id: Number(list.dataset.lessonsOf),
            lessons: [...list.querySelectorAll('[data-lesson-id]')].map(el => Number(el.dataset.lessonId)),
        }));
        postJson(root.dataset.lessonsReorder, { modules })
            .then(data => toast(data.message, 'success'))
            .catch(err => toast(err.message, 'error'));
    };

    new window.Sortable(root, { handle: '.module-handle', draggable: '[data-module-id]', animation: 150, ghostClass: 'sortable-ghost', onEnd: saveModules });
    root.querySelectorAll('[data-lessons-of]').forEach(list => {
        new window.Sortable(list, {
            group: 'lessons', handle: '.lesson-handle', draggable: '[data-lesson-id]', animation: 150,
            ghostClass: 'sortable-ghost',
            // Moving between modules fires onEnd once on the source list.
            onEnd: saveLessons,
        });
    });
}

function initModuleModal() {
    const modal = document.getElementById('moduleModal');
    if (!modal) return;
    const form = document.getElementById('moduleForm');
    const method = document.getElementById('moduleMethod');
    const title = document.getElementById('moduleModalTitle');

    modal.addEventListener('show.bs.modal', event => {
        const trigger = event.relatedTarget;
        form.reset();
        if (trigger?.dataset.moduleMode === 'edit') {
            form.action = trigger.dataset.action;
            method.value = 'PUT';
            title.textContent = title.dataset.edit;
            form.querySelector('[name=duration_hours]').value = trigger.dataset.hours || '';
            const translations = JSON.parse(trigger.dataset.translations || '{}');
            Object.entries(translations).forEach(([loc, tr]) => {
                const titleInput = form.querySelector(`[name="translations[${loc}][title]"]`);
                const descInput = form.querySelector(`[name="translations[${loc}][description]"]`);
                if (titleInput) titleInput.value = tr.title || '';
                if (descInput) descInput.value = tr.description || '';
            });
        } else {
            form.action = form.dataset.createAction;
            method.value = 'POST';
            title.textContent = title.dataset.create;
        }
    });
    modal.addEventListener('shown.bs.modal', () => form.querySelector('[name=duration_hours]').focus());
}

function initLessonModal() {
    const modal = document.getElementById('lessonModal');
    if (!modal) return;
    const form = document.getElementById('lessonForm');
    modal.addEventListener('show.bs.modal', event => {
        form.reset();
        form.action = event.relatedTarget.dataset.action;
        document.getElementById('lessonModalModule').textContent = event.relatedTarget.dataset.moduleTitle || '';
    });
    modal.addEventListener('shown.bs.modal', () => form.querySelector('[name=title]').focus());
}

// ─── Lesson editor ────────────────────────────────────────────────────────────
function initRichEditors() {
    if (!window.Quill) return;
    document.querySelectorAll('[data-rich-editor]').forEach(textarea => {
        const holder = document.createElement('div');
        textarea.insertAdjacentElement('afterend', holder);
        textarea.hidden = true;
        const quill = new window.Quill(holder, {
            theme: 'snow',
            placeholder: textarea.getAttribute('placeholder') || '',
            modules: {
                toolbar: [
                    [{ header: [2, 3, 4, false] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    [{ list: 'ordered' }, { list: 'bullet' }],
                    ['blockquote', 'code-block'],
                    ['link', 'image', 'video'],
                    ['clean'],
                ],
            },
        });
        quill.clipboard.dangerouslyPasteHTML(textarea.value);
        textarea.form.addEventListener('submit', () => {
            textarea.value = quill.getText().trim() === '' && !holder.querySelector('img,iframe') ? '' : quill.root.innerHTML;
        });
    });
}

function initTypeAndSource() {
    const form = document.getElementById('lessonEditor');
    if (!form) return;

    const sync = () => {
        const type = form.querySelector('[name=type]:checked')?.value;
        form.querySelectorAll('[data-for-type]').forEach(el => {
            el.hidden = !el.dataset.forType.split(',').includes(type);
        });
        const source = form.querySelector('[name=video_source]:checked')?.value;
        form.querySelectorAll('[data-for-source]').forEach(el => {
            el.hidden = el.dataset.forSource !== source;
        });
    };
    form.addEventListener('change', event => {
        if (['type', 'video_source'].includes(event.target.name)) sync();
    });
    sync();

    // Live YouTube preview so the instructor sees exactly what students will see.
    const yt = form.querySelector('[name=youtube_url]');
    const preview = document.getElementById('youtubePreview');
    if (yt && preview) {
        const render = () => {
            const id = parseYoutubeId(yt.value);
            const list = id ? null : (yt.value.match(/[?&]list=([A-Za-z0-9_-]{10,64})/) || [])[1];
            const src = id ? `https://www.youtube-nocookie.com/embed/${id}?rel=0&modestbranding=1` : `https://www.youtube-nocookie.com/embed/videoseries?list=${list}&rel=0`;
            preview.innerHTML = (id || list)
                ? `${list ? `<div class="small text-success mb-1">${icon('collection-play', 'me-1')}${t('playlist_detected')}</div>` : ''}<div class="ratio ratio-16x9 rounded overflow-hidden"><iframe src="${src}" allow="encrypted-media; picture-in-picture" allowfullscreen title="YouTube"></iframe></div>`
                : (yt.value.trim() ? `<div class="text-danger small">${t('youtube_invalid')}</div>` : '');
        };
        yt.addEventListener('input', debounce(render, 400));
        render();
    }
}

export function parseYoutubeId(url) {
    const value = (url || '').trim();
    if (/^[A-Za-z0-9_-]{11}$/.test(value)) return value;
    const m = value.match(/(?:youtube(?:-nocookie)?\.com\/(?:watch\?(?:.*&)?v=|embed\/|shorts\/|live\/|v\/)|youtu\.be\/)([A-Za-z0-9_-]{11})/i);
    return m ? m[1] : null;
}

function debounce(fn, ms) {
    let timer;
    return (...args) => { clearTimeout(timer); timer = setTimeout(() => fn(...args), ms); };
}

// ─── Chunked, resumable video upload ─────────────────────────────────────────
function initVideoUpload() {
    const box = document.getElementById('videoUploader');
    if (!box) return;

    const input = box.querySelector('input[type=file]');
    const zone = box.querySelector('.dropzone');
    const status = box.querySelector('[data-role=status]');
    const chunkSize = Number(box.dataset.chunkMb) * 1024 * 1024;
    const maxBytes = Number(box.dataset.maxMb) * 1024 * 1024;
    const allowed = box.dataset.extensions.split(',');
    let paused = false;
    let running = false;

    zone.addEventListener('click', () => !running && input.click());
    zone.addEventListener('keydown', e => { if ((e.key === 'Enter' || e.key === ' ') && !running) { e.preventDefault(); input.click(); } });
    ['dragenter', 'dragover'].forEach(ev => zone.addEventListener(ev, e => { e.preventDefault(); zone.classList.add('is-over'); }));
    ['dragleave', 'drop'].forEach(ev => zone.addEventListener(ev, e => { e.preventDefault(); zone.classList.remove('is-over'); }));
    zone.addEventListener('drop', e => { if (!running && e.dataTransfer.files[0]) start(e.dataTransfer.files[0]); });
    input.addEventListener('change', () => input.files[0] && start(input.files[0]));

    // Leaving the page mid-upload would lose progress (it can be resumed, but warn anyway).
    window.addEventListener('beforeunload', e => { if (running) { e.preventDefault(); e.returnValue = ''; } });

    function readDuration(file) {
        return new Promise(resolve => {
            const video = document.createElement('video');
            video.preload = 'metadata';
            video.onloadedmetadata = () => { URL.revokeObjectURL(video.src); resolve(isFinite(video.duration) ? video.duration : null); };
            video.onerror = () => resolve(null);
            video.src = URL.createObjectURL(file);
        });
    }

    // Same file (name + size + date) => same upload id, so a retry resumes where it stopped.
    async function uploadIdFor(file) {
        const raw = `${box.dataset.lessonId}-${file.name}-${file.size}-${file.lastModified}`;
        if (crypto.subtle) {
            const hash = await crypto.subtle.digest('SHA-256', new TextEncoder().encode(raw));
            return [...new Uint8Array(hash)].slice(0, 16).map(b => b.toString(16).padStart(2, '0')).join('');
        }
        return raw.replace(/[^A-Za-z0-9]/g, '').slice(-60).padStart(12, '0');
    }

    function render(html) { status.innerHTML = html; }

    async function start(file) {
        const ext = file.name.split('.').pop().toLowerCase();
        if (!allowed.includes(ext)) return toast(t('upload_bad_extension'), 'error');
        if (file.size > maxBytes) return toast(t('upload_too_large', { max: box.dataset.maxMb }), 'error');

        running = true;
        paused = false;
        const total = Math.max(1, Math.ceil(file.size / chunkSize));
        const uploadId = await uploadIdFor(file);
        const duration = await readDuration(file);

        render(`
            <div class="upload-card mt-3">
                <div class="d-flex justify-content-between align-items-center mb-2 gap-2">
                    <div class="text-truncate">${icon('film', 'me-1')}<strong>${escapeHtml(file.name)}</strong>
                        <span class="text-muted small">(${formatBytes(file.size)})</span></div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-light" data-action="pause">${icon('pause-fill')} ${t('pause')}</button>
                    </div>
                </div>
                <div class="progress" style="height:10px"><div class="progress-bar progress-bar-striped progress-bar-animated" style="width:0%"></div></div>
                <div class="d-flex justify-content-between small text-muted mt-1"><span data-role="pct">0 %</span><span data-role="eta"></span></div>
            </div>`);
        const bar = status.querySelector('.progress-bar');
        const pct = status.querySelector('[data-role=pct]');
        const eta = status.querySelector('[data-role=eta]');
        const pauseBtn = status.querySelector('[data-action=pause]');
        pauseBtn.addEventListener('click', () => {
            paused = !paused;
            pauseBtn.innerHTML = paused ? `${icon('play-fill')} ${t('resume')}` : `${icon('pause-fill')} ${t('pause')}`;
            if (!paused) loop();
        });

        let received = [];
        try {
            const res = await fetch(`${box.dataset.statusUrl}?upload_id=${uploadId}`, { headers: { Accept: 'application/json' } });
            if (res.ok) received = (await res.json()).received || [];
        } catch (e) { /* start from scratch */ }

        const done = new Set(received);
        if (done.size) toast(t('upload_resuming', { pct: Math.round((done.size / total) * 100) }), 'info');
        const startedAt = Date.now();
        const startedDone = done.size;

        const update = () => {
            const p = Math.round((done.size / total) * 100);
            bar.style.width = p + '%';
            pct.textContent = `${p} % — ${formatBytes(Math.min(file.size, done.size * chunkSize))} / ${formatBytes(file.size)}`;
            const elapsed = (Date.now() - startedAt) / 1000;
            const rate = (done.size - startedDone) / Math.max(elapsed, 1);
            if (rate > 0 && done.size < total) {
                eta.textContent = t('upload_eta', { time: formatDuration((total - done.size) / rate) });
            }
        };
        update();

        async function sendChunk(index, attempt = 1) {
            const form = new FormData();
            form.append('upload_id', uploadId);
            form.append('index', index);
            form.append('total', total);
            form.append('filename', file.name);
            form.append('size', file.size);
            if (duration) form.append('duration_seconds', duration);
            form.append('chunk', file.slice(index * chunkSize, Math.min(file.size, (index + 1) * chunkSize)), 'chunk');
            try {
                const res = await fetch(box.dataset.chunkUrl, {
                    method: 'POST', body: form, headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf() },
                });
                const data = await res.json().catch(() => ({}));
                if (res.status === 422 || res.status === 403 || res.status === 423) {
                    const err = new Error(data.message || Object.values(data.errors || {}).flat()[0] || `HTTP ${res.status}`);
                    err.fatal = true;
                    throw err;
                }
                if (!res.ok) throw new Error(`HTTP ${res.status}`);
                return data;
            } catch (err) {
                // Network hiccup: retry with backoff (poor connections are common).
                if (!err.fatal && attempt < 6) {
                    await new Promise(r => setTimeout(r, Math.min(30000, 1000 * 2 ** attempt)));
                    return sendChunk(index, attempt + 1);
                }
                throw err;
            }
        }

        async function loop() {
            try {
                for (let i = 0; i < total; i++) {
                    if (paused) return;
                    if (done.has(i)) continue;
                    const data = await sendChunk(i);
                    done.add(i);
                    update();
                    if (data.done) {
                        running = false;
                        bar.classList.remove('progress-bar-animated', 'progress-bar-striped');
                        bar.classList.add('bg-success');
                        toast(data.message, 'success');
                        setTimeout(() => window.location.reload(), 900);
                        return;
                    }
                }
            } catch (err) {
                running = false;
                bar.classList.add('bg-danger');
                eta.textContent = '';
                pct.innerHTML = `<span class="text-danger">${escapeHtml(err.message)}</span> — ${t('upload_retry_hint')}`;
                toast(err.message, 'error');
            }
        }
        loop();
    }
}

function formatBytes(bytes) {
    const units = i18n.byte_units || ['B', 'KB', 'MB', 'GB'];
    let i = 0; let v = bytes;
    while (v >= 1024 && i < units.length - 1) { v /= 1024; i++; }
    return `${v.toLocaleString(document.documentElement.lang, { maximumFractionDigits: i ? 1 : 0 })} ${units[i]}`;
}

function formatDuration(seconds) {
    const s = Math.round(seconds);
    if (s < 60) return t('seconds_short', { count: s });
    const m = Math.floor(s / 60);
    if (m < 60) return t('minutes_short', { count: m });
    return t('hours_minutes_short', { hours: Math.floor(m / 60), minutes: m % 60 });
}

function escapeHtml(str) {
    return String(str).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}

// ─── Quiz builder ─────────────────────────────────────────────────────────────
function initQuizBuilder() {
    const modal = document.getElementById('questionModal');
    if (!modal) return;
    const form = document.getElementById('questionForm');
    const list = form.querySelector('[data-role=options]');
    const typeSelect = form.querySelector('[name=type]');

    const addOption = (text = '', correct = false) => {
        const index = list.children.length;
        const row = document.createElement('div');
        row.className = 'option-row';
        row.innerHTML = `
            <input class="form-check-input mt-0 flex-shrink-0" type="${typeSelect.value === 'multiple' ? 'checkbox' : 'radio'}"
                   name="correct[]" value="${index}" ${correct ? 'checked' : ''} title="${t('correct_answer')}" aria-label="${t('correct_answer')}">
            <input type="text" class="form-control form-control-sm" name="options[${index}][text]" maxlength="1000" placeholder="${t('option')} ${index + 1}">
            <button type="button" class="btn btn-sm btn-light" data-action="remove" aria-label="${t('delete')}">${icon('x-lg')}</button>`;
        row.querySelector('[type=text]').value = text;
        row.querySelector('[data-action=remove]').addEventListener('click', () => {
            if (list.children.length <= 2) return toast(t('quiz_need_two_options'), 'warning');
            row.remove();
            renumber();
        });
        list.appendChild(row);
    };

    // Keep option indexes contiguous so "correct[]" values match "options[i]".
    const renumber = () => {
        [...list.children].forEach((row, i) => {
            row.querySelector('[name="correct[]"]').value = i;
            row.querySelector('[type=text]').name = `options[${i}][text]`;
            row.querySelector('[type=text]').placeholder = `${t('option')} ${i + 1}`;
        });
    };

    typeSelect.addEventListener('change', () => {
        list.querySelectorAll('[name="correct[]"]').forEach(input => {
            input.type = typeSelect.value === 'multiple' ? 'checkbox' : 'radio';
        });
    });
    form.querySelector('[data-action=add-option]').addEventListener('click', () => {
        if (list.children.length >= 10) return;
        addOption();
    });

    modal.addEventListener('show.bs.modal', event => {
        const trigger = event.relatedTarget;
        form.reset();
        list.innerHTML = '';
        const method = form.querySelector('[name=_method]');
        if (trigger?.dataset.question) {
            const q = JSON.parse(trigger.dataset.question);
            form.action = trigger.dataset.action;
            method.value = 'PUT';
            form.querySelector('[name=question]').value = q.question;
            typeSelect.value = q.type;
            const moduleSelect = form.querySelector('[name=module_id]');
            if (moduleSelect) moduleSelect.value = q.module_id || '';
            const explanation = form.querySelector('[name=explanation]');
            if (explanation) explanation.value = q.explanation || '';
            q.options.forEach(o => addOption(o.text, o.correct));
        } else {
            form.action = form.dataset.createAction;
            method.value = 'POST';
            typeSelect.value = 'single';
            addOption(); addOption(); addOption(); addOption();
        }
    });
    modal.addEventListener('shown.bs.modal', () => form.querySelector('[name=question]').focus());

    form.addEventListener('submit', event => {
        if (!form.querySelector('[name="correct[]"]:checked')) {
            event.preventDefault();
            toast(t('quiz_need_correct'), 'warning');
        }
    });

    const questions = document.getElementById('questionList');
    if (questions && window.Sortable && questions.dataset.reorderUrl) {
        new window.Sortable(questions, {
            handle: '.drag-handle', animation: 150, ghostClass: 'sortable-ghost',
            onEnd: () => {
                const ids = [...questions.querySelectorAll('[data-question-id]')].map(el => Number(el.dataset.questionId));
                postJson(questions.dataset.reorderUrl, { ids }).then(d => toast(d.message, 'success')).catch(e => toast(e.message, 'error'));
            },
        });
    }
}

// ─── New course: step-by-step wizard (the course is only created at the last step) ───
function initCreateWizard() {
    const form = document.getElementById('courseWizard');
    if (!form) return;
    const steps = [...form.querySelectorAll('[data-step]')];
    const tabs = [...document.querySelectorAll('[data-step-tab]')];
    const prev = form.querySelector('[data-wizard-prev]');
    const next = form.querySelector('[data-wizard-next]');
    const submit = form.querySelector('[data-wizard-submit]');
    // After a server-side error, open the first step containing an invalid field.
    let current = Math.max(0, steps.findIndex(step => step.querySelector('.is-invalid')));

    const show = index => {
        current = index;
        steps.forEach((step, i) => { step.hidden = i !== index; });
        tabs.forEach((tab, i) => {
            tab.classList.toggle('active', i === index);
            tab.classList.toggle('done', i < index);
        });
        prev.hidden = index === 0;
        next.hidden = index === steps.length - 1;
        submit.hidden = index !== steps.length - 1;
        steps[index].querySelector('input, select, textarea')?.focus({ preventScroll: true });
    };

    // Fields of the current step must be valid before going on.
    const fieldsValid = index => [...steps[index].querySelectorAll('input, select, textarea')].every(field => {
        if (field.checkValidity()) { field.classList.remove('is-invalid'); return true; }
        field.classList.add('is-invalid');
        field.reportValidity();
        return false;
    });
    // Content step: at least one of YouTube link, uploaded video, course material.
    const contentValid = index => {
        const error = steps[index].querySelector('#contentError');
        if (!error) return true;
        const youtube = steps[index].querySelector('[name=content_youtube_url]')?.value.trim();
        const files = steps[index].querySelectorAll('[name^="videos["][name$="[token]"], [name^="documents["][name$="[token]"]').length;
        const ok = Boolean(youtube) || files > 0;
        error.classList.toggle('d-none', ok);
        if (!ok) error.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return ok;
    };
    const stepValid = index => fieldsValid(index) && contentValid(index);

    next.addEventListener('click', () => { if (stepValid(current)) show(current + 1); });
    prev.addEventListener('click', () => show(current - 1));
    tabs.forEach((tab, i) => tab.addEventListener('click', () => {
        if (i <= current || steps.slice(0, i).every((_, k) => stepValid(k))) show(i);
    }));

    // "Enter" in a field goes to the next step instead of creating the course early.
    form.addEventListener('keydown', e => {
        if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA' && current < steps.length - 1) {
            e.preventDefault();
            next.click();
        }
    });
    form.addEventListener('submit', e => {
        if (current < steps.length - 1) { e.preventDefault(); next.click(); return; }
        const invalid = steps.findIndex((_, i) => !stepValid(i));
        if (invalid !== -1) { e.preventDefault(); show(invalid); return; }
        submit.disabled = true;
    });

    show(current);
}

// ─── Live preview of a YouTube link ([data-youtube-input] + [data-youtube-preview]) ───
function initYoutubePreview() {
    document.querySelectorAll('[data-youtube-input]').forEach(input => {
        const box = input.closest('section, form, div').querySelector('[data-youtube-preview]');
        if (!box) return;
        const render = () => {
            const value = input.value.trim();
            const playlist = input.hasAttribute('data-allow-playlist') ? (value.match(/[?&]list=([A-Za-z0-9_-]{10,64})/) || [])[1] : null;
            const id = parseYoutubeId(value);
            input.classList.toggle('is-invalid', Boolean(value) && !id && !playlist);
            const src = playlist
                ? `https://www.youtube-nocookie.com/embed/videoseries?list=${playlist}&rel=0&modestbranding=1`
                : `https://www.youtube-nocookie.com/embed/${id}?rel=0&modestbranding=1`;
            box.innerHTML = (id || playlist) ? `${playlist ? `<div class="small text-success mb-1">${icon('collection-play', 'me-1')}${t('playlist_detected')}</div>` : ''}<div class="ratio ratio-16x9 rounded overflow-hidden bg-dark">
                <iframe src="${src}" title="YouTube"
                        allow="accelerometer; encrypted-media; gyroscope; picture-in-picture; fullscreen" allowfullscreen></iframe></div>`
                : (value ? `<div class="text-danger small">${t('youtube_invalid')}</div>` : '');
        };
        input.addEventListener('input', render);
        render();
    });
}

// ─── Course presentation video: YouTube link or uploaded file (1 MB chunks, 20 MB max) ───
function initIntroVideo() {
    const root = document.querySelector('[data-intro-video]');
    if (!root) return;
    const form = root.closest('form');
    const panels = root.querySelectorAll('[data-intro-panel]');
    const radios = root.querySelectorAll('[name=intro_source]');
    const box = root.querySelector('[data-intro-uploader]');
    const token = box.querySelector('[name=intro_video_token]');
    const input = box.querySelector('input[type=file]');
    const zone = box.querySelector('.dropzone');
    const status = box.querySelector('[data-role=status]');
    const preview = box.querySelector('[data-role=preview]');
    const maxBytes = Number(box.dataset.maxMb) * 1024 * 1024;
    const chunkSize = Number(box.dataset.chunkMb) * 1024 * 1024;
    const allowed = box.dataset.extensions.split(',');
    let uploading = false;

    const source = () => root.querySelector('[name=intro_source]:checked')?.value || 'youtube';
    radios.forEach(r => r.addEventListener('change', () => {
        panels.forEach(p => { p.hidden = p.dataset.introPanel !== source(); });
    }));

    zone.addEventListener('click', () => !uploading && input.click());
    zone.addEventListener('keydown', e => { if ((e.key === 'Enter' || e.key === ' ') && !uploading) { e.preventDefault(); input.click(); } });
    ['dragenter', 'dragover'].forEach(ev => zone.addEventListener(ev, e => { e.preventDefault(); zone.classList.add('is-over'); }));
    ['dragleave', 'drop'].forEach(ev => zone.addEventListener(ev, e => { e.preventDefault(); zone.classList.remove('is-over'); }));
    zone.addEventListener('drop', e => { if (!uploading && e.dataTransfer.files[0]) upload(e.dataTransfer.files[0]); });
    input.addEventListener('change', () => input.files[0] && upload(input.files[0]));

    async function upload(file) {
        const ext = file.name.split('.').pop().toLowerCase();
        if (!allowed.includes(ext)) return toast(t('upload_bad_extension'), 'warning');
        if (file.size > maxBytes) return toast(t('upload_too_large').replace(':max', box.dataset.maxMb), 'warning');

        uploading = true;
        token.value = '';
        const uploadId = (crypto.randomUUID ? crypto.randomUUID() : String(Date.now()) + Math.random()).replace(/[^A-Za-z0-9]/g, '').slice(0, 32);
        const total = Math.max(1, Math.ceil(file.size / chunkSize));
        status.innerHTML = `<div class="progress" style="height:8px"><div class="progress-bar" style="width:0%"></div></div>
            <div class="small text-muted mt-1" data-role="label">${file.name}</div>`;
        const bar = status.querySelector('.progress-bar');

        try {
            for (let index = 0; index < total; index++) {
                const body = new FormData();
                body.append('upload_id', uploadId);
                body.append('index', index);
                body.append('total', total);
                body.append('filename', file.name);
                body.append('size', file.size);
                body.append('chunk', file.slice(index * chunkSize, (index + 1) * chunkSize), 'chunk');
                const res = await fetch(box.dataset.url, {
                    method: 'POST', body, credentials: 'same-origin',
                    headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf() },
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) throw new Error(data.message || Object.values(data.errors || {})[0]?.[0] || `HTTP ${res.status}`);
                bar.style.width = `${Math.round(((index + 1) / total) * 100)}%`;
                if (data.done) token.value = data.token;
            }
            status.innerHTML = `<span class="text-success small">${icon('check-circle', 'me-1')}${t('video_ready')} — ${file.name}</span>`;
            preview.innerHTML = `<div class="ratio ratio-16x9 rounded overflow-hidden bg-dark"><video controls preload="metadata"></video></div>`;
            preview.querySelector('video').src = URL.createObjectURL(file);
            root.querySelector('[data-intro-current]')?.remove();
        } catch (err) {
            status.innerHTML = `<span class="text-danger small">${icon('exclamation-circle', 'me-1')}${err.message}</span>`;
        } finally {
            uploading = false;
        }
    }

    // Only the chosen source is sent; the course cannot be saved while the file is still uploading.
    form.addEventListener('submit', e => {
        if (uploading) {
            e.preventDefault();
            e.stopImmediatePropagation();
            toast(t('upload_in_progress'), 'warning');
            return;
        }
        if (source() === 'upload') {
            const url = root.querySelector('[name=intro_youtube_url]');
            if (url) url.value = '';
        } else {
            token.value = '';
        }
    }, true);
}

// ─── New course: modules with a name and a number of lessons ───────────────────
function initStructure() {
    const list = document.getElementById('moduleRows');
    if (!list) return;
    const total = document.getElementById('structureTotal');

    const renumber = () => {
        [...list.querySelectorAll('[data-module-row]')].forEach((row, i) => {
            row.querySelector('.structure-num').textContent = i + 1;
            row.querySelectorAll('input').forEach(input => {
                input.name = input.name.replace(/modules\[\d+\]/, `modules[${i}]`);
            });
        });
        const rows = list.querySelectorAll('[data-module-row]');
        const lessons = [...rows].reduce((sum, row) => sum + (Number(row.querySelector('[name$="[lessons]"]').value) || 0), 0);
        total.textContent = total.dataset.template.replace(':modules', rows.length).replace(':lessons', lessons);
        rows.forEach(row => { row.querySelector('[data-remove-module]').disabled = rows.length === 1; });
    };

    document.getElementById('addModule').addEventListener('click', () => {
        const rows = list.querySelectorAll('[data-module-row]');
        const row = rows[rows.length - 1].cloneNode(true);
        row.querySelectorAll('input').forEach(input => { input.classList.remove('is-invalid'); });
        row.querySelector('[name$="[title]"]').value = `${list.dataset.moduleLabel} ${rows.length + 1}`;
        row.querySelector('[name$="[hours]"]').value = '';
        list.appendChild(row);
        renumber();
        row.querySelector('[name$="[title]"]').select();
    });
    list.addEventListener('click', e => {
        const btn = e.target.closest('[data-remove-module]');
        if (btn && list.querySelectorAll('[data-module-row]').length > 1) { btn.closest('[data-module-row]').remove(); renumber(); }
    });
    list.addEventListener('input', renumber);
    renumber();
}

// ─── Several files uploaded in 1 MB chunks (videos, course materials) ─────────
let uploadsRunning = 0;
function initMultiUpload() {
    document.querySelectorAll('[data-multi-upload]').forEach(box => {
        const input = box.querySelector('input[type=file]');
        const zone = box.querySelector('.dropzone');
        const list = box.querySelector('[data-role=list]');
        const maxBytes = Number(box.dataset.maxMb) * 1024 * 1024;
        const chunkSize = Number(box.dataset.chunkMb) * 1024 * 1024;
        const allowed = box.dataset.extensions.split(',');
        const queue = [];
        let busy = false;
        let counter = 0;

        zone.addEventListener('click', () => input.click());
        zone.addEventListener('keydown', e => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); input.click(); } });
        ['dragenter', 'dragover'].forEach(ev => zone.addEventListener(ev, e => { e.preventDefault(); zone.classList.add('is-over'); }));
        ['dragleave', 'drop'].forEach(ev => zone.addEventListener(ev, e => { e.preventDefault(); zone.classList.remove('is-over'); }));
        zone.addEventListener('drop', e => add([...e.dataTransfer.files]));
        input.addEventListener('change', () => { add([...input.files]); input.value = ''; });

        function add(files) {
            files.forEach(file => {
                const ext = file.name.split('.').pop().toLowerCase();
                const item = document.createElement('li');
                item.className = 'upload-item';
                item.innerHTML = `<span class="flex-grow-1 min-w-0"><span class="d-block text-truncate small fw-semibold"></span>
                    <span class="progress mt-1" style="height:5px"><span class="progress-bar" style="width:0%"></span></span>
                    <span class="small text-muted" data-role="state"></span></span>
                    <button type="button" class="btn btn-sm btn-light text-danger" aria-label="${t('delete')}">${icon('x-lg')}</button>`;
                item.querySelector('.fw-semibold').textContent = file.name;
                item.querySelector('button').addEventListener('click', () => { item.remove(); renumberInputs(); });
                list.appendChild(item);
                if (!allowed.includes(ext)) return fail(item, box.dataset.kind === 'document' ? t('document_bad_extension') : t('upload_bad_extension'));
                if (file.size > maxBytes) return fail(item, t('upload_too_large').replace(':max', box.dataset.maxMb));
                queue.push({ file, item });
            });
            run();
        }

        function fail(item, message) {
            item.classList.add('is-error');
            item.querySelector('[data-role=state]').innerHTML = `<span class="text-danger">${message}</span>`;
        }

        // Hidden inputs follow the visible order: field[0], field[1]…
        function renumberInputs() {
            [...list.querySelectorAll('.upload-item[data-token]')].forEach((item, i) => {
                item.querySelector('[data-role=inputs]').innerHTML = '';
                item.querySelector('[data-role=inputs]').insertAdjacentHTML('beforeend',
                    `<input type="hidden" name="${box.dataset.field}[${i}][token]"><input type="hidden" name="${box.dataset.field}[${i}][name]">`);
                const [token, name] = item.querySelectorAll('[data-role=inputs] input');
                token.value = item.dataset.token;
                name.value = item.dataset.name;
            });
        }

        async function run() {
            if (busy) return;
            busy = true;
            uploadsRunning++;
            while (queue.length) {
                const { file, item } = queue.shift();
                if (!item.isConnected) continue;
                const bar = item.querySelector('.progress-bar');
                const state = item.querySelector('[data-role=state]');
                const uploadId = `${Date.now().toString(36)}${(++counter).toString(36)}${Math.random().toString(36).slice(2, 10)}`.replace(/[^A-Za-z0-9]/g, '').slice(0, 40);
                const total = Math.max(1, Math.ceil(file.size / chunkSize));
                try {
                    for (let index = 0; index < total; index++) {
                        const body = new FormData();
                        body.append('kind', box.dataset.kind);
                        body.append('upload_id', uploadId);
                        body.append('index', index);
                        body.append('total', total);
                        body.append('filename', file.name);
                        body.append('size', file.size);
                        body.append('chunk', file.slice(index * chunkSize, (index + 1) * chunkSize), 'chunk');
                        const res = await fetch(box.dataset.url, { method: 'POST', body, credentials: 'same-origin', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf() } });
                        const data = await res.json().catch(() => ({}));
                        if (!res.ok) throw new Error(data.message || Object.values(data.errors || {})[0]?.[0] || `HTTP ${res.status}`);
                        bar.style.width = `${Math.round(((index + 1) / total) * 100)}%`;
                        if (data.done) {
                            item.dataset.token = data.token;
                            item.dataset.name = file.name;
                            item.insertAdjacentHTML('beforeend', '<span data-role="inputs" hidden></span>');
                            state.innerHTML = `<span class="text-success">${icon('check-circle', 'me-1')}${data.size}</span>`;
                        }
                    }
                } catch (err) {
                    fail(item, err.message);
                }
                renumberInputs();
                document.getElementById('contentError')?.classList.add('d-none');
            }
            busy = false;
            uploadsRunning--;
        }
    });

    // The course cannot be created while files are still uploading.
    document.getElementById('courseWizard')?.addEventListener('submit', e => {
        if (uploadsRunning > 0) {
            e.preventDefault();
            e.stopImmediatePropagation();
            toast(t('upload_in_progress'), 'warning');
        }
    }, true);
}

document.addEventListener('DOMContentLoaded', () => {
    initCurriculum();
    initStructure();
    initMultiUpload();
    initIntroVideo();
    initCreateWizard();
    initYoutubePreview();
    initModuleModal();
    initLessonModal();
    initRichEditors();
    initTypeAndSource();
    initVideoUpload();
    initQuizBuilder();
});
