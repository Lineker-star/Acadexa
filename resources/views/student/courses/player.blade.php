@extends('layouts.app')
@section('title', ($currentLesson ? $currentLesson->title() . ' — ' : ($currentQuiz ? $currentQuiz->title() . ' — ' : '')) . $course->title())
@section('robots', 'noindex, nofollow')

@php
    $lessonDone = $currentLesson && in_array($currentLesson->id, $completedIds, true);
    $kind = $currentLesson?->videoKind();
    $ratio = config('lms.video_completion_ratio');
    $watchedPct = $watchState ? (int) round($watchState->watchedRatio() * 100) : 0;
    $hasLessonCheck = $currentLesson && $lessonQuiz && $currentLesson->type !== 'quiz';
    $requireWatch = $currentLesson && $currentLesson->type === 'video' && in_array($kind, ['upload', 'youtube'], true);
    $stepCount = $steps->count();
    $playerUrl = route('student.courses.player', $enrollment);
    $stepUrl = fn ($s) => $playerUrl . ($s['type'] === 'lesson' ? '?lesson=' . $s['lesson']->id : '?assessment=' . $s['quiz']->id);
    $stepTitle = fn ($s) => $s['type'] === 'lesson' ? $s['lesson']->title() : $s['quiz']->title();
    $nextOpen = $nextStep && ($unlocked[$nextStep['key']] ?? false);
    $config = $currentLesson ? [
        'lessonId'      => $currentLesson->id,
        'type'          => $currentLesson->type,
        'videoKind'     => $currentLesson->type === 'video' ? $kind : null,
        'youtubeId'     => $kind === 'youtube' ? $currentLesson->youtubeId() : null,
        'completeUrl'   => route('student.lesson.complete', $currentLesson),
        'watchUrl'      => route('student.lesson.watch', $currentLesson),
        'completed'     => $lessonDone,
        'hasQuiz'       => $hasLessonCheck,
        'requireWatch'  => $requireWatch,
        'ratio'         => $ratio,
        'watchedPct'    => $watchedPct,
        'resumeAt'      => $watchState && ! $lessonDone && $watchState->position_seconds > 5 ? $watchState->position_seconds : 0,
        'nextUrl'       => $nextStep ? $stepUrl($nextStep) : null,
    ] : ($currentQuiz ? [
        'type'      => 'assessment',
        'completed' => in_array(\App\Services\ProgressService::quizKey($currentQuiz->id), $doneKeys, true),
        'nextUrl'   => $nextStep ? $stepUrl($nextStep) : null,
    ] : null);
@endphp

@push('styles')
<style>
    /* The player uses the whole screen: hide the site chrome. */
    .acadexxa-navbar, footer, .site-footer { display: none !important; }
</style>
@endpush

