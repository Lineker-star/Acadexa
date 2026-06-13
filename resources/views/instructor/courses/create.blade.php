@extends('layouts.instructor')
@section('title', 'Create Course')

@section('content')
<div class="mb-4">
    <h4 class="fw-bold">Create New Course</h4>
    <p class="text-muted mb-0">Fill in the basic details. You can add modules and lessons after creation.</p>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="bg-white rounded-xl shadow-brand p-4">
            <form method="POST" action="{{ route('instructor.courses.store') }}" enctype="multipart/form-data">
                @csrf

                <div class="mb-3">
                    <label class="form-label fw-bold">Course Title (English) *</label>
                    <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
                           value="{{ old('title') }}" required placeholder="e.g. Complete Web Development Bootcamp">
                    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Title (French)</label>
                    <input type="text" name="translations[fr][title]" class="form-control"
                           value="{{ old('translations.fr.title') }}">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Short Description (English) *</label>
                    <textarea name="description" class="form-control @error('description') is-invalid @enderror"
                              rows="4" required>{{ old('description') }}</textarea>
                    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Description (French)</label>
                    <textarea name="translations[fr][description]" class="form-control" rows="3">{{ old('translations.fr.description') }}</textarea>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Category *</label>
                        <select name="category_id" class="form-select @error('category_id') is-invalid @enderror" required>
                            <option value="">Select Category</option>
                            @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ old('category_id')==$cat->id?'selected':'' }}>
                                {{ $cat->icon ?? '' }} {{ $cat->name }}
                            </option>
                            @endforeach
                        </select>
                        @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Level *</label>
                        <select name="level" class="form-select" required>
                            <option value="beginner" {{ old('level')=='beginner'?'selected':'' }}>Beginner</option>
                            <option value="intermediate" {{ old('level')=='intermediate'?'selected':'' }}>Intermediate</option>
                            <option value="advanced" {{ old('level')=='advanced'?'selected':'' }}>Advanced</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Language</label>
                        <select name="language" class="form-select">
                            @foreach(config('app.locale_names') as $locale => $name)
                            <option value="{{ $locale }}" {{ old('language', 'en')==$locale?'selected':'' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Duration (hours)</label>
                        <input type="number" name="duration_hours" class="form-control" min="0" step="0.5"
                               value="{{ old('duration_hours', 0) }}">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">What Students Will Learn (one item per line)</label>
                    <textarea name="what_you_learn" class="form-control" rows="4"
                              placeholder="Build real-world projects&#10;Understand core concepts&#10;Get career-ready skills">{{ old('what_you_learn') }}</textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Requirements / Prerequisites (one per line)</label>
                    <textarea name="requirements" class="form-control" rows="3"
                              placeholder="Basic computer skills&#10;Access to the internet">{{ old('requirements') }}</textarea>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Course Thumbnail</label>
                    <input type="file" name="thumbnail" class="form-control" accept="image/*">
                    <div class="form-text">Recommended: 1280×720px (16:9), JPEG or PNG</div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" name="action" value="save_draft" class="btn btn-outline-secondary">
                        Save as Draft
                    </button>
                    <button type="submit" name="action" value="continue" class="btn btn-primary">
                        Create & Add Curriculum →
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="bg-white rounded-xl shadow-brand p-4">
            <h6 class="fw-bold mb-3">Course Creation Guide</h6>
            <ol class="ps-3" style="font-size:.85rem;color:var(--text-muted);">
                <li class="mb-2">Fill in basic course details here</li>
                <li class="mb-2">Add modules to organize your content</li>
                <li class="mb-2">Add lessons to each module (video, text, quiz)</li>
                <li class="mb-2">Preview your course</li>
                <li class="mb-2">Submit for admin review</li>
                <li>Once approved, it goes live!</li>
            </ol>
            <div class="alert alert-info py-2" style="font-size:.8rem;">
                <i class="bi bi-info-circle me-1"></i>
                Courses must be reviewed by an admin before students can enroll.
            </div>
        </div>
    </div>
</div>
@endsection
