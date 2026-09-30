@extends('layouts.instructor')
@section('title', $course->title())
@section('page-title', __('lms.edit_course'))

@php
    $statusClass = match($course->status) {
        'published' => 'bg-success', 'pending' => 'bg-warning text-dark', 'rejected' => 'bg-danger', default => 'bg-secondary',
    };
    $modulesHours = $course->modulesHoursTotal();
    $courseHours  = (float) $course->duration_hours;
    $hoursPct     = $courseHours > 0 ? min(100, round($modulesHours / $courseHours * 100)) : 0;
    $locked       = $course->status === 'pending' && ! auth()->user()->isAdmin();
    $typeLabels   = ['video' => __('lms.type_video'), 'text' => __('lms.type_text'), 'quiz' => __('lms.type_quiz'), 'assignment' => __('lms.type_assignment')];
@endphp

@section('content')
{{-- Header --}}
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="badge {{ $statusClass }}">{{ __('lms.status_' . $course->status) }}</span>
            <span class="text-muted small"><x-icon name="translate" class="me-1" />{{ strtoupper($course->language) }}</span>
            <span class="text-muted small"><x-icon name="clock" class="me-1" />{{ $course->hoursLabel() }}</span>
        </div>
        <h4 class="fw-bold mb-0">{{ $course->title() }}</h4>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('instructor.students.index', $course) }}" class="btn btn-outline-primary btn-sm"><x-icon name="people" class="me-1" />{{ __('lms.students') }} ({{ $course->enrollments_count }})</a>
        <a href="{{ route('instructor.students.knowledge', $course) }}" class="btn btn-outline-primary btn-sm"><x-icon name="graph-up-arrow" class="me-1" />{{ __('learn.knowledge_tracking') }}</a>
        <a href="{{ route('instructor.announcements.index', $course) }}" class="btn btn-outline-primary btn-sm"><x-icon name="megaphone" class="me-1" />{{ __('lms.announcements') }}</a>
        @if($course->status === 'published')
            <a href="{{ route('courses.show', $course->slug) }}" target="_blank" class="btn btn-outline-secondary btn-sm"><x-icon name="box-arrow-up-right" class="me-1" />{{ __('lms.view_public_page') }}</a>
        @endif
    </div>
</div>

@if($course->admin_feedback && in_array($course->status, ['rejected', 'unpublished']))
    <div class="alert alert-danger"><strong><x-icon name="chat-left-text" class="me-1" />{{ __('lms.admin_feedback') }} :</strong> {{ $course->admin_feedback }}</div>
@endif
@if($locked)
    <div class="alert alert-warning"><x-icon name="lock" class="me-1" />{{ __('lms.course_locked_pending') }}</div>
@endif
@if($errors->has('submit'))
    <div class="alert alert-danger">{{ $errors->first('submit') }}</div>
@endif

