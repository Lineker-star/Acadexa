// Student course player: watch-time tracking (our <video> and the YouTube IFrame API),
// resume position, completion rules, auto-advance, quizzes. Actions made while the
// connection drops are queued in the offline outbox and replayed later.
import { icon } from './icons';
import { enqueue } from './offline/outbox';

const i18n = window.ACADEXA_I18N || {};
const t = (key, vars = {}) => Object.entries(vars).reduce((s, [k, v]) => s.replaceAll(`:${k}`, v), i18n[key] || key);
const csrf = () => document.querySelector('meta[name="csrf-token"]').content;
const toast = (msg, type = 'info') => window.showToast?.(msg, type);
const userId = Number(document.body.dataset.userId) || null;

const configEl = document.getElementById('playerConfig');
const config = configEl ? JSON.parse(configEl.textContent) : null;

async function post(url, body) {
    const res = await fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf() },
        body: JSON.stringify(body || {}),
    });
    const data = await res.json().catch(() => ({}));
    return { ok: res.ok, status: res.status, data };
}

const isNetworkError = err => err instanceof TypeError; // fetch() rejects with TypeError when offline

// ─── Outline drawer (mobile) ──────────────────────────────────────────────────
function initOutline() {
    const outline = document.getElementById('playerOutline');
    const toggle = document.getElementById('outlineToggle');
    const close = document.getElementById('outlineClose');
    if (!outline) return;
    const set = open => {
        outline.classList.toggle('open', open);
        toggle?.setAttribute('aria-expanded', open ? 'true' : 'false');
    };
    toggle?.addEventListener('click', () => set(!outline.classList.contains('open')));
    close?.addEventListener('click', () => set(false));
    document.addEventListener('keydown', e => { if (e.key === 'Escape') set(false); });
    outline.querySelector('.outline-lesson.active')?.scrollIntoView({ block: 'center' });
}

// ─── Progress UI ──────────────────────────────────────────────────────────────
function applyProgress(result) {
    if (!result) return;
    const bar = document.getElementById('progressBar');
    const pct = document.getElementById('progressPct');
    if (bar) bar.style.width = `${result.progress}%`;
    if (pct) pct.textContent = result.progress;

    const box = document.getElementById('completionBox');
    if (box) box.innerHTML = `<span class="btn btn-success btn-sm disabled">${icon('check2-circle', 'me-1')}${t('completed')}</span>`;

    const current = document.querySelector('.outline-lesson.active .state');
    if (current) current.innerHTML = icon('check-circle-fill', 'text-success');
    document.getElementById('nextLessonBtn')?.classList.remove('disabled');

    if (result.certificate_issued) {
        toast(t('certificate_ready'), 'success');
        setTimeout(() => { window.location.href = '/my-certificates'; }, 2500);
    }
}

// ─── Completion ───────────────────────────────────────────────────────────────
let completing = false;
async function complete({ auto = false } = {}) {
    if (!config || config.completed || completing) return false;
    completing = true;
    const btn = document.getElementById('markCompleteBtn');
    if (btn) btn.disabled = true;
    try {
        const { ok, data } = await post(config.completeUrl);
        if (!ok) {
            if (!auto) toast(data.message || t('error'), 'warning');
            if (btn) btn.disabled = false;
            return false;
        }
        config.completed = true;
        applyProgress(data);
        if (!auto) toast(data.message, 'success');
        return true;
    } catch (err) {
        if (isNetworkError(err)) {
            await enqueue({ type: 'complete', lesson_id: config.lessonId, user_id: userId });
            config.completed = true;
            applyProgress({ progress: document.getElementById('progressPct')?.textContent || 0 });
            toast(t('saved_offline'), 'info');
            return true;
        }
        if (btn) btn.disabled = false;
        return false;
    } finally {
        completing = false;
    }
}

function initCompleteButton() {
    document.getElementById('markCompleteBtn')?.addEventListener('click', () => complete());
}

