@extends('layouts.instructor')
@section('title', $lesson->title())
@section('page-title', __('lms.edit_lesson'))

@php
    $locked = $course->status === 'pending' && ! auth()->user()->isAdmin();
    $kind = $lesson->videoKind() ?? 'upload';
    $ordered = $course->modules->flatMap->lessons->values();
    $pos = $ordered->search(fn ($l) => $l->id === $lesson->id);
    $prev = $pos > 0 ? $ordered[$pos - 1] : null;
    $next = $ordered[$pos + 1] ?? null;
    $typeLabels = ['video' => __('lms.type_video'), 'text' => __('lms.type_text'), 'quiz' => __('lms.type_quiz'), 'assignment' => __('lms.type_assignment')];
@endphp

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css">
@endpush

@section('content')
<nav aria-label="{{ __('Breadcrumb') }}" class="mb-2">
    <ol class="breadcrumb small mb-0">
        <li class="breadcrumb-item"><a href="{{ route('instructor.courses.edit', ['course' => $course, 'tab' => 'curriculum']) }}">{{ \Illuminate\Support\Str::limit($course->title(), 40) }}</a></li>
        <li class="breadcrumb-item">{{ \Illuminate\Support\Str::limit($lesson->module->title(), 40) }}</li>
        <li class="breadcrumb-item active">{{ \Illuminate\Support\Str::limit($lesson->title(), 40) }}</li>
    </ol>
</nav>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <h4 class="fw-bold mb-0"><x-icon :name="$lesson->icon()" class="text-primary me-2" />{{ $lesson->title() }}</h4>
    <div class="btn-group btn-group-sm">
        <a class="btn btn-outline-secondary {{ $prev ? '' : 'disabled' }}" href="{{ $prev ? route('instructor.lessons.edit', $prev) : '#' }}"><x-icon name="chevron-left" /> {{ __('lms.previous') }}</a>
        <a class="btn btn-outline-secondary {{ $next ? '' : 'disabled' }}" href="{{ $next ? route('instructor.lessons.edit', $next) : '#' }}">{{ __('lms.next') }} <x-icon name="chevron-right" /></a>
    </div>
</div>

@if($locked)
    <div class="alert alert-warning"><x-icon name="lock" class="me-1" />{{ __('lms.course_locked_pending') }}</div>
@endif

<div class="row g-4">
<div class="col-xl-8">

