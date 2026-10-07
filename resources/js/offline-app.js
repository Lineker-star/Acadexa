// Offline app (/offline): reads the courses downloaded to IndexedDB and lets the student
// follow them without network — lessons, lesson quizzes, module exercises — and read the
// books of their library. Progress, watch time, quiz answers and reading positions are queued
// in the outbox and sent to the server automatically once back online.
import { icon } from './icons';
import { db } from './offline/db';
import { enqueue, flush, pendingCount } from './offline/outbox';
import { formatBytes, listDownloaded, getDownloaded } from './offline/downloader';
import { getLibrary, isCached, syncLibrary } from './offline/library';

const i18n = window.ACADEXA_I18N || {};
const t = (key, vars = {}) => Object.entries(vars).reduce((s, [k, v]) => s.replaceAll(`:${k}`, v), i18n[key] || key);
const app = document.getElementById('offlineApp');

const esc = str => String(str ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
const typeIcon = { video: 'play-circle', text: 'file-text', quiz: 'patch-question', assignment: 'clipboard-check', exam: 'clipboard-check', final: 'trophy' };
const bookIcon = { pdf: 'file-earmark-pdf', slides: 'file-earmark-slides', audio: 'file-earmark-music', video: 'file-earmark-play' };

let ownerId = null;
let activeTracker = null;

// ─── Connectivity pill ────────────────────────────────────────────────────────
async function renderConnectivity() {
    const pill = document.getElementById('connPill');
    const link = document.getElementById('onlineLink');
    const pending = await pendingCount();
    if (navigator.onLine) {
        pill.className = 'offline-pill ms-auto';
        pill.innerHTML = `${icon('wifi')}${t('online')}${pending ? ' · ' + t('pending_sync', { count: pending }) : ''}`;
        link.hidden = false;
    } else {
        pill.className = 'offline-pill off ms-auto';
        pill.innerHTML = `${icon('wifi-off')}${t('offline')}${pending ? ' · ' + t('pending_sync', { count: pending }) : ''}`;
        link.hidden = true;
    }
}
window.addEventListener('online', async () => { await flush(); renderConnectivity(); });
window.addEventListener('offline', renderConnectivity);
window.addEventListener('acadexa:synced', event => { applyServerGrades(event.detail); renderConnectivity(); });

// ─── Course path (same rules as the server, see ProgressService) ──────────────
function steps(course) {
    const list = [];
    course.modules.forEach(module => {
        module.lessons.forEach(lesson => list.push({ key: `lesson:${lesson.id}`, type: 'lesson', module, lesson }));
        if (module.exam) list.push({ key: `quiz:${module.exam.id}`, type: 'exam', module, quiz: module.exam });
    });
    if (course.final) list.push({ key: `quiz:${course.final.id}`, type: 'final', final: course.final });
    return list;
}

function doneKeys(course) {
    const done = new Set(course.completed_ids.map(id => `lesson:${id}`));
    (course.passed_quiz_ids || []).forEach(id => done.add(`quiz:${id}`));
    return done;
}

function unlockMap(course) {
    const done = doneKeys(course);
    const map = {};
    let allPrevious = true;
    for (const step of steps(course)) {
        let open;
        if (course.course.sequential) open = allPrevious;
        else if (step.type === 'lesson') open = true;
        else if (step.type === 'exam') open = step.module.lessons.every(l => done.has(`lesson:${l.id}`));
        else open = allPrevious;
        map[step.key] = open;
        if (!done.has(step.key)) allPrevious = false;
    }
    return map;
}

async function saveProgress(course) {
    const all = steps(course);
    const done = doneKeys(course);
    course.progress = all.length ? Math.round((all.filter(s => done.has(s.key)).length / all.length) * 10000) / 100 : 0;
    await db.put('courses', course);
}

async function markLocalComplete(course, lessonId) {
    if (!course.completed_ids.includes(lessonId)) course.completed_ids.push(lessonId);
    await saveProgress(course);
}

async function markLocalQuizPassed(course, quiz, passed) {
    if (quiz.scope === 'lesson') {
        const lesson = course.modules.flatMap(m => m.lessons).find(l => l.quiz?.id === quiz.id);
        if (!lesson) return;
        if (passed) await markLocalComplete(course, lesson.id);
        else { course.completed_ids = course.completed_ids.filter(id => id !== lesson.id); await saveProgress(course); }
        return;
    }
    course.passed_quiz_ids ||= [];
    if (passed && !course.passed_quiz_ids.includes(quiz.id)) course.passed_quiz_ids.push(quiz.id);
    if (!passed) course.passed_quiz_ids = course.passed_quiz_ids.filter(id => id !== quiz.id);
    await saveProgress(course);
}

// The server's grading is the one that counts: correct provisional offline results after a sync.
async function applyServerGrades(detail) {
    const quizResults = (detail?.results || []).filter(r => r.event?.type === 'quiz' && r.ok);
    if (!quizResults.length) return;
    for (const course of await listDownloaded()) {
        const quizzes = course.modules.flatMap(m => [...m.lessons.map(l => l.quiz), m.exam]).filter(Boolean);
        for (const r of quizResults) {
            const quiz = quizzes.find(q => q.id === r.event.quiz_id);
            // Diagnostics and retakes never change the path; only standard attempts are replayed here.
            if (quiz) await markLocalQuizPassed(course, quiz, Boolean(r.data?.passed));
        }
    }
}

async function sha256(text) {
    const bytes = new TextEncoder().encode(text);
    const digest = await crypto.subtle.digest('SHA-256', bytes);
    return [...new Uint8Array(digest)].map(b => b.toString(16).padStart(2, '0')).join('');
}

/** Provisional score computed on the device from the salted answer hashes. */
async function provisionalScore(course, quiz, answers) {
    if (!window.crypto?.subtle) return null;
    let correct = 0;
    for (const q of quiz.questions) {
        const ids = (answers[q.id] || []).slice().sort((a, b) => a - b).join(',');
        if (await sha256(`${course.hash_salt}|${q.id}|${ids}`) === q.check) correct++;
    }
    const total = quiz.questions.length;
    return { correct, total, score: total ? Math.round((correct / total) * 10000) / 100 : 0 };
}

// ─── Home: downloaded courses + library ───────────────────────────────────────
async function renderHome() {
    let courses = await listDownloaded();
    // Only show the downloads of the account that made them.
    courses = courses.filter(c => !ownerId || c.user_id === ownerId);
    const library = await getLibrary();
    const books = library && (!ownerId || library.user_id === ownerId) ? library.items : [];
    document.title = t('offline_courses');

    const cachedFlags = await Promise.all(books.map(b => isCached(b.url)));

    const coursesHtml = courses.length ? `
        <div class="row g-3">${courses.map(c => `
            <div class="col-sm-6 col-lg-4">
                <div class="offline-card">
                    <img src="${esc(c.course.thumbnail)}" alt="" onerror="this.style.visibility='hidden'">
                    <div class="p-3 d-flex flex-column flex-grow-1">
                        <h2 class="h6 fw-bold mb-1">${esc(c.course.title)}</h2>
                        <div class="small text-muted mb-2">${esc(c.course.instructor || '')} · ${esc(c.course.hours || '')}</div>
                        <div class="progress mb-1" style="height:6px"><div class="progress-bar" style="width:${c.progress}%"></div></div>
                        <div class="small text-muted mb-3">${t('progress_pct', { pct: c.progress })} · ${formatBytes(c.total_bytes)}</div>
                        <a href="#course-${c.enrollment_id}" class="btn btn-primary btn-sm mt-auto">${icon('play-fill', 'me-1')}${t('open')}</a>
                    </div>
                </div>
            </div>`).join('')}
        </div>` : `
        <div class="text-center py-4 px-3 bg-white rounded-xl shadow-brand">
            ${icon('cloud-arrow-down', '', 'font-size:2.5rem;color:var(--bs-primary);opacity:.45')}
            <h2 class="h5 mt-3">${t('offline_empty_title')}</h2>
            <p class="text-muted mx-auto small" style="max-width:520px">${t('offline_empty_help')}</p>
            ${navigator.onLine ? `<a class="btn btn-primary btn-sm" href="/my-courses">${t('go_my_courses')}</a>` : ''}
        </div>`;

    const libraryHtml = books.length ? books.map((b, i) => `
        <div class="library-card mb-2">
            <div class="cover">${icon(bookIcon[b.kind] || 'book')}</div>
            <div class="flex-grow-1 min-w-0">
                <div class="fw-semibold text-truncate">${esc(b.title)}</div>
                <div class="small text-muted text-truncate">${esc(b.author || '')}${b.author ? ' · ' : ''}${esc(b.course || '')}</div>
                <div class="progress mt-1" style="height:4px;max-width:220px"><div class="progress-bar" style="width:${b.progress || 0}%"></div></div>
            </div>
            ${cachedFlags[i]
                ? `<button type="button" class="btn btn-sm btn-primary" data-open-media="${esc(b.url)}" data-media-kind="${esc(b.kind)}" data-media-title="${esc(b.title)}" data-book-id="${b.book_id}" data-position="${b.position || 0}">${icon('book-half', 'me-1')}${t('read')}</button>`
                : `<span class="small text-muted">${icon('cloud-slash', 'me-1')}${t('book_not_downloaded')}</span>`}
        </div>`).join('') : `<p class="small text-muted">${t('library_empty_offline')}</p>`;

    app.innerHTML = `
        <h1 class="h4 fw-bold mb-1">${t('offline_courses')}</h1>
        <p class="text-muted small mb-4">${t('offline_home_help')}</p>
        ${coursesHtml}
        <h2 class="h5 fw-bold mt-5 mb-1">${icon('book', 'me-1')}${t('my_library')}</h2>
        <p class="text-muted small mb-3">${t('library_offline_help')}</p>
        ${libraryHtml}`;
}

// ─── Course ───────────────────────────────────────────────────────────────────
async function renderCourse(enrollmentId, target) {
    const course = await getDownloaded(enrollmentId);
    if (!course || (ownerId && course.user_id !== ownerId)) {
        location.hash = '';
        return;
    }
    const all = steps(course);
    const unlocked = unlockMap(course);
    const done = doneKeys(course);
    let step = all.find(s => s.key === target && unlocked[s.key] && s.type !== 'final')
        || all.find(s => !done.has(s.key) && unlocked[s.key] && s.type !== 'final')
        || all.find(s => s.type === 'lesson');
    if (!step) {
        app.innerHTML = `<p class="text-muted">${t('course_empty')}</p>`;
        return;
    }
    const index = all.indexOf(step);
    const prev = all[index - 1];
    const next = all[index + 1];
    const href = s => `#course-${course.enrollment_id}/${s.type === 'lesson' ? 'lesson-' + s.lesson.id : 'quiz-' + (s.quiz || s.final).id}`;
    const title = s => (s.type === 'lesson' ? s.lesson.title : (s.quiz || s.final).title);
    document.title = `${title(step)} — ${course.course.title}`;

    const main = step.type === 'lesson' ? lessonHtml(course, step.lesson, done.has(step.key)) : examHtml(course, step, done.has(step.key));

    app.innerHTML = `
        <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
            <a href="#" data-nav="home" class="btn btn-sm btn-light">${icon('arrow-left')}</a>
            <div class="flex-grow-1 min-w-0">
                <div class="fw-bold text-truncate">${esc(course.course.title)}</div>
                <div class="progress" style="height:5px;max-width:260px"><div class="progress-bar" id="offProgress" style="width:${course.progress}%"></div></div>
            </div>
        </div>
        <div class="row g-4">
            <div class="col-lg-8 order-2 order-lg-1">
                <div class="bg-white rounded-xl shadow-brand overflow-hidden">
                    ${main}
                    <div class="px-3 px-md-4 pb-3">
                        <nav class="lesson-nav">
                            ${prev && prev.type !== 'final' ? `<a class="btn btn-outline-secondary" href="${href(prev)}">${icon('chevron-left', 'me-1')}${t('previous')}</a>` : '<span></span>'}
                            ${next && next.type !== 'final' ? `<a class="btn btn-primary ${unlocked[next.key] ? '' : 'disabled'}" id="offNext" href="${href(next)}">${next.type === 'lesson' ? t('next') : esc(title(next))}${icon('chevron-right', 'ms-1')}</a>` : ''}
                        </nav>
                    </div>
                </div>
            </div>
            <aside class="col-lg-4 order-1 order-lg-2">
                <div class="bg-white rounded-xl shadow-brand">
                    ${course.modules.map((m, mi) => `
                        <div class="border-bottom">
                            <div class="px-3 py-2 fw-semibold small" style="background:#F8FAFD">${t('module')} ${mi + 1} : ${esc(m.title)} <span class="text-muted fw-normal">${esc(m.hours || '')}</span></div>
                            ${all.filter(s => s.module === m).map(s => outlineItem(course, s, step, unlocked, done, href, title)).join('')}
                        </div>`).join('')}
                    ${course.final ? `
                        <div class="outline-lesson locked outline-assessment">
                            <span class="state">${done.has(`quiz:${course.final.id}`) ? icon('check-circle-fill', 'text-success') : icon('wifi')}</span>
                            <span class="flex-grow-1">${esc(course.final.title)}<span class="d-block meta">${icon('trophy', 'me-1')}${t('final_online_only')}</span></span>
                        </div>` : ''}
                </div>
            </aside>
        </div>`;

    if (step.type === 'lesson') bindLesson(course, step.lesson, next);
    else bindQuiz(course, step.quiz);
}

function outlineItem(course, s, current, unlocked, done, href, title) {
    const open = unlocked[s.key];
    const ok = done.has(s.key);
    const state = ok ? icon('check-circle-fill', 'text-success') : (open ? icon('circle', 'text-muted') : icon('lock'));
    const cls = `outline-lesson ${s === current ? 'active' : ''} ${open ? '' : 'locked'} ${s.type !== 'lesson' ? 'outline-assessment' : ''}`;
    const meta = s.type === 'lesson'
        ? `${icon(typeIcon[s.lesson.type], 'me-1')}${s.lesson.minutes ? t('minutes_short', { count: s.lesson.minutes }) : ''}
           ${s.lesson.quiz_check ? ' · ' + t('with_quiz') : ''}${s.lesson.video && !s.lesson.video.offline ? ' · ' + icon('wifi') + ' ' + t('online_only') : ''}`
        : `${icon(typeIcon.exam, 'me-1')}${t('questions_n', { count: s.quiz.questions.length })}`;
    const inner = `<span class="state">${state}</span><span class="flex-grow-1">${esc(title(s))}<span class="d-block meta">${meta}</span></span>`;
    return open ? `<a class="${cls}" href="${href(s)}">${inner}</a>` : `<span class="${cls}">${inner}</span>`;
}

function lessonHtml(course, lesson, done) {
    const module = course.modules.find(m => m.lessons.includes(lesson));
    return `
        ${renderMedia(lesson)}
        <div class="p-3 p-md-4" data-protected>
            <div class="small text-muted">${esc(module?.title)}</div>
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                <h1 class="h5 fw-bold mb-0">${esc(lesson.title)}</h1>
                <div id="offComplete">${renderCompleteControl(lesson, done)}</div>
            </div>
            ${lesson.content ? `<div class="lesson-content mb-4">${lesson.content}</div>` : ''}
            ${lesson.type === 'assignment' ? `<div class="alert alert-info">${icon('wifi', 'me-1')}${t('assignment_online_only')}</div>` : ''}
            ${lesson.resources.length ? `
                <h2 class="h6 fw-bold mt-4">${t('resources')}</h2>
                ${lesson.resources.map(r => `
                    <div class="resource-link">
                        ${icon('file-earmark')}
                        <span class="flex-grow-1 min-w-0"><span class="d-block fw-semibold text-truncate">${esc(r.title)}</span><span class="small text-muted">${esc(r.name)} · ${esc(r.size)}</span></span>
                        ${r.kind ? `<button type="button" class="btn btn-sm btn-outline-primary" data-open-media="${esc(r.url)}" data-media-kind="${esc(r.kind)}" data-media-title="${esc(r.title)}">${icon('eye', 'me-1')}${t('open')}</button>` : ''}
                    </div>`).join('')}` : ''}
            ${lesson.quiz ? `
                <section class="mt-4" id="lessonQuiz">
                    ${lesson.quiz_check ? `<h2 class="h6 fw-bold">${icon('patch-question', 'me-1')}${t('check_your_knowledge')}</h2>` : ''}
                    ${renderQuiz(lesson.quiz)}
                </section>` : ''}
        </div>`;
}

function examHtml(course, step, done) {
    return `
        <div class="p-3 p-md-4" data-protected>
            <div class="small text-muted">${esc(step.module.title)}</div>
            <h1 class="h5 fw-bold mb-1">${icon('clipboard-check', 'me-1 text-primary')}${esc(step.quiz.title)}</h1>
            <div class="small text-muted mb-3">${t('questions_n', { count: step.quiz.questions.length })} · ${t('pass_at', { score: step.quiz.passing_score })}
                ${done ? ` · <span class="text-success">${icon('check-circle-fill', 'me-1')}${t('completed')}</span>` : ''}</div>
            ${renderQuiz(step.quiz)}
        </div>`;
}

function renderMedia(lesson) {
    if (lesson.type !== 'video' || !lesson.video) return '';
    if (lesson.video.offline) {
        return `<div class="video-stage" data-protected>
            <button type="button" class="wm-fullscreen" data-protected-fullscreen aria-label="${esc(t('fullscreen'))}">${icon('arrows-fullscreen')}</button>
            <div class="ratio ratio-16x9">
            <video id="offVideo" controls playsinline preload="metadata" controlsList="nodownload nofullscreen noremoteplayback" disablepictureinpicture src="${esc(lesson.video.url)}"></video></div></div>`;
    }
    return `<div class="p-4 text-center bg-dark text-white">
        ${icon('wifi-off', 'fs-2 d-block mb-2')}
        ${lesson.video.kind === 'youtube' ? t('youtube_needs_network') : t('video_needs_network')}
    </div>`;
}

function renderCompleteControl(lesson, done) {
    if (done) return `<span class="btn btn-success btn-sm disabled">${icon('check2-circle', 'me-1')}${t('completed')}</span>`;
    if (lesson.quiz_check) return `<a href="#lessonQuiz" class="btn btn-outline-primary btn-sm" data-scroll-quiz>${icon('patch-question', 'me-1')}${t('take_lesson_quiz')}</a>`;
    if (!lesson.completable) return '';
    return `<button type="button" class="btn btn-primary btn-sm" id="offCompleteBtn">${icon('check2', 'me-1')}${t('mark_complete')}</button>`;
}

function renderQuiz(quiz) {
    if (quiz.open) return `<div class="alert alert-info">${icon('wifi', 'me-1')}${t('exercise_online_only')}</div>`;
    if (quiz.timed) return `<div class="alert alert-info">${icon('stopwatch', 'me-1')}${t('timed_quiz_needs_network')}</div>`;
    return `
        <form id="offQuiz" class="border rounded-xl p-3 bg-light">
            <p class="small text-muted">${icon('info-circle', 'me-1')}${t('offline_quiz_help', { score: quiz.passing_score })}</p>
            ${quiz.questions.map((q, qi) => `
                <fieldset class="mb-3 quiz-question" data-question="${q.id}">
                    <legend class="fs-6 fw-semibold">${qi + 1}. ${esc(q.text)}</legend>
                    ${q.options.map(o => `
                        <label class="quiz-option d-flex align-items-center gap-2 mb-2">
                            <input class="form-check-input mt-0" type="${q.multiple ? 'checkbox' : 'radio'}" name="q${q.id}" value="${o.id}">
                            <span>${esc(o.text)}</span>
                        </label>`).join('')}
                </fieldset>`).join('')}
            <button class="btn btn-primary">${t('submit_answers')}</button>
            <div class="mt-2 fw-semibold" id="offQuizResult"></div>
        </form>`;
}

function bindLesson(course, lesson, next) {
    activeTracker?.stop();
    activeTracker = null;

    const onComplete = async () => {
        if (course.completed_ids.includes(lesson.id) || lesson.quiz_check) return;
        await enqueue({ type: 'complete', lesson_id: lesson.id, user_id: course.user_id });
        await markLocalComplete(course, lesson.id);
        refreshAfterProgress(course, lesson);
        window.showToast?.(navigator.onLine ? t('lesson_completed') : t('saved_offline'), 'success');
        flush().then(renderConnectivity);
    };
    document.getElementById('offCompleteBtn')?.addEventListener('click', onComplete);

    const video = document.getElementById('offVideo');
    if (video) {
        activeTracker = trackVideo(video, course, lesson, async () => {
            if (lesson.quiz_check) {
                window.showToast?.(t('now_take_quiz'), 'info');
                document.getElementById('lessonQuiz')?.scrollIntoView({ behavior: 'smooth' });
                return;
            }
            await onComplete();
            if (next && unlockMap(course)[next.key]) window.showToast?.(t('next_available'), 'info');
        });
    }
    if (lesson.quiz) bindQuiz(course, lesson.quiz, lesson);
}

function refreshAfterProgress(course, lesson) {
    if (lesson) {
        const box = document.getElementById('offComplete');
        if (box && course.completed_ids.includes(lesson.id)) box.innerHTML = renderCompleteControl(lesson, true);
    }
    const bar = document.getElementById('offProgress');
    if (bar) bar.style.width = `${course.progress}%`;
    const nextBtn = document.getElementById('offNext');
    if (nextBtn) nextBtn.classList.remove('disabled');
}

function bindQuiz(course, quiz, lesson = null) {
    const form = document.getElementById('offQuiz');
    form?.addEventListener('submit', async e => {
        e.preventDefault();
        const answers = {};
        form.querySelectorAll('.quiz-question').forEach(fs => {
            answers[fs.dataset.question] = [...fs.querySelectorAll('input:checked')].map(i => Number(i.value));
        });
        const result = document.getElementById('offQuizResult');
        form.querySelectorAll('input,button').forEach(el => { el.disabled = true; });

        const local = await provisionalScore(course, quiz, answers);
        await enqueue({ type: 'quiz', quiz_id: quiz.id, answers, user_id: course.user_id });
        if (local) {
            const passed = local.score >= quiz.passing_score;
            await markLocalQuizPassed(course, quiz, passed);
            result.innerHTML = `<span class="${passed ? 'text-success' : 'text-danger'}">${t('provisional_score', { score: local.score, correct: local.correct, total: local.total })}
                — ${passed ? t('provisional_passed') : t('provisional_failed', { score: quiz.passing_score })}</span>
                ${passed ? '' : ` <button type="button" class="btn btn-sm btn-outline-primary ms-2" data-retry>${t('retry')}</button>`}`;
            result.querySelector('[data-retry]')?.addEventListener('click', () => route());
            if (passed) refreshAfterProgress(course, lesson);
        } else {
            result.innerHTML = `<span class="text-primary">${icon('cloud-slash', 'me-1')}${t('quiz_saved_offline')}</span>`;
        }

        const sync = await flush();
        const mine = sync?.results?.find(r => r.event?.type === 'quiz' && r.event?.quiz_id === quiz.id);
        if (mine?.ok) {
            const d = mine.data;
            result.innerHTML = `<span class="${d.passed ? 'text-success' : 'text-danger'}">${t('score_line', { score: d.score, correct: d.correct, total: d.total })} — ${esc(d.message)}</span>`;
            await markLocalQuizPassed(course, quiz, d.passed);
            if (d.passed) refreshAfterProgress(course, lesson);
        }
        renderConnectivity();
    });
}

// Same rule as online: only real playback counts; reported in batches to the outbox.
function trackVideo(video, course, lesson, onEnded) {
    let last = null;
    let pending = 0;
    const send = async () => {
        const delta = Math.round(pending);
        if (!delta || !video.duration) return;
        pending -= delta;
        await enqueue({
            type: 'watch', lesson_id: lesson.id, user_id: course.user_id,
            position: Math.floor(video.currentTime), duration: Math.round(video.duration), watched_delta: Math.min(delta, 120),
        });
    };
    const timer = setInterval(send, 60000);
    const onTime = () => {
        if (video.paused || video.seeking) return;
        if (last !== null) {
            const step = video.currentTime - last;
            if (step > 0 && step <= 2.5 * Math.max(1, video.playbackRate)) pending += step;
        }
        last = video.currentTime;
    };
    const onPause = () => { last = null; send(); };
    const onSeeked = () => { last = video.currentTime; };
    const onEnd = async () => { last = null; await send(); await onEnded(); };
    video.addEventListener('timeupdate', onTime);
    video.addEventListener('pause', onPause);
    video.addEventListener('seeked', onSeeked);
    video.addEventListener('ended', onEnd);
    return {
        stop() {
            clearInterval(timer);
            send();
            video.removeEventListener('timeupdate', onTime);
            video.removeEventListener('pause', onPause);
            video.removeEventListener('seeked', onSeeked);
            video.removeEventListener('ended', onEnd);
        },
    };
}

// ─── Router ───────────────────────────────────────────────────────────────────
async function route() {
    const match = location.hash.match(/^#course-(\d+)(?:\/(lesson|quiz)-(\d+))?/);
    if (match) {
        const key = match[2] ? `${match[2]}:${match[3]}` : null;
        await renderCourse(Number(match[1]), key);
    } else {
        activeTracker?.stop();
        activeTracker = null;
        await renderHome();
    }
    window.scrollTo({ top: 0 });
}

document.addEventListener('click', e => {
    if (e.target.closest('[data-nav=home]')) { e.preventDefault(); location.hash = ''; }
    if (e.target.closest('[data-scroll-quiz]')) { e.preventDefault(); document.getElementById('lessonQuiz')?.scrollIntoView({ behavior: 'smooth' }); }
});
window.addEventListener('hashchange', route);
window.addEventListener('acadexa:library-synced', () => { if (!location.hash) renderHome(); });

document.addEventListener('DOMContentLoaded', async () => {
    try {
        ownerId = Number(await db.get('meta', 'user_id')) || null;
    } catch (e) {
        app.innerHTML = `<div class="alert alert-warning">${t('offline_unsupported')}</div>`;
        return;
    }
    renderConnectivity();
    await route();
    if (navigator.onLine) {
        flush().then(renderConnectivity);
        syncLibrary().catch(() => {});
    }
});
