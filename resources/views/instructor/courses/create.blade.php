@extends('layouts.instructor')
@section('title', __('lms.new_course'))
@section('page-title', __('lms.new_course'))

@php
    $oldModules = old('modules', [['title' => __('lms.module') . ' 1', 'hours' => null, 'lessons' => 3]]);
@endphp

@section('content')
<div class="row justify-content-center">
    <div class="col-xl-9">
        <div class="bg-white rounded-xl shadow-brand p-4 p-md-5">
            <h4 class="fw-bold mb-1">{{ __('lms.new_course') }}</h4>
            <p class="text-muted mb-4">{{ __('learn.wizard_help') }}</p>

            {{-- Steps: nothing is created before the last step. Without JavaScript all steps are shown at once. --}}
            <ol class="wizard-steps list-unstyled d-flex flex-wrap gap-2 mb-4" id="wizardSteps">
                @foreach([__('learn.wizard_step_info'), __('learn.wizard_step_category'), __('learn.wizard_step_structure'), __('learn.wizard_step_content')] as $i => $label)
                    <li data-step-tab="{{ $i }}" class="{{ $i === 0 ? 'active' : '' }}"><span class="num">{{ $i + 1 }}</span>{{ $label }}</li>
                @endforeach
            </ol>

            @if($errors->any())
                <div class="alert alert-danger small">{{ $errors->first('content') ?: __('learn.wizard_fix_errors') }}</div>
            @endif

            <form method="POST" action="{{ route('instructor.courses.store') }}" enctype="multipart/form-data" id="courseWizard" novalidate>
                @csrf

                {{-- ─── Step 1: information ─── --}}
                <section data-step="0">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold" for="title">{{ __('lms.title') }} *</label>
                            <input type="text" name="title" id="title" class="form-control @error('title') is-invalid @enderror"
                                   maxlength="255" required value="{{ old('title') }}" placeholder="{{ __('lms.course_title_placeholder') }}">
                            @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" for="description">{{ __('lms.description') }} *</label>
                            <textarea name="description" id="description" rows="5" class="form-control @error('description') is-invalid @enderror" required>{{ old('description') }}</textarea>
                            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="language">{{ __('lms.course_language') }} *</label>
                            <select name="language" id="language" class="form-select" required>
                                @foreach(\App\Http\Controllers\Instructor\CourseController::CONTENT_LOCALES as $loc)
                                    <option value="{{ $loc }}" @selected(old('language', 'fr') === $loc)>{{ config('app.locale_names')[$loc] }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">{{ __('lms.course_language_help') }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="duration_hours">{{ __('lms.course_hours') }} *</label>
                            <div class="input-group has-validation">
                                <input type="number" name="duration_hours" id="duration_hours" class="form-control @error('duration_hours') is-invalid @enderror"
                                       min="0.5" max="2000" step="0.5" required value="{{ old('duration_hours') }}" placeholder="40">
                                <span class="input-group-text">{{ __('lms.hours') }}</span>
                                @error('duration_hours')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="form-text">{{ __('lms.course_hours_help') }}</div>
                        </div>
                    </div>
                </section>

                {{-- ─── Step 2: category, level, picture ─── --}}
                <section data-step="1">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="category_id">{{ __('lms.category') }} *</label>
                            <select name="category_id" id="category_id" class="form-select @error('category_id') is-invalid @enderror" required>
                                <option value="">—</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" @selected(old('category_id') == $cat->id)>{{ $cat->name() }}</option>
                                @endforeach
                            </select>
                            @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="level">{{ __('lms.level') }} *</label>
                            <select name="level" id="level" class="form-select" required>
                                @foreach(['beginner', 'intermediate', 'advanced'] as $lvl)
                                    <option value="{{ $lvl }}" @selected(old('level') === $lvl)>{{ __('messages.' . $lvl) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" for="thumbnail">{{ __('lms.thumbnail') }}</label>
                            <input type="file" name="thumbnail" id="thumbnail" class="form-control @error('thumbnail') is-invalid @enderror" accept="image/jpeg,image/png,image/webp">
                            <div class="form-text">{{ __('lms.thumbnail_help') }} {{ __('learn.thumbnail_generated_help') }}</div>
                            @error('thumbnail')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </section>

                {{-- ─── Step 3: structure (modules and number of lessons) ─── --}}
                <section data-step="2">
                    <p class="small text-muted">{{ __('learn.structure_help') }}</p>
                    <div id="moduleRows" data-module-label="{{ __('lms.module') }}">
                        @foreach($oldModules as $i => $m)
                            <div class="structure-row" data-module-row>
                                <span class="structure-num">{{ $i + 1 }}</span>
                                <div class="flex-grow-1">
                                    <label class="form-label small mb-1">{{ __('lms.module_title') }} *</label>
                                    <input type="text" name="modules[{{ $i }}][title]" class="form-control form-control-sm @error("modules.$i.title") is-invalid @enderror" maxlength="255" required value="{{ $m['title'] ?? '' }}">
                                </div>
                                <div style="width:110px">
                                    <label class="form-label small mb-1">{{ __('learn.lessons_number') }} *</label>
                                    <input type="number" name="modules[{{ $i }}][lessons]" class="form-control form-control-sm" min="1" max="100" required value="{{ $m['lessons'] ?? 1 }}">
                                </div>
                                <div style="width:110px">
                                    <label class="form-label small mb-1">{{ __('lms.hours') }}</label>
                                    <input type="number" name="modules[{{ $i }}][hours]" class="form-control form-control-sm" min="0.5" max="500" step="0.5" value="{{ $m['hours'] ?? '' }}" placeholder="—">
                                </div>
                                <button type="button" class="btn btn-sm btn-light text-danger align-self-end" data-remove-module aria-label="{{ __('lms.delete') }}"><x-icon name="trash" /></button>
                            </div>
                        @endforeach
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="addModule"><x-icon name="plus-lg" class="me-1" />{{ __('lms.add_module') }}</button>
                    <div class="small text-muted mt-2" id="structureTotal" data-template="{{ __('learn.structure_total') }}"></div>
                </section>

                {{-- ─── Step 4: content — at least one option ─── --}}
                <section data-step="3">
                    <p class="small text-muted">{{ __('learn.content_help') }}</p>
                    <div id="contentError" class="alert alert-warning small py-2 @unless($errors->has('content')) d-none @endunless">{{ __('learn.content_required') }}</div>

                    {{-- A. YouTube video or playlist --}}
                    <div class="content-option">
                        <h6 class="fw-bold"><x-icon name="youtube" class="text-danger me-1" />{{ __('learn.content_youtube') }}</h6>
                        <input type="text" name="content_youtube_url" class="form-control @error('content_youtube_url') is-invalid @enderror" data-youtube-input data-allow-playlist
                               value="{{ old('content_youtube_url') }}" placeholder="{{ __('learn.content_youtube_placeholder') }}" aria-label="{{ __('learn.content_youtube') }}">
                        @error('content_youtube_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">{{ __('learn.content_youtube_help') }}</div>
                        <div class="mt-2" style="max-width:520px" data-youtube-preview></div>
                    </div>

                    {{-- B. Uploaded videos, one per lesson --}}
                    <div class="content-option">
                        <h6 class="fw-bold"><x-icon name="camera-video" class="text-primary me-1" />{{ __('learn.content_videos', ['max' => config('lms.video.max_size_mb')]) }}</h6>
                        @include('instructor.courses.partials.multi-upload', [
                            'kind' => 'video', 'field' => 'videos',
                            'maxMb' => config('lms.video.max_size_mb'), 'extensions' => config('lms.video.extensions'),
                            'accept' => 'video/mp4,video/webm,video/quicktime,video/x-m4v,video/ogg,.mp4,.webm,.mov,.m4v,.ogv',
                            'help' => __('learn.content_videos_help'),
                        ])
                    </div>

                    {{-- C. Course material: PDF or PowerPoint --}}
                    <div class="content-option">
                        <h6 class="fw-bold"><x-icon name="file-earmark-pdf" class="text-danger me-1" />{{ __('learn.content_documents') }}</h6>
                        @include('instructor.courses.partials.multi-upload', [
                            'kind' => 'document', 'field' => 'documents',
                            'maxMb' => config('lms.document.max_size_mb'), 'extensions' => config('lms.document.extensions'),
                            'accept' => '.pdf,.ppt,.pptx,application/pdf,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation',
                            'help' => __('learn.content_documents_help'),
                        ])
                    </div>

                    {{-- Optional presentation video shown on the course page --}}
                    <details class="content-option" @if(old('intro_youtube_url') || old('intro_video_token')) open @endif>
                        <summary class="fw-bold">{{ __('learn.intro_video') }} <span class="text-muted fw-normal small">— {{ __('learn.optional') }}</span></summary>
                        <div class="mt-3">@include('instructor.courses.partials.intro-video', ['course' => null])</div>
                    </details>
                </section>

                <div class="d-flex justify-content-between gap-2 mt-4">
                    <div>
                        <a href="{{ route('instructor.courses.index') }}" class="btn btn-light">{{ __('lms.cancel') }}</a>
                        <button type="button" class="btn btn-outline-secondary" data-wizard-prev hidden><x-icon name="arrow-left" class="me-1" />{{ __('lms.previous') }}</button>
                    </div>
                    <div>
                        <button type="button" class="btn btn-primary" data-wizard-next hidden>{{ __('lms.next') }}<x-icon name="arrow-right" class="ms-1" /></button>
                        <button type="submit" class="btn btn-success" data-wizard-submit><x-icon name="check2-circle" class="me-1" />{{ __('learn.create_course') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@vite(['resources/js/course-builder.js'])
@endpush