{{-- ─── Main lesson form ─────────────────────────────────────────────── --}}
<form method="POST" action="{{ route('instructor.lessons.update', $lesson) }}" id="lessonEditor" class="bg-white rounded-xl shadow-brand p-4 mb-4">
    @csrf @method('PUT')
    <fieldset @disabled($locked)>

    <label class="form-label fw-semibold">{{ __('lms.lesson_type') }}</label>
    <div class="source-picker mb-4">
        @foreach(['video' => 'bi-play-circle', 'text' => 'bi-file-text', 'quiz' => 'bi-patch-question', 'assignment' => 'bi-clipboard-check'] as $type => $icon)
            <div class="position-relative">
                <input type="radio" name="type" value="{{ $type }}" id="ltype-{{ $type }}" @checked(old('type', $lesson->type) === $type)>
                <label for="ltype-{{ $type }}" class="w-100"><x-icon :name="$icon" />{{ $typeLabels[$type] }}</label>
            </div>
        @endforeach
    </div>

    {{-- Title + content per language --}}
    <ul class="nav nav-pills lang-tabs mb-3">
        @foreach($locales as $loc)
            <li class="nav-item"><button type="button" class="nav-link {{ $loc === $course->language ? 'active' : '' }}" data-bs-toggle="pill" data-bs-target="#lesson-{{ $loc }}">
                {{ config('app.locale_names')[$loc] ?? strtoupper($loc) }}
                @if($lesson->translationExact($loc))<x-icon name="check-circle-fill" class="text-success ms-1" />@endif
            </button></li>
        @endforeach
    </ul>
    <div class="tab-content mb-4">
        @foreach($locales as $loc)
            @php
                $tr = $lesson->translationExact($loc);
                // Older lessons kept their body in lessons.content: show it in the main language.
                $body = $tr?->content ?: ($loc === $course->language ? $lesson->content : '');
            @endphp
            <div class="tab-pane fade {{ $loc === $course->language ? 'show active' : '' }}" id="lesson-{{ $loc }}">
                @if($loc !== $course->language)
                    <p class="small text-muted"><x-icon name="info-circle" class="me-1" />{{ __('lms.optional_translation') }}</p>
                @endif
                <div class="mb-3">
                    <label class="form-label fw-semibold">{{ __('lms.lesson_title') }} @if($loc === $course->language)*@endif</label>
                    <input type="text" name="translations[{{ $loc }}][title]" class="form-control @error("translations.$loc.title") is-invalid @enderror"
                           maxlength="255" value="{{ old("translations.$loc.title", $tr?->title) }}">
                    @error("translations.$loc.title")<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <label class="form-label fw-semibold">
                    <span data-for-type="video,text,quiz">{{ __('lms.lesson_content') }}</span>
                    <span data-for-type="assignment">{{ __('lms.assignment_instructions') }}</span>
                </label>
                <div class="editor-box">
                    <textarea name="translations[{{ $loc }}][content]" rows="10" class="form-control" data-rich-editor
                              placeholder="{{ __('lms.lesson_content_placeholder') }}">{{ old("translations.$loc.content", $body) }}</textarea>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Video source --}}
    <section data-for-type="video" class="mb-4">
        <h6 class="fw-bold mb-2">{{ __('lms.video') }}</h6>
        <div class="source-picker mb-3">
            @foreach(['upload' => 'bi-cloud-arrow-up', 'youtube' => 'bi-youtube', 'vimeo' => 'bi-vimeo', 'url' => 'bi-link-45deg'] as $src => $icon)
                <div class="position-relative">
                    <input type="radio" name="video_source" value="{{ $src }}" id="src-{{ $src }}" @checked(old('video_source', $kind) === $src)>
                    <label for="src-{{ $src }}" class="w-100"><x-icon :name="$icon" />{{ __('lms.source_' . $src) }}</label>
                </div>
            @endforeach
        </div>

        <div data-for-source="upload">
            @if($lesson->video_path)
                <div class="upload-card mb-3">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                        <div><x-icon name="film" class="text-primary me-1" /><strong>{{ $lesson->video_original_name }}</strong>
                            <span class="text-muted small">({{ \App\Support\Format::bytes($lesson->video_size) }})</span></div>
                        <button type="submit" form="deleteVideoForm" class="btn btn-sm btn-outline-danger" @disabled($locked)><x-icon name="trash" class="me-1" />{{ __('lms.delete_video') }}</button>
                    </div>
                    <div class="ratio ratio-16x9 rounded overflow-hidden bg-dark">
                        <video controls preload="metadata" src="{{ route('media.lesson.video', $lesson) }}"></video>
                    </div>
                </div>
                <p class="small text-muted mb-2">{{ __('lms.replace_video_help') }}</p>
            @endif
            <div id="videoUploader"
                 data-lesson-id="{{ $lesson->id }}"
                 data-chunk-url="{{ route('instructor.lessons.video.chunk', $lesson) }}"
                 data-status-url="{{ route('instructor.lessons.video.status', $lesson) }}"
                 data-chunk-mb="{{ config('lms.video.chunk_size_mb') }}"
                 data-max-mb="{{ config('lms.video.max_size_mb') }}"
                 data-extensions="{{ implode(',', config('lms.video.extensions')) }}">
                <div class="dropzone" tabindex="0" role="button" aria-label="{{ __('lms.upload_video') }}">
                    <x-icon name="cloud-arrow-up" />
                    <div class="fw-semibold mt-2">{{ __('lms.drop_video') }}</div>
                    <div class="small text-muted">{{ __('lms.video_formats', ['formats' => 'MP4, WebM, MOV', 'max' => \App\Support\Format::bytes(config('lms.video.max_size_mb') * 1048576)]) }}</div>
                    <div class="small text-muted">{{ __('lms.upload_resumable_help') }}</div>
                </div>
                <input type="file" accept="video/mp4,video/webm,video/quicktime,video/x-m4v,video/ogg,.mp4,.webm,.mov,.m4v,.ogv" hidden>
                <div data-role="status"></div>
            </div>
        </div>

        <div data-for-source="youtube">
            <label class="form-label fw-semibold">{{ __('lms.youtube_url') }}</label>
            <input type="text" name="youtube_url" class="form-control @error('youtube_url') is-invalid @enderror"
                   placeholder="{{ __('https://www.youtube.com/watch?v=…') }}" value="{{ old('youtube_url', $lesson->youtubeId() ? 'https://www.youtube.com/watch?v=' . $lesson->youtubeId() : ($lesson->youtube_playlist_id ? $lesson->video_url : '')) }}">
            @error('youtube_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <div class="form-text">{{ __('lms.youtube_help') }}</div>
            <div id="youtubePreview" class="mt-3"></div>
        </div>

        <div data-for-source="vimeo">
            <label class="form-label fw-semibold">{{ __('lms.vimeo_url') }}</label>
            <input type="text" name="vimeo_url" class="form-control @error('vimeo_url') is-invalid @enderror"
                   placeholder="{{ __('https://vimeo.com/123456789') }}" value="{{ old('vimeo_url', $kind === 'vimeo' ? $lesson->video_url : '') }}">
            @error('vimeo_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div data-for-source="url">
            <label class="form-label fw-semibold">{{ __('lms.external_url') }}</label>
            <input type="url" name="external_url" class="form-control @error('external_url') is-invalid @enderror"
                   placeholder="{{ __('https://…/video.mp4') }}" value="{{ old('external_url', $kind === 'url' ? $lesson->video_url : '') }}">
            <div class="form-text">{{ __('lms.external_url_help') }}</div>
        </div>
    </section>

    {{-- Assignment scoring --}}
    <section data-for-type="assignment" class="mb-4">
        <h6 class="fw-bold mb-2">{{ __('lms.grading') }}</h6>
        <div class="row g-3">
            <div class="col-sm-6">
                <label class="form-label">{{ __('lms.max_score') }}</label>
                <input type="number" name="assignment_max_score" class="form-control" min="1" max="1000" value="{{ old('assignment_max_score', $lesson->assignment_max_score) }}">
            </div>
            <div class="col-sm-6">
                <label class="form-label">{{ __('lms.pass_score') }}</label>
                <input type="number" name="assignment_pass_score" class="form-control @error('assignment_pass_score') is-invalid @enderror" min="0" max="1000" value="{{ old('assignment_pass_score', $lesson->assignment_pass_score) }}">
                @error('assignment_pass_score')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
        <p class="small text-muted mt-2 mb-0">{{ __('lms.assignment_completion_help') }}</p>
    </section>

    <div class="row g-3 align-items-end">
        <div class="col-sm-4">
            <label class="form-label fw-semibold">{{ __('lms.duration_minutes') }}</label>
            <div class="input-group">
                <input type="number" name="duration_minutes" class="form-control" min="0" max="1440" value="{{ old('duration_minutes', $lesson->duration_minutes) }}">
                <span class="input-group-text">{{ __('min') }}</span>
            </div>
        </div>
        <div class="col-sm-8">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="is_free_preview" value="1" id="freePreview" @checked(old('is_free_preview', $lesson->is_free_preview))>
                <label class="form-check-label" for="freePreview">{{ __('lms.free_preview') }} <span class="text-muted small">— {{ __('lms.free_preview_help') }}</span></label>
            </div>
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="is_downloadable" value="1" id="downloadable" @checked(old('is_downloadable', $lesson->is_downloadable))>
                <label class="form-check-label" for="downloadable">{{ __('lms.downloadable') }} <span class="text-muted small">— {{ __('lms.downloadable_help') }}</span></label>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mt-4">
        <a href="{{ route('instructor.courses.edit', ['course' => $course, 'tab' => 'curriculum']) }}" class="btn btn-light"><x-icon name="arrow-left" class="me-1" />{{ __('lms.back_to_curriculum') }}</a>
        <button class="btn btn-primary"><x-icon name="check-lg" class="me-1" />{{ __('lms.save_lesson') }}</button>
    </div>
    </fieldset>
</form>

<form method="POST" action="{{ route('instructor.lessons.video.destroy', $lesson) }}" id="deleteVideoForm" data-confirm="{{ __('lms.confirm_delete_video') }}">
    @csrf @method('DELETE')
</form>

{{-- ─── Lesson quiz (validates the lesson) ───────────────────────── --}}
@if($lesson->type !== 'assignment')
    @php
        $lessonQuiz = $lesson->quiz;
        $qCount = $lessonQuiz ? $lessonQuiz->questions->count() : 0;
        $qMin = config('lms.assessment.min_questions.lesson');
    @endphp
    <div class="bg-white rounded-xl shadow-brand p-4 mb-4" id="quiz">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h5 class="fw-bold mb-1"><x-icon name="patch-question" class="me-1" />{{ $lesson->type === 'quiz' ? __('lms.quiz') : __('learn.lesson_quiz') }}</h5>
                <p class="small text-muted mb-0">{{ $lesson->type === 'quiz'
                    ? __('learn.quiz_lesson_help', ['min' => $qMin, 'pass' => config('lms.assessment.pass_percent')])
                    : __('learn.scope_help_lesson', ['min' => $qMin, 'pass' => config('lms.assessment.pass_percent')]) }}</p>
            </div>
            <div class="text-end">
                <span class="badge mb-2 {{ $qCount >= $qMin ? 'bg-success' : 'bg-warning text-dark' }}">{{ __('learn.questions_of_min', ['count' => $qCount, 'min' => $qMin]) }}</span><br>
                <a href="{{ route('instructor.assessments.lesson', $lesson) }}" class="btn btn-sm btn-primary {{ $locked && ! $lessonQuiz ? 'disabled' : '' }}">
                    <x-icon name="pencil-square" class="me-1" />{{ $lessonQuiz ? __('learn.edit_questions') : __('learn.create_quiz') }}</a>
            </div>
        </div>
    </div>
@endif

{{-- ─── Resources ────────────────────────────────────────────────────── --}}
<div class="bg-white rounded-xl shadow-brand p-4 mb-4" id="resources">
    <h5 class="fw-bold mb-1"><x-icon name="paperclip" class="me-1" />{{ __('lms.resources') }}</h5>
    <p class="small text-muted">{{ __('lms.resources_help') }}</p>

    @foreach($lesson->resources as $resource)
        <div class="d-flex align-items-center gap-3 border rounded p-2 mb-2">
            <x-icon :name="$resource->icon()" class="fs-4 text-secondary" />
            <div class="flex-grow-1 min-w-0">
                <div class="fw-semibold text-truncate">{{ $resource->title }}</div>
                <div class="small text-muted text-truncate">{{ $resource->original_name }} · {{ $resource->sizeLabel() }}</div>
            </div>
            <a href="{{ route('media.resource', $resource) }}" class="btn btn-sm btn-light" aria-label="{{ __('lms.download') }}"><x-icon name="download" /></a>
            @unless($locked)
            <form method="POST" action="{{ route('instructor.resources.destroy', $resource) }}" data-confirm="{{ __('lms.confirm_delete_resource') }}">
                @csrf @method('DELETE')
                <button class="btn btn-sm btn-light text-danger" aria-label="{{ __('lms.delete') }}"><x-icon name="trash" /></button>
            </form>
            @endunless
        </div>
    @endforeach

    @unless($locked)
    <form method="POST" action="{{ route('instructor.resources.store', $lesson) }}" enctype="multipart/form-data" class="row g-2 align-items-end mt-2">
        @csrf
        <div class="col-md-5">
            <label class="form-label small">{{ __('lms.resource_title') }}</label>
            <input type="text" name="title" class="form-control form-control-sm" maxlength="255" placeholder="{{ __('lms.resource_title_placeholder') }}">
        </div>
        <div class="col-md-5">
            <label class="form-label small">{{ __('lms.file') }} ({{ __('max. :size', ['size' => \App\Support\Format::bytes(config('lms.resource.max_size_mb') * 1048576)]) }})</label>
            <input type="file" name="file" class="form-control form-control-sm @error('file') is-invalid @enderror" required>
            @error('file')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-2"><button class="btn btn-sm btn-outline-primary w-100"><x-icon name="upload" class="me-1" />{{ __('lms.add') }}</button></div>
    </form>
    @endunless
</div>
</div>

{{-- ─── Sidebar ──────────────────────────────────────────────────────── --}}
<div class="col-xl-4">
    <div class="bg-white rounded-xl shadow-brand p-4 mb-3">
        <h6 class="fw-bold mb-2">{{ __('lms.tips') }}</h6>
        <ul class="small text-muted ps-3 mb-0">
            <li>{{ __('lms.tip_video') }}</li>
            <li>{{ __('lms.tip_youtube') }}</li>
            <li>{{ __('lms.tip_offline') }}</li>
            <li>{{ __('lms.tip_completion') }}</li>
        </ul>
    </div>
    <div class="bg-white rounded-xl shadow-brand p-4">
        <h6 class="fw-bold mb-2">{{ $lesson->module->title() }}</h6>
        <div class="small text-muted mb-2">{{ $lesson->module->hoursLabel() }}</div>
        <ol class="small ps-3 mb-0">
            @foreach($course->modules->firstWhere('id', $lesson->module_id)?->lessons ?? [] as $sibling)
                <li class="{{ $sibling->id === $lesson->id ? 'fw-bold' : '' }}">
                    <a href="{{ route('instructor.lessons.edit', $sibling) }}" class="{{ $sibling->id === $lesson->id ? 'text-dark' : '' }}">{{ $sibling->title() }}</a>
                </li>
            @endforeach
        </ol>
        @unless($locked)
        <hr>
        <form method="POST" action="{{ route('instructor.lessons.destroy', $lesson) }}" data-confirm="{{ __('lms.confirm_delete_lesson') }}">
            @csrf @method('DELETE')
            <button class="btn btn-sm btn-outline-danger w-100"><x-icon name="trash" class="me-1" />{{ __('lms.delete_lesson') }}</button>
        </form>
        @endunless
    </div>
</div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
@vite(['resources/js/course-builder.js'])
@endpush