// ─── Watch tracking ───────────────────────────────────────────────────────────
function createTracker() {
    let watched = config.watchedPct || 0; // percent, as reported by the server
    let pendingDelta = 0;                // seconds actually played since last report
    let lastTime = null;
    let lastReport = Date.now();
    let duration = 0;
    let position = 0;

    const hint = document.getElementById('watchHint');
    const btn = document.getElementById('markCompleteBtn');

    const updateHint = () => {
        if (hint) hint.textContent = `${t('watched_percent', { percent: Math.min(100, Math.round(watched)) })} — ${t('auto_complete_hint')}`;
        if (btn && watched >= config.ratio * 100 && !config.completed) btn.disabled = false;
    };

    async function report(force = false) {
        if (!duration) return;
        if (!force && Date.now() - lastReport < 15000) return;
        const delta = Math.round(pendingDelta);
        if (!delta && !force) return;
        pendingDelta -= delta;
        lastReport = Date.now();
        const payload = { position: Math.floor(position), duration: Math.round(duration), watched_delta: delta };
        try {
            const { ok, data } = await post(config.watchUrl, payload);
            if (ok) { watched = data.watched_percent; updateHint(); }
        } catch (err) {
            if (isNetworkError(err)) {
                await enqueue({ type: 'watch', lesson_id: config.lessonId, user_id: userId, ...payload });
                watched = Math.min(100, watched + (delta / duration) * 100);
                updateHint();
            }
        }
    }

    // Called ~4×/s while playing. Only small forward steps count as watching:
    // seeking ahead or scrubbing produces big jumps that are ignored.
    function tick(currentTime, totalDuration, playbackRate = 1) {
        duration = totalDuration || duration;
        position = currentTime;
        if (lastTime !== null) {
            const step = currentTime - lastTime;
            if (step > 0 && step <= 2.5 * Math.max(1, playbackRate)) pendingDelta += step;
        }
        lastTime = currentTime;
        report();
    }

    return {
        tick,
        seeked(time) { lastTime = time; },
        pause() { lastTime = null; report(true); },
        async ended() {
            lastTime = null;
            await report(true);
            if (config.hasQuiz && !config.completed) {
                // The lesson is validated by its quiz: take the student there.
                toast(t('now_take_quiz'), 'info');
                document.getElementById('lessonQuiz')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                return;
            }
            const done = await complete({ auto: true });
            if (done || config.completed) offerNext();
        },
        flush() { report(true); },
    };
}