@section('content')
<div class="player-shell">
    <header class="player-bar">
        <a href="{{ route('student.courses.index') }}" class="btn btn-link p-0" aria-label="{{ __('lms.back') }}"><x-icon name="arrow-left" class="fs-5" /></a>
        <div class="flex-grow-1 min-w-0">
            <div class="course-name">{{ $course->title() }}</div>
            <div class="small" style="color:rgba(255,255,255,.65)">{{ __('learn.step_x_of_y', ['x' => $stepPosition, 'y' => $stepCount]) }}</div>
        </div>
        <div class="d-none d-sm-flex align-items-center gap-2" title="{{ __('lms.progress') }}">
            <div class="player-progress"><div id="progressBar" style="width: {{ $enrollment->progress_percent }}%"></div></div>
            <span class="small"><span id="progressPct">{{ (float) $enrollment->progress_percent }}</span> %</span>
        </div>
        <div class="dropdown">
            <button class="btn btn-link p-0" data-bs-toggle="dropdown" aria-label="{{ __('lms.more') }}"><x-icon name="three-dots-vertical" class="fs-5" /></button>
            <div class="dropdown-menu dropdown-menu-end p-3" style="min-width:290px">
                <div class="small fw-semibold mb-2"><x-icon name="cloud-arrow-down" class="me-1" />{{ __('lms.offline_mode') }}</div>
                <div data-offline-download="{{ $enrollment->id }}" class="mb-2"></div>
                <p class="small text-muted mb-2">{{ __('lms.offline_mode_help') }}</p>
                <hr class="my-2">
                <a class="dropdown-item px-0 small" href="{{ route('student.courses.results', $enrollment) }}"><x-icon name="graph-up-arrow" class="me-2" />{{ __('learn.my_results') }}</a>
                @if($course->books->isNotEmpty())
                    <a class="dropdown-item px-0 small" href="{{ route('student.library.course', $course) }}"><x-icon name="book" class="me-2" />{{ __('learn.course_books') }} ({{ $course->books->count() }})</a>
                @endif
                <a class="dropdown-item px-0 small" href="{{ route('student.course.announcements', $course) }}"><x-icon name="megaphone" class="me-2" />{{ __('lms.announcements') }}</a>
                <a class="dropdown-item px-0 small" href="{{ route('messages.index', ['course' => $course->id]) }}"><x-icon name="envelope" class="me-2" />{{ __('lms.contact_instructor') }}</a>
                <a class="dropdown-item px-0 small" href="{{ route('courses.show', $course->slug) }}"><x-icon name="info-circle" class="me-2" />{{ __('lms.course_page') }}</a>
            </div>
        </div>
        <button class="btn btn-link p-0 d-lg-none" id="outlineToggle" aria-controls="playerOutline" aria-expanded="false" aria-label="{{ __('lms.course_content') }}"><x-icon name="list-ul" class="fs-4" /></button>
    </header>

    <div class="player-body">
        <main class="player-content" id="playerContent">
            @include('partials.flash')

            @if($canDiagnose && ! $doneKeys && ! $finalMode)
                <div class="alert alert-primary d-flex flex-wrap align-items-center gap-3 m-3 mb-0" data-diagnostic-banner="{{ $course->id }}">
                    <x-icon name="speedometer2" class="fs-3" />
                    <div class="flex-grow-1">
                        <strong>{{ __('learn.diagnostic_title') }}</strong>
                        <div class="small">{{ __('learn.diagnostic_banner') }}</div>
                    </div>
                    <a href="{{ $playerUrl }}?assessment={{ $final->id }}&mode=diagnostic" class="btn btn-primary btn-sm">{{ __('learn.diagnostic_start') }}</a>
                    <button type="button" class="btn btn-link btn-sm" data-dismiss-diagnostic>{{ __('learn.later') }}</button>
                </div>
            @endif

            @if(! $currentLesson && ! $currentQuiz)
                <div class="text-center py-5 px-3">
                    <x-icon name="hourglass" style="font-size:3rem;color:var(--bs-primary);opacity:.4" />
                    <h4 class="mt-3">{{ __('lms.course_empty') }}</h4>
                </div>
            @elseif($currentQuiz)
                {{-- ─── Module exercise / final evaluation ─── --}}
                <div class="lesson-body" data-protected>
                    @include('student.courses.partials.assessment', [
                        'quiz' => $currentQuiz,
                        'state' => $quizState,
                        'mode' => $finalMode ?? 'standard',
                        'done' => in_array(\App\Services\ProgressService::quizKey($currentQuiz->id), $doneKeys, true),
                    ])
                    @include('student.courses.partials.step-nav')
                </div>
            @else
                {{-- ─── Video stage ─── --}}
                @if($currentLesson->type === 'video' && $currentLesson->hasVideo())
                    <div class="video-stage" data-protected>
                        <button type="button" class="wm-fullscreen" data-protected-fullscreen aria-label="{{ __('learn.fullscreen') }}"><x-icon name="arrows-fullscreen" /></button>
                        <div class="ratio ratio-16x9">
                            @if($kind === 'upload')
                                <video id="lessonVideo" controls playsinline preload="metadata" controlsList="nodownload nofullscreen noremoteplayback" disablePictureInPicture oncontextmenu="return false;"
                                       src="{{ route('media.lesson.video', $currentLesson) }}"></video>
                            @elseif($kind === 'youtube')
                                <div id="ytPlayer"></div>
                            @elseif($kind === 'vimeo')
                                <iframe src="https://player.vimeo.com/video/{{ $currentLesson->vimeoId() }}?title=0&byline=0&portrait=0&dnt=1"
                                        title="{{ $currentLesson->title() }}" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen></iframe>
                            @else
                                <video id="lessonVideo" controls playsinline preload="metadata" controlsList="nodownload nofullscreen noremoteplayback" disablePictureInPicture src="{{ $currentLesson->video_url }}"></video>
                            @endif
                        </div>
                    </div>
                @elseif($currentLesson->type === 'video')
                    <div class="alert alert-warning m-3">{{ __('lms.video_unavailable') }}</div>
                @endif

                <div class="lesson-body" data-protected>
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                        <div>
                            <div class="small text-muted">{{ $currentLesson->module->title() }}</div>
                            <h1 class="h4 fw-bold mb-1">{{ $currentLesson->title() }}</h1>
                            <div class="small text-muted">
                                <x-icon :name="$currentLesson->icon()" class="me-1" />{{ __('lms.type_' . $currentLesson->type) }}
                                @if($currentLesson->duration_minutes) · {{ __('lms.minutes_short', ['count' => $currentLesson->duration_minutes]) }} @endif
                            </div>
                        </div>

                        {{-- Completion control --}}
                        <div class="text-end" id="completionBox">
                            @if($lessonDone)
                                <span class="btn btn-success btn-sm disabled"><x-icon name="check2-circle" class="me-1" />{{ __('lms.completed') }}</span>
                            @elseif($hasLessonCheck)
                                <a href="#lessonQuiz" class="btn btn-outline-primary btn-sm"><x-icon name="patch-question" class="me-1" />{{ __('learn.take_lesson_quiz') }}</a>
                                <div class="watch-hint mt-1">{{ __('learn.lesson_quiz_rule', ['score' => $lessonQuiz->effectivePassingScore()]) }}</div>
                            @elseif($currentLesson->isManuallyCompletable())
                                <button type="button" id="markCompleteBtn" class="btn btn-primary btn-sm" @disabled($requireWatch && $watchedPct < $ratio * 100)>
                                    <x-icon name="check2" class="me-1" />{{ __('lms.mark_complete') }}
                                </button>
                                @if($requireWatch)
                                    <div class="watch-hint mt-1" id="watchHint">{{ __('lms.watched_percent', ['percent' => $watchedPct]) }} — {{ __('lms.auto_complete_hint') }}</div>
                                @endif
                            @elseif($currentLesson->type === 'quiz')
                                <span class="small text-muted"><x-icon name="info-circle" class="me-1" />{{ __('lms.quiz_completes_lesson') }}</span>
                            @elseif($currentLesson->type === 'assignment')
                                <span class="small text-muted"><x-icon name="info-circle" class="me-1" />{{ __('lms.assignment_completes_lesson') }}</span>
                            @endif
                        </div>
                    </div>

                    {{-- Tabs --}}
                    <ul class="nav nav-underline mb-3" role="tablist">
                        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#pane-lesson" type="button">{{ __('lms.tab_lesson') }}</button></li>
                        @if($currentLesson->resources->isNotEmpty())
                            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#pane-resources" type="button">{{ __('lms.resources') }} ({{ $currentLesson->resources->count() }})</button></li>
                        @endif
                        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#pane-qa" type="button" id="qaTab">{{ __('lms.qa') }} ({{ $currentLesson->comments->count() }})</button></li>
                    </ul>

                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="pane-lesson">
                            @php $body = $currentLesson->renderedBody(); @endphp
                            @if(trim(strip_tags($body, '<img><iframe>')) !== '')
                                <div class="lesson-content mb-4">{!! $body !!}</div>
                            @endif

                            @if($currentLesson->type === 'quiz')
                                @if($lessonQuiz)
                                    @include('student.courses.partials.quiz', ['quiz' => $lessonQuiz, 'state' => $quizState, 'mode' => 'standard'])
                                @else
                                    <div class="alert alert-info">{{ __('lms.quiz_not_ready') }}</div>
                                @endif
                            @endif

                            @if($currentLesson->type === 'assignment')
                                @include('student.courses.partials.assignment', ['lesson' => $currentLesson, 'submission' => $submission])
                            @endif

                            @if($hasLessonCheck)
                                <section id="lessonQuiz" class="mt-4">
                                    <h2 class="h5 fw-bold mb-1"><x-icon name="patch-question" class="text-primary me-1" />{{ __('learn.check_your_knowledge') }}</h2>
                                    <p class="small text-muted">{{ __('learn.lesson_quiz_rule', ['score' => $lessonQuiz->effectivePassingScore()]) }}</p>
                                    @include('student.courses.partials.quiz', ['quiz' => $lessonQuiz, 'state' => $quizState, 'mode' => 'standard'])
                                </section>
                            @endif
                        </div>

                        @if($currentLesson->resources->isNotEmpty())
                        <div class="tab-pane fade" id="pane-resources">
                            @foreach($currentLesson->resources as $resource)
                                @include('student.partials.resource-link', ['resource' => $resource])
                            @endforeach
                        </div>
                        @endif

                        <div class="tab-pane fade" id="pane-qa">
                            <div id="comments"></div>
                            <form method="POST" action="{{ route('student.lesson.comment', $currentLesson) }}" class="mb-4">
                                @csrf
                                <label class="form-label small fw-semibold" for="commentBox">{{ __('lms.ask_question') }}</label>
                                <textarea name="comment" id="commentBox" class="form-control mb-2" rows="3" maxlength="2000" required placeholder="{{ __('lms.ask_question_placeholder') }}"></textarea>
                                <button class="btn btn-primary btn-sm">{{ __('lms.post') }}</button>
                            </form>
                            @forelse($currentLesson->comments as $comment)
                                <div class="d-flex gap-3 mb-4">
                                    <img src="{{ $comment->user->avatarUrl() }}" class="rounded-circle" width="36" height="36" alt="">
                                    <div class="flex-grow-1">
                                        <strong class="small">{{ $comment->user->name }}</strong>
                                        @if($comment->user_id === $course->instructor_id)<span class="badge bg-primary-subtle text-primary ms-1">{{ __('lms.instructor') }}</span>@endif
                                        <span class="text-muted small ms-1">{{ $comment->created_at->diffForHumans() }}</span>
                                        <p class="mb-1" style="white-space:pre-wrap">{{ $comment->comment }}</p>
                                        @foreach($comment->replies as $reply)
                                            <div class="d-flex gap-2 mt-2 ps-3 border-start">
                                                <img src="{{ $reply->user->avatarUrl() }}" class="rounded-circle" width="28" height="28" alt="">
                                                <div>
                                                    <strong class="small">{{ $reply->user->name }}</strong>
                                                    @if($reply->user_id === $course->instructor_id)<span class="badge bg-primary-subtle text-primary ms-1">{{ __('lms.instructor') }}</span>@endif
                                                    <div class="small" style="white-space:pre-wrap">{{ $reply->comment }}</div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @empty
                                <p class="text-muted small">{{ __('lms.no_questions_yet') }}</p>
                            @endforelse
                        </div>
                    </div>

                    @include('student.courses.partials.step-nav')
                </div>
            @endif
        </main>

        {{-- ─── Outline ─── --}}
        <aside class="player-outline" id="playerOutline" aria-label="{{ __('lms.course_content') }}">
            <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                <strong>{{ __('lms.course_content') }}</strong>
                <button class="btn btn-sm btn-light d-lg-none" id="outlineClose" aria-label="{{ __('lms.close') }}"><x-icon name="x-lg" /></button>
            </div>
            @php $currentKey = $currentLesson ? \App\Services\ProgressService::lessonKey($currentLesson->id) : ($currentQuiz ? \App\Services\ProgressService::quizKey($currentQuiz->id) : null); @endphp
            @foreach($course->modules as $module)
                @php
                    $moduleSteps = $steps->filter(fn ($s) => $s['type'] !== 'final' && $s['module']->id === $module->id);
                    $moduleDone = $moduleSteps->filter(fn ($s) => in_array($s['key'], $doneKeys, true))->count();
                    $containsCurrent = $moduleSteps->contains('key', $currentKey);
                @endphp
                <div class="outline-module">
                    <button type="button" data-bs-toggle="collapse" data-bs-target="#om-{{ $module->id }}" aria-expanded="{{ $containsCurrent ? 'true' : 'false' }}">
                        <span class="flex-grow-1">
                            {{ __('lms.module') }} {{ $loop->iteration }} : {{ $module->title() }}
                            <small>{{ $moduleDone }}/{{ $moduleSteps->count() }} · {{ $module->hoursLabel() ?? '' }}</small>
                        </span>
                        <x-icon name="chevron-down" />
                    </button>
                    <div class="collapse {{ $containsCurrent ? 'show' : '' }}" id="om-{{ $module->id }}">
                        @foreach($moduleSteps as $s)
                            @include('student.courses.partials.outline-step', ['s' => $s])
                        @endforeach
                    </div>
                </div>
            @endforeach
            @if($finalStep = $steps->firstWhere('type', 'final'))
                <div class="outline-module">
                    @include('student.courses.partials.outline-step', ['s' => $finalStep])
                </div>
            @endif
        </aside>
    </div>
</div>

@if($config)
<script type="application/json" id="playerConfig">@json($config)</script>
@endif
@endsection

@push('scripts')
@vite(['resources/js/player.js'])
@endpush
