@extends('layouts.instructor')
@section('title', __('lms.new_course'))
@section('page-title', __('lms.new_course'))

@section('content')
<div class="row justify-content-center">
    <div class="col-xl-8">
        <div class="bg-white rounded-xl shadow-brand p-4 p-md-5">
            <h4 class="fw-bold mb-1">{{ __('lms.new_course') }}</h4>
            <p class="text-muted mb-4">{{ __('learn.wizard_help') }}</p>

            {{-- Steps: nothing is created before the last step. Without JavaScript all steps are shown at once. --}}
            <ol class="wizard-steps list-unstyled d-flex flex-wrap gap-2 mb-4" id="wizardSteps">
                @foreach([__('learn.wizard_step_info'), __('learn.wizard_step_category'), __('learn.wizard_step_video')] as $i => $label)
                    <li data-step-tab="{{ $i }}" class="{{ $i === 0 ? 'active' : '' }}"><span class="num">{{ $i + 1 }}</span>{{ $label }}</li>
                @endforeach
            </ol>

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

                {{-- ─── Step 3: presentation video (YouTube) ─── --}}
                <section data-step="2">
                    <label class="form-label fw-semibold" for="intro_youtube_url"><x-icon name="youtube" class="me-1 text-danger" />{{ __('learn.intro_video') }}</label>
                    <input type="text" name="intro_youtube_url" id="intro_youtube_url" class="form-control @error('intro_youtube_url') is-invalid @enderror"
                           value="{{ old('intro_youtube_url') }}" placeholder="{{ __('https://www.youtube.com/watch?v=…') }}" data-youtube-input>
                    @error('intro_youtube_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text">{{ __('learn.intro_video_help') }}</div>
                    <div class="mt-3" data-youtube-preview></div>
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