function initHtml5Video(tracker) {
    const video = document.getElementById('lessonVideo');
    if (!video) return;

    // Remember the preferred speed across lessons.
    try {
        const rate = parseFloat(localStorage.getItem('acadexa.rate'));
        if (rate) video.playbackRate = rate;
    } catch (e) { /* storage blocked */ }
    video.addEventListener('ratechange', () => { try { localStorage.setItem('acadexa.rate', video.playbackRate); } catch (e) { /* ignore */ } });

    video.addEventListener('loadedmetadata', () => {
        if (config.resumeAt && config.resumeAt < video.duration - 10) {
            video.currentTime = config.resumeAt;
            toast(t('resumed_at', { time: formatClock(config.resumeAt) }), 'info');
        }
    }, { once: true });
    video.addEventListener('timeupdate', () => { if (!video.paused && !video.seeking) tracker.tick(video.currentTime, video.duration, video.playbackRate); });
    video.addEventListener('seeked', () => tracker.seeked(video.currentTime));
    video.addEventListener('pause', () => tracker.pause());
    video.addEventListener('ended', () => tracker.ended());

    // Keyboard shortcuts like the big platforms: space, left/right arrows 5 s, F fullscreen.
    document.addEventListener('keydown', e => {
        if (['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName) || document.activeElement?.isContentEditable) return;
        if (e.key === ' ' || e.key === 'k') { e.preventDefault(); video.paused ? video.play() : video.pause(); }
        if (e.key === 'ArrowRight') video.currentTime = Math.min(video.duration, video.currentTime + 5);
        if (e.key === 'ArrowLeft') video.currentTime = Math.max(0, video.currentTime - 5);
        if (e.key === 'f') video.requestFullscreen?.();
    });
}

function initYoutube(tracker) {
    const holder = document.getElementById('ytPlayer');
    if (!holder || !config.youtubeId) return;

    let timer = null;
    const start = () => {
        const player = new window.YT.Player('ytPlayer', {
            videoId: config.youtubeId,
            host: 'https://www.youtube-nocookie.com',
            width: '100%',
            height: '100%',
            playerVars: {
                // fs: 0 — fullscreen goes through our button so that the watermark stays visible.
                rel: 0, modestbranding: 1, playsinline: 1, iv_load_policy: 3, fs: 0,
                start: config.resumeAt ? Math.floor(config.resumeAt) : 0,
                hl: document.documentElement.lang || 'fr',
                origin: window.location.origin,
            },
            events: {
                onStateChange: event => {
                    const YTS = window.YT.PlayerState;
                    clearInterval(timer);
                    if (event.data === YTS.PLAYING) {
                        timer = setInterval(() => {
                            tracker.tick(player.getCurrentTime(), player.getDuration(), player.getPlaybackRate());
                        }, 500);
                    } else if (event.data === YTS.PAUSED || event.data === YTS.BUFFERING) {
                        tracker.pause();
                    } else if (event.data === YTS.ENDED) {
                        tracker.ended();
                    }
                },
            },
        });
    };

    if (window.YT?.Player) {
        start();
    } else {
        const previous = window.onYouTubeIframeAPIReady;
        window.onYouTubeIframeAPIReady = () => { previous?.(); start(); };
        const tag = document.createElement('script');
        tag.src = 'https://www.youtube.com/iframe_api';
        tag.onerror = () => {
            holder.innerHTML = `<div class="d-flex h-100 align-items-center justify-content-center text-white p-3 text-center">${t('youtube_unreachable')}</div>`;
        };
        document.head.appendChild(tag);
    }
}

// After a video ends: "Next lesson in 5 s" with a cancel button.
function offerNext() {
    if (!config.nextUrl || document.getElementById('autoNext')) return;
    let seconds = 5;
    const box = document.createElement('div');
    box.id = 'autoNext';
    box.className = 'alert alert-primary d-flex align-items-center justify-content-between gap-2 mt-3';
    box.innerHTML = `<span>${t('next_in', { seconds: `<strong>${seconds}</strong>` })}</span>
        <span class="d-flex gap-2"><a class="btn btn-sm btn-primary" href="${config.nextUrl}">${t('go_now')}</a>
        <button type="button" class="btn btn-sm btn-light">${t('cancel')}</button></span>`;
    document.querySelector('.lesson-body')?.prepend(box);
    const timer = setInterval(() => {
        seconds--;
        box.querySelector('strong').textContent = seconds;
        if (seconds <= 0) { clearInterval(timer); window.location.href = config.nextUrl; }
    }, 1000);
    box.querySelector('button').addEventListener('click', () => { clearInterval(timer); box.remove(); });
}

function formatClock(s) {
    s = Math.floor(s);
    const h = Math.floor(s / 3600);
    const m = Math.floor((s % 3600) / 60);
    const sec = String(s % 60).padStart(2, '0');
    return h ? `${h}:${String(m).padStart(2, '0')}:${sec}` : `${m}:${sec}`;
}

// ─── Quiz ─────────────────────────────────────────────────────────────────────
function initQuiz() {
    const container = document.getElementById('quizContainer');
    const form = document.getElementById('quizForm');
    if (!container || !form) return;

    const timeLimit = Number(container.dataset.timeLimit) || 0;
    const mode = container.dataset.mode || 'standard';
    const result = document.getElementById('quizResult');
    const submitBtn = document.getElementById('submitQuizBtn');
    let countdown = null;
    let submitted = false;
    let timeUp = false;

    // Visual state of the option cards.
    form.addEventListener('change', e => {
        if (!e.target.matches('input')) return;
        const fieldset = e.target.closest('fieldset');
        fieldset.querySelectorAll('.quiz-option').forEach(label => {
            label.classList.toggle('selected', label.querySelector('input').checked);
        });
    });

    const collect = () => {
        const answers = {};
        form.querySelectorAll('.quiz-question').forEach(fs => {
            answers[fs.dataset.question] = [...fs.querySelectorAll('input:checked')].map(i => Number(i.value));
        });
        return answers;
    };

    const startBtn = document.getElementById('startQuizBtn');
    startBtn?.addEventListener('click', async () => {
        startBtn.disabled = true;
        try {
            const { ok, data } = await post(container.dataset.startUrl, { mode });
            if (!ok) { toast(data.message, 'warning'); startBtn.disabled = false; return; }
            document.getElementById('quizIntro').hidden = true;
            form.hidden = false;
            runTimer(data.remaining_seconds ?? timeLimit * 60);
        } catch (err) {
            toast(t('timed_quiz_needs_network'), 'warning');
            startBtn.disabled = false;
        }
    });

    function runTimer(remaining) {
        const el = document.getElementById('quizTimer');
        const draw = () => {
            el.innerHTML = icon('stopwatch', 'me-1') + formatClock(Math.max(0, remaining));
            el.classList.toggle('low', remaining <= 60);
        };
        draw();
        countdown = setInterval(() => {
            remaining--;
            draw();
            if (remaining <= 0) {
                clearInterval(countdown);
                timeUp = true;
                toast(t('time_up'), 'warning');
                form.requestSubmit();
            }
        }, 1000);
    }

    form.addEventListener('submit', async e => {
        e.preventDefault();
        if (submitted) return;
        const answers = collect();
        const unanswered = Object.values(answers).filter(a => !a.length).length;
        if (unanswered && !timeUp && !form.dataset.confirmed) {
            form.dataset.confirmed = '1';
            result.innerHTML = `<span class="text-warning-emphasis">${t('unanswered_confirm', { count: unanswered })}</span>`;
            return;
        }

        submitted = true;
        clearInterval(countdown);
        submitBtn.disabled = true;
        submitBtn.innerHTML = `<span class="spinner-border spinner-border-sm me-1"></span>${t('sending')}`;

        try {
            const { ok, data } = await post(container.dataset.attemptUrl, { answers, mode });
            if (!ok) {
                result.innerHTML = `<span class="text-danger">${data.message || t('error')}</span>`;
                submitted = false;
                submitBtn.disabled = false;
                submitBtn.textContent = t('submit_answers');
                return;
            }
            showResult(data);
        } catch (err) {
            if (isNetworkError(err) && !timeLimit && mode === 'standard') {
                await enqueue({ type: 'quiz', quiz_id: Number(container.dataset.quizId), answers, user_id: userId });
                result.innerHTML = `<span class="text-primary">${icon('cloud-slash', 'me-1')}${t('quiz_saved_offline')}</span>`;
                form.querySelectorAll('input').forEach(i => { i.disabled = true; });
                submitBtn.hidden = true;
            } else {
                result.innerHTML = `<span class="text-danger">${t('error')}</span>`;
                submitted = false;
                submitBtn.disabled = false;
            }
        }
    });

    function showResult(data) {
        form.querySelectorAll('input').forEach(i => { i.disabled = true; });
        submitBtn.hidden = true;

        Object.entries(data.review || {}).forEach(([questionId, review]) => {
            const fs = form.querySelector(`[data-question="${questionId}"]`);
            if (!fs) return;
            fs.classList.add('quiz-question-review', review.correct ? 'correct' : 'wrong');
            if (review.answer) {
                fs.querySelectorAll('.quiz-option').forEach(label => {
                    const id = Number(label.dataset.option);
                    const checked = label.querySelector('input').checked;
                    if (review.answer.includes(id)) label.classList.add('correct');
                    else if (checked) label.classList.add('wrong');
                });
            }
            const note = fs.querySelector('.review-note');
            note.hidden = false;
            note.innerHTML = (review.correct
                ? `<span class="text-success">${icon('check-circle', 'me-1')}${t('correct')}</span>`
                : `<span class="text-danger">${icon('x-circle', 'me-1')}${t('incorrect')}</span>`)
                + (review.explanation ? `<div class="quiz-explanation mt-1">${icon('lightbulb', 'me-1')}${escapeHtml(review.explanation)}</div>` : '');
        });

        const retry = !data.passed && data.attempts_left !== 0 && mode === 'standard'
            ? ` <button type="button" class="btn btn-sm btn-outline-primary ms-2" onclick="location.reload()">${t('retry')}</button>` : '';
        result.innerHTML = `<span class="${data.passed ? 'text-success' : 'text-danger'}">
            ${t('score_line', { score: data.score, correct: data.correct, total: data.total })} — ${data.message}</span>${retry}`;
        toast(data.message, data.passed || mode !== 'standard' ? 'success' : 'warning');
        if (data.knowledge) showKnowledge(data.knowledge);

        if (data.passed && data.progress) {
            config && (config.completed = true);
            applyProgress(data.progress);
            if (!data.progress.certificate_issued) offerNext();
        }
        result.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}

// Final evaluation: starting level, current level and trend.
function showKnowledge(k) {
    const box = document.getElementById('knowledgeResult');
    if (!box) return;
    const trend = {
        progress: ['graph-up-arrow', 'text-success'], regression: ['graph-down-arrow', 'text-danger'],
        stable: ['arrow-right', 'text-secondary'], single: ['dot', 'text-secondary'], none: ['dash', 'text-muted'],
    }[k.trend] || ['dot', 'text-secondary'];
    const pts = v => (v === null || v === undefined ? '—' : `${v > 0 ? '+' : ''}${v} pts`);
    box.hidden = false;
    box.innerHTML = `<div class="border rounded-3 bg-white p-3 small">
        <div class="fw-semibold mb-1">${icon('graph-up-arrow', 'me-1')}${t('knowledge_evolution')}</div>
        <div>${t('current_level')} : <strong>${k.current ?? '—'} %</strong>${k.level ? ` (${escapeHtml(k.level)})` : ''}</div>
        <div>${t('knowledge_gain')} : <strong>${pts(k.gain)}</strong></div>
        <div class="${trend[1]}">${icon(trend[0], 'me-1')}${t('trend_' + k.trend)} ${k.delta !== null && k.delta !== undefined ? '(' + pts(k.delta) + ')' : ''}</div>
    </div>`;
}

function escapeHtml(str) {
    return String(str ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}

// Placement-test banner: "Later" hides it for this course on this device.
function initDiagnosticBanner() {
    const banner = document.querySelector('[data-diagnostic-banner]');
    if (!banner) return;
    const key = 'acadexa.diag.' + banner.dataset.diagnosticBanner;
    try { if (localStorage.getItem(key)) { banner.remove(); return; } } catch (e) { /* storage blocked */ }
    banner.querySelector('[data-dismiss-diagnostic]')?.addEventListener('click', () => {
        try { localStorage.setItem(key, '1'); } catch (e) { /* ignore */ }
        banner.remove();
    });
}

// ─── Boot ─────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    initOutline();
    initDiagnosticBanner();
    if (!config) return;
    initCompleteButton();
    initQuiz();

    // Open the Q&A tab directly when coming from a notification link (#comments).
    if (location.hash === '#comments') document.getElementById('qaTab')?.click();

    if (config.type === 'video' && config.requireWatch) {
        const tracker = createTracker();
        initHtml5Video(tracker);
        initYoutube(tracker);
        document.addEventListener('visibilitychange', () => { if (document.hidden) tracker.flush(); });
        window.addEventListener('pagehide', () => tracker.flush());
    } else if (config.type === 'video') {
        // Vimeo / external link: no reliable watch tracking, manual completion only.
        document.getElementById('lessonVideo')?.addEventListener('ended', () => complete({ auto: true }).then(offerNext));
    }
});