<div class="row g-4">
    <div class="col-xl-8">
        <ul class="nav nav-tabs mb-3" role="tablist">
            <li class="nav-item"><button class="nav-link {{ $tab === 'info' ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#tab-info" type="button">
                <x-icon name="info-circle" class="me-1" />{{ __('lms.tab_info') }}</button></li>
            <li class="nav-item"><button class="nav-link {{ $tab === 'curriculum' ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#tab-curriculum" type="button">
                <x-icon name="list-nested" class="me-1" />{{ __('lms.tab_curriculum') }}</button></li>
            <li class="nav-item"><button class="nav-link {{ $tab === 'assessments' ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#tab-assessments" type="button">
                <x-icon name="patch-check" class="me-1" />{{ __('learn.tab_assessments') }}</button></li>
            <li class="nav-item"><button class="nav-link {{ $tab === 'books' ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#tab-books" type="button">
                <x-icon name="book" class="me-1" />{{ __('learn.tab_books') }} ({{ $course->books->count() }})</button></li>
        </ul>

        <div class="tab-content">
            {{-- ─── Info ─────────────────────────────────────────────── --}}
            <div class="tab-pane fade {{ $tab === 'info' ? 'show active' : '' }}" id="tab-info">
                <form method="POST" action="{{ route('instructor.courses.update', $course) }}" enctype="multipart/form-data" class="bg-white rounded-xl shadow-brand p-4">
                    @csrf @method('PUT')
                    <fieldset @disabled($locked)>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">{{ __('lms.course_language') }} *</label>
                            <select name="language" class="form-select">
                                @foreach(\App\Http\Controllers\Instructor\CourseController::CONTENT_LOCALES as $loc)
                                <option value="{{ $loc }}" @selected($course->language === $loc)>{{ config('app.locale_names')[$loc] }}</option>
                            @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">{{ __('lms.course_hours') }} *</label>
                            <div class="input-group">
                                <input type="number" name="duration_hours" class="form-control @error('duration_hours') is-invalid @enderror"
                                       min="0.5" step="0.5" required value="{{ old('duration_hours', $course->duration_hours) }}">
                                <span class="input-group-text">h</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">{{ __('lms.level') }}</label>
                            <select name="level" class="form-select">
                                @foreach(['beginner', 'intermediate', 'advanced'] as $lvl)
                                    <option value="{{ $lvl }}" @selected($course->level === $lvl)>{{ __('messages.' . $lvl) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">{{ __('lms.category') }} *</label>
                            <select name="category_id" class="form-select" required>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" @selected($course->category_id == $cat->id)>{{ $cat->icon ?? '' }} {{ $cat->name() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">{{ __('lms.price') }}</label>
                            <div class="input-group">
                                <input type="number" name="price" class="form-control" min="0" step="1" value="{{ old('price', (float) $course->price) }}">
                                <span class="input-group-text">FCFA</span>
                            </div>
                            <div class="form-text">{{ __('lms.price_help') }}</div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold" for="intro_youtube_url"><x-icon name="youtube" class="me-1 text-danger" />{{ __('learn.intro_video') }}</label>
                        <input type="text" name="intro_youtube_url" id="intro_youtube_url" class="form-control @error('intro_youtube_url') is-invalid @enderror" data-youtube-input
                               value="{{ old('intro_youtube_url', $course->intro_youtube_id ? 'https://www.youtube.com/watch?v=' . $course->intro_youtube_id : '') }}"
                               placeholder="{{ __('https://www.youtube.com/watch?v=…') }}">
                        @error('intro_youtube_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">{{ __('learn.intro_video_help') }}</div>
                        <div class="mt-2" style="max-width:480px" data-youtube-preview></div>
                    </div>

                    <div class="form-check form-switch mb-4">
                        <input class="form-check-input" type="checkbox" role="switch" name="is_sequential" value="1" id="isSequential" @checked($course->is_sequential)>
                        <label class="form-check-label" for="isSequential"><strong>{{ __('lms.sequential') }}</strong> — <span class="text-muted">{{ __('lms.sequential_help') }}</span></label>
                    </div>

                    {{-- Content in each language --}}
                    <ul class="nav nav-pills lang-tabs mb-3" role="tablist">
                        @foreach($locales as $loc)
                            <li class="nav-item"><button type="button" class="nav-link {{ $loop->first ? 'active' : '' }}" data-bs-toggle="pill" data-bs-target="#course-{{ $loc }}">
                                {{ config('app.locale_names')[$loc] ?? strtoupper($loc) }}
                                @if($course->translationExact($loc))<x-icon name="check-circle-fill" class="text-success ms-1" />@endif
                            </button></li>
                        @endforeach
                    </ul>
                    <div class="tab-content">
                        @foreach($locales as $loc)
                            @php $tr = $course->translationExact($loc); @endphp
                            <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="course-{{ $loc }}">
                                @if($loc !== $course->language)
                                    <p class="small text-muted"><x-icon name="info-circle" class="me-1" />{{ __('lms.optional_translation') }}</p>
                                @endif
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">{{ __('lms.title') }} @if($loc === $course->language)*@endif</label>
                                    <input type="text" name="translations[{{ $loc }}][title]" class="form-control @error("translations.$loc.title") is-invalid @enderror"
                                           maxlength="255" value="{{ old("translations.$loc.title", $tr?->title) }}">
                                    @error("translations.$loc.title")<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">{{ __('lms.description') }} @if($loc === $course->language)*@endif</label>
                                    <textarea name="translations[{{ $loc }}][description]" rows="6" class="form-control @error("translations.$loc.description") is-invalid @enderror">{{ old("translations.$loc.description", $tr?->description) }}</textarea>
                                    @error("translations.$loc.description")<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">{{ __('lms.what_you_learn') }}</label>
                                        <textarea name="translations[{{ $loc }}][what_you_learn]" rows="5" class="form-control" placeholder="{{ __('lms.one_per_line') }}">{{ old("translations.$loc.what_you_learn", $tr?->what_you_learn) }}</textarea>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">{{ __('lms.requirements') }}</label>
                                        <textarea name="translations[{{ $loc }}][requirements]" rows="5" class="form-control" placeholder="{{ __('lms.one_per_line') }}">{{ old("translations.$loc.requirements", $tr?->requirements) }}</textarea>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <hr class="my-4">
                    <div class="row g-3 align-items-center">
                        <div class="col-auto">
                            <img src="{{ $course->thumbnailUrl() }}" alt="" class="rounded" style="width:160px;aspect-ratio:16/9;object-fit:cover;background:var(--light);">
                        </div>
                        <div class="col">
                            <label class="form-label fw-semibold">{{ __('lms.thumbnail') }}</label>
                            <input type="file" name="thumbnail" class="form-control @error('thumbnail') is-invalid @enderror" accept="image/jpeg,image/png,image/webp">
                            <div class="form-text">{{ __('lms.thumbnail_help') }}</div>
                            @error('thumbnail')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="text-end mt-4">
                        <button class="btn btn-primary"><x-icon name="check-lg" class="me-1" />{{ __('lms.save') }}</button>
                    </div>
                    </fieldset>
                </form>
            </div>

            {{-- ─── Curriculum ───────────────────────────────────────── --}}
            <div class="tab-pane fade {{ $tab === 'curriculum' ? 'show active' : '' }}" id="tab-curriculum">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <p class="text-muted small mb-0"><x-icon name="arrows-move" class="me-1" />{{ __('lms.drag_help') }}</p>
                    @unless($locked)
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#moduleModal" data-module-mode="create">
                        <x-icon name="plus-lg" class="me-1" />{{ __('lms.add_module') }}
                    </button>
                    @endunless
                </div>

                <div id="curriculum"
                     data-modules-reorder="{{ route('instructor.modules.reorder', $course) }}"
                     data-lessons-reorder="{{ route('instructor.lessons.reorder', $course) }}"
                     data-locked="{{ $locked ? '1' : '0' }}">
                    @forelse($course->modules as $module)
                        @php $lessonMinutes = $module->lessonsMinutes(); @endphp
                        <div class="builder-module" data-module-id="{{ $module->id }}">
                            <div class="builder-module-head">
                                <x-icon name="grip-vertical" class="drag-handle module-handle" title="{{ __('lms.drag') }}" />
                                <div class="flex-grow-1 min-w-0">
                                    <div class="module-index">{{ __('lms.module') }} {{ $loop->iteration }}</div>
                                    <h6 class="text-truncate">{{ $module->title() }}</h6>
                                    <div class="small text-muted">
                                        <x-icon name="clock" class="me-1" />{{ $module->hoursLabel() ?? '—' }}
                                        · {{ trans_choice('lms.lessons_count', $module->lessons->count(), ['count' => $module->lessons->count()]) }}
                                        @if($lessonMinutes) · {{ __('lms.content_minutes', ['minutes' => $lessonMinutes]) }} @endif
                                    </div>
                                </div>
                                @unless($locked)
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light" data-bs-toggle="dropdown" aria-label="{{ __('lms.actions') }}"><x-icon name="three-dots-vertical" /></button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#moduleModal"
                                                    data-module-mode="edit"
                                                    data-action="{{ route('instructor.modules.update', $module) }}"
                                                    data-hours="{{ $module->duration_hours }}"
                                                    data-translations="{{ json_encode($module->translations->mapWithKeys(fn ($t) => [$t->locale => ['title' => $t->title, 'description' => $t->description]])) }}">
                                            <x-icon name="pencil" class="me-2" />{{ __('lms.edit') }}</button></li>
                                        <li>
                                            <form method="POST" action="{{ route('instructor.modules.destroy', $module) }}" data-confirm="{{ __('lms.confirm_delete_module') }}">
                                                @csrf @method('DELETE')
                                                <button class="dropdown-item text-danger"><x-icon name="trash" class="me-2" />{{ __('lms.delete') }}</button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                                @endunless
                            </div>

                            <div class="builder-lessons" data-lessons-of="{{ $module->id }}">
                                @foreach($module->lessons as $lesson)
                                    @php
                                        $warn = ($lesson->type === 'video' && ! $lesson->hasVideo())
                                            || ($lesson->type === 'quiz' && (! $lesson->quiz || $lesson->quiz->questions->isEmpty()));
                                    @endphp
                                    <div class="builder-lesson {{ $warn ? 'is-warning' : '' }}" data-lesson-id="{{ $lesson->id }}">
                                        <x-icon name="grip-vertical" class="drag-handle lesson-handle" />
                                        <span class="lesson-type"><x-icon :name="$lesson->icon()" /></span>
                                        <div class="flex-grow-1 min-w-0">
                                            <div class="text-truncate fw-semibold">{{ $lesson->title() }}</div>
                                            <div class="lesson-meta">
                                                {{ $typeLabels[$lesson->type] }}
                                                @if($lesson->duration_minutes) · {{ __('lms.minutes_short', ['count' => $lesson->duration_minutes]) }} @endif
                                                @if($lesson->type === 'video' && $lesson->hasVideo())
                                                    · <x-icon :name="$lesson->videoKind() === 'youtube' ? 'bi-youtube' : 'bi-hdd'" /> {{ __('lms.source_' . $lesson->videoKind()) }}
                                                @endif
                                                @if($lesson->resources->count()) · <x-icon name="paperclip" />{{ $lesson->resources->count() }} @endif
                                                @if($lesson->is_free_preview) · <span class="text-success">{{ __('lms.free_preview') }}</span> @endif
                                                @if($warn) · <span class="text-warning-emphasis"><x-icon name="exclamation-triangle" /> {{ __('lms.lesson_incomplete') }}</span> @endif
                                            </div>
                                        </div>
                                        @if($lesson->type !== 'assignment')
                                            @php $qn = $lesson->quiz ? $lesson->quiz->questions->count() : 0; $qmin = config('lms.assessment.min_questions.lesson'); @endphp
                                            <a href="{{ route('instructor.assessments.lesson', $lesson) }}" class="badge text-decoration-none {{ $qn >= $qmin ? 'bg-success' : 'bg-warning text-dark' }}"
                                               title="{{ $lesson->type === 'quiz' ? __('lms.quiz') : __('learn.lesson_quiz') }}"><x-icon name="patch-question" class="me-1" />{{ $qn }}/{{ $qmin }}</a>
                                        @endif
                                        <a href="{{ route('instructor.lessons.edit', $lesson) }}" class="btn btn-sm btn-outline-primary"><x-icon name="pencil" /><span class="d-none d-md-inline ms-1">{{ __('lms.edit') }}</span></a>
                                    </div>
                                @endforeach
                            </div>

                            @php $en = $module->exam ? $module->exam->questions->count() : 0; $emin = config('lms.assessment.min_questions.module'); @endphp
                            <a href="{{ route('instructor.assessments.module', $module) }}" class="builder-exam {{ $en >= $emin ? '' : 'is-warning' }}">
                                <x-icon name="clipboard-check" class="text-primary" />
                                <span class="flex-grow-1 fw-semibold">{{ __('learn.module_exercise') }}</span>
                                <span class="badge {{ $en >= $emin ? 'bg-success' : 'bg-warning text-dark' }}">{{ __('learn.questions_of_min', ['count' => $en, 'min' => $emin]) }}</span>
                            </a>

                            @unless($locked)
                            <div class="px-3 pb-3">
                                <button type="button" class="btn btn-sm btn-light w-100 border-dashed" data-bs-toggle="modal" data-bs-target="#lessonModal"
                                        data-action="{{ route('instructor.lessons.store', $module) }}" data-module-title="{{ $module->title() }}">
                                    <x-icon name="plus-lg" class="me-1" />{{ __('lms.add_lesson') }}
                                </button>
                            </div>
                            @endunless
                        </div>
                    @empty
                        <div class="text-center bg-white rounded-xl shadow-brand py-5 px-3">
                            <x-icon name="diagram-3" style="font-size:2.5rem;color:var(--bs-primary);opacity:.4" />
                            <h5 class="mt-3">{{ __('lms.no_modules_title') }}</h5>
                            <p class="text-muted">{{ __('lms.no_modules_help') }}</p>
                            @unless($locked)
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#moduleModal" data-module-mode="create">
                                <x-icon name="plus-lg" class="me-1" />{{ __('lms.add_first_module') }}
                            </button>
                            @endunless
                        </div>
                    @endforelse
                </div>

                @if($course->modules->isNotEmpty())
                    @php $fn = $course->finalExam ? $course->finalExam->questions->count() : 0; $fmin = config('lms.assessment.min_questions.course'); @endphp
                    <a href="{{ route('instructor.assessments.final', $course) }}" class="builder-exam builder-final {{ $fn >= $fmin ? '' : 'is-warning' }}">
                        <x-icon name="trophy" class="text-warning fs-5" />
                        <span class="flex-grow-1"><span class="fw-bold d-block">{{ __('learn.final_evaluation') }}</span><span class="small text-muted">{{ __('learn.final_evaluation_help') }}</span></span>
                        <span class="badge {{ $fn >= $fmin ? 'bg-success' : 'bg-warning text-dark' }}">{{ __('learn.questions_of_min', ['count' => $fn, 'min' => $fmin]) }}</span>
                    </a>
                @endif
            </div>

            {{-- ─── Assessments ──────────────────────────────────────── --}}
            <div class="tab-pane fade {{ $tab === 'assessments' ? 'show active' : '' }}" id="tab-assessments">
                <div class="bg-white rounded-xl shadow-brand p-4 mb-3">
                    <h5 class="fw-bold">{{ __('learn.assessment_path') }}</h5>
                    <ol class="small text-muted mb-0">
                        <li>{{ __('learn.path_lesson', ['min' => config('lms.assessment.min_questions.lesson'), 'pass' => config('lms.assessment.pass_percent')]) }}</li>
                        <li>{{ __('learn.path_module', ['min' => config('lms.assessment.min_questions.module'), 'pass' => config('lms.assessment.pass_percent')]) }}</li>
                        <li>{{ __('learn.path_final', ['min' => config('lms.assessment.min_questions.course'), 'pass' => config('lms.assessment.pass_percent')]) }}</li>
                    </ol>
                </div>
                @include('instructor.quizzes.partials.plan', ['course' => $course])
            </div>

            {{-- ─── Books ────────────────────────────────────────────── --}}
            <div class="tab-pane fade {{ $tab === 'books' ? 'show active' : '' }}" id="tab-books">
                @include('instructor.courses.partials.books', ['course' => $course, 'locked' => $locked])
            </div>
        </div>
    </div>

    {{-- ─── Sidebar ─────────────────────────────────────────────────── --}}
    <div class="col-xl-4">
        <div class="bg-white rounded-xl shadow-brand p-4 mb-3">
            <h6 class="fw-bold mb-3"><x-icon name="hourglass-split" class="me-1" />{{ __('lms.hours_breakdown') }}</h6>
            <div class="d-flex justify-content-between small mb-1">
                <span>{{ __('lms.modules_total') }}</span>
                <strong>{{ \App\Models\Course::formatHours($modulesHours) }} / {{ $course->hoursLabel() }}</strong>
            </div>
            <div class="hours-meter {{ $modulesHours > $courseHours ? 'over' : '' }}"><div style="width: {{ $hoursPct }}%"></div></div>
            <p class="small text-muted mt-2 mb-0">
                @if($modulesHours > $courseHours)
                    <x-icon name="exclamation-triangle" class="text-danger me-1" />{{ __('lms.hours_over') }}
                @elseif($modulesHours < $courseHours)
                    {{ __('lms.hours_remaining', ['hours' => \App\Models\Course::formatHours($courseHours - $modulesHours)]) }}
                @else
                    <x-icon name="check-circle" class="text-success me-1" />{{ __('lms.hours_match') }}
                @endif
            </p>
            @if($course->duration_minutes)
                <p class="small text-muted mb-0 mt-1"><x-icon name="play-btn" class="me-1" />{{ __('lms.content_duration', ['duration' => $course->durationFormatted()]) }}</p>
            @endif
        </div>

        <div class="bg-white rounded-xl shadow-brand p-4 mb-3">
            <h6 class="fw-bold mb-3"><x-icon name="list-check" class="me-1" />{{ __('lms.before_submit') }}</h6>
            <ul class="checklist">
                @foreach($checklist as $item)
                    <li class="{{ ! $item['ok'] && session('checklist_failed') ? 'text-danger fw-semibold' : '' }}">
                        <x-icon :name="$item['ok'] ? 'check-circle-fill' : 'circle'" :class="$item['ok'] ? 'me-2 text-success' : 'me-2 text-muted'" />{{ $item['label'] }}
                    </li>
                @endforeach
            </ul>
            @if(in_array($course->status, ['draft', 'rejected', 'unpublished']))
                <form method="POST" action="{{ route('instructor.courses.submit', $course) }}" class="mt-3">
                    @csrf
                    <button class="btn btn-success w-100" @disabled(collect($checklist)->contains('ok', false))>
                        <x-icon name="send" class="me-1" />{{ __('lms.submit_review') }}
                    </button>
                </form>
            @elseif($course->status === 'pending')
                <div class="alert alert-warning small mt-3 mb-0">{{ __('lms.pending_review_help') }}</div>
            @endif
        </div>

        <div class="bg-white rounded-xl shadow-brand p-4">
            <h6 class="fw-bold mb-3">{{ __('lms.stats') }}</h6>
            <div class="row g-2 text-center">
                <div class="col-6"><div class="fs-4 fw-bold text-primary">{{ $course->enrollments_count }}</div><div class="small text-muted">{{ __('lms.students') }}</div></div>
                <div class="col-6"><div class="fs-4 fw-bold" style="color:#F59E0B">{{ number_format((float) $course->reviews_avg_rating, 1) }}</div><div class="small text-muted">{{ __('lms.rating') }}</div></div>
                <div class="col-6"><div class="fs-4 fw-bold text-primary">{{ $course->modules->count() }}</div><div class="small text-muted">{{ __('lms.modules') }}</div></div>
                <div class="col-6"><div class="fs-4 fw-bold text-primary">{{ $course->modules->sum(fn ($m) => $m->lessons->count()) }}</div><div class="small text-muted">{{ __('lms.lessons') }}</div></div>
            </div>
            @if($course->enrollments_count === 0 && $course->status !== 'published')
                <form method="POST" action="{{ route('instructor.courses.destroy', $course) }}" class="mt-3 text-center" data-confirm="{{ __('lms.confirm_delete_course') }}">
                    @csrf @method('DELETE')
                    <button class="btn btn-link btn-sm text-danger"><x-icon name="trash" class="me-1" />{{ __('lms.delete_course') }}</button>
                </form>
            @endif
        </div>
    </div>
</div>

{{-- ─── Module modal (create / edit) ───────────────────────────────────── --}}
<div class="modal fade" id="moduleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <form method="POST" class="modal-content" id="moduleForm" data-create-action="{{ route('instructor.modules.store', $course) }}">
            @csrf
            <input type="hidden" name="_method" value="POST" id="moduleMethod">
            <div class="modal-header">
                <h5 class="modal-title" id="moduleModalTitle" data-create="{{ __('lms.add_module') }}" data-edit="{{ __('lms.edit_module') }}">{{ __('lms.add_module') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('lms.close') }}"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3" style="max-width:220px;">
                    <label class="form-label fw-semibold">{{ __('lms.module_hours') }} *</label>
                    <div class="input-group">
                        <input type="number" name="duration_hours" class="form-control" min="0.5" step="0.5" required>
                        <span class="input-group-text">h</span>
                    </div>
                </div>
                <ul class="nav nav-pills lang-tabs mb-3">
                    @foreach($locales as $loc)
                        <li class="nav-item"><button type="button" class="nav-link {{ $loop->first ? 'active' : '' }}" data-bs-toggle="pill" data-bs-target="#module-{{ $loc }}">{{ config('app.locale_names')[$loc] ?? strtoupper($loc) }}</button></li>
                    @endforeach
                </ul>
                <div class="tab-content">
                    @foreach($locales as $loc)
                        <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="module-{{ $loc }}">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">{{ __('lms.module_title') }} @if($loc === $course->language)*@endif</label>
                                <input type="text" name="translations[{{ $loc }}][title]" class="form-control" maxlength="255" @required($loc === $course->language)>
                            </div>
                            <div>
                                <label class="form-label fw-semibold">{{ __('lms.module_description') }}</label>
                                <textarea name="translations[{{ $loc }}][description]" rows="3" class="form-control" placeholder="{{ __('lms.module_description_placeholder') }}"></textarea>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('lms.cancel') }}</button>
                <button class="btn btn-primary">{{ __('lms.save') }}</button>
            </div>
        </form>
    </div>
</div>

{{-- ─── New lesson modal ──────────────────────────────────────────────── --}}
<div class="modal fade" id="lessonModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" class="modal-content" id="lessonForm">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">{{ __('lms.add_lesson') }} <small class="text-muted d-block fs-6" id="lessonModalModule"></small></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('lms.close') }}"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">{{ __('lms.lesson_title') }} *</label>
                    <input type="text" name="title" class="form-control" maxlength="255" required>
                </div>
                <label class="form-label fw-semibold">{{ __('lms.lesson_type') }}</label>
                <div class="source-picker">
                    @foreach(['video' => 'bi-play-circle', 'text' => 'bi-file-text', 'quiz' => 'bi-patch-question', 'assignment' => 'bi-clipboard-check'] as $type => $icon)
                        <div class="position-relative">
                            <input type="radio" name="type" value="{{ $type }}" id="type-{{ $type }}" @checked($loop->first)>
                            <label for="type-{{ $type }}" class="w-100"><x-icon :name="$icon" />{{ $typeLabels[$type] }}</label>
                        </div>
                    @endforeach
                </div>
                <p class="small text-muted mt-3 mb-0">{{ __('lms.add_lesson_help') }}</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('lms.cancel') }}</button>
                <button class="btn btn-primary">{{ __('lms.create_and_edit') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
@vite(['resources/js/course-builder.js'])
@endpush
