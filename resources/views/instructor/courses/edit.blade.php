@extends('layouts.instructor')
@section('title', 'Edit Course')

@section('content')
<div class="mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h4 class="fw-bold mb-0">Edit: {{ $course->title() }}</h4>
            <span class="badge {{ match($course->status) {'published'=>'bg-success','pending'=>'bg-warning text-dark','rejected'=>'bg-danger',default=>'bg-secondary'} }} mt-1">
                {{ ucfirst($course->status) }}
            </span>
        </div>
        @if($course->status === 'draft' || $course->status === 'rejected')
        <form method="POST" action="{{ route('instructor.courses.submit', $course) }}">
            @csrf
            <button class="btn btn-success">
                <i class="bi bi-send me-1"></i>Submit for Review
            </button>
        </form>
        @endif
    </div>
    @if($course->admin_feedback)
    <div class="alert alert-danger mt-2 py-2">
        <strong>Admin Feedback:</strong> {{ $course->admin_feedback }}
    </div>
    @endif
</div>

<!-- Tabs -->
<ul class="nav nav-tabs mb-4" id="courseTabs">
    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tab-info">Course Info</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-curriculum">Curriculum</a></li>
</ul>

<div class="tab-content">
    <!-- Course Info Tab -->
    <div class="tab-pane fade show active" id="tab-info">
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="bg-white rounded-xl shadow-brand p-4">
                    <form method="POST" action="{{ route('instructor.courses.update', $course) }}" enctype="multipart/form-data">
                        @csrf @method('PUT')
                        <div class="mb-3">
                            <label class="form-label fw-bold">Title (English) *</label>
                            <input type="text" name="title" class="form-control" required
                                   value="{{ old('title', $course->title('en')) }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Title (French)</label>
                            <input type="text" name="translations[fr][title]" class="form-control"
                                   value="{{ old('translations.fr.title', $course->title('fr')) }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Description (English) *</label>
                            <textarea name="description" class="form-control" rows="4" required>{{ old('description', $course->description('en')) }}</textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Description (French)</label>
                            <textarea name="translations[fr][description]" class="form-control" rows="3">{{ old('translations.fr.description', $course->description('fr')) }}</textarea>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Category *</label>
                                <select name="category_id" class="form-select" required>
                                    @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ $course->category_id==$cat->id?'selected':'' }}>
                                        {{ $cat->icon ?? '' }} {{ $cat->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Level</label>
                                <select name="level" class="form-select">
                                    @foreach(['beginner','intermediate','advanced'] as $lvl)
                                    <option value="{{ $lvl }}" {{ $course->level==$lvl?'selected':'' }}>{{ ucfirst($lvl) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Duration (hours)</label>
                                <input type="number" name="duration_hours" class="form-control" min="0" step="0.5"
                                       value="{{ $course->duration_hours }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Language</label>
                                <select name="language" class="form-select">
                                    @foreach(config('app.locale_names') as $locale => $name)
                                    <option value="{{ $locale }}" {{ $course->language==$locale?'selected':'' }}>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">What Students Will Learn</label>
                            <textarea name="what_you_learn" class="form-control" rows="4">{{ old('what_you_learn', is_array($course->what_you_learn) ? implode("\n", $course->what_you_learn) : $course->what_you_learn) }}</textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Requirements</label>
                            <textarea name="requirements" class="form-control" rows="3">{{ old('requirements', is_array($course->requirements) ? implode("\n", $course->requirements) : $course->requirements) }}</textarea>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-bold">Thumbnail</label>
                            @if($course->thumbnail)
                            <div class="mb-2">
                                <img src="{{ $course->thumbnailUrl() }}" class="rounded" style="height:100px;object-fit:cover;" alt="thumbnail">
                            </div>
                            @endif
                            <input type="file" name="thumbnail" class="form-control" accept="image/*">
                        </div>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </form>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="bg-white rounded-xl shadow-brand p-4">
                    <h6 class="fw-bold mb-3">Course Stats</h6>
                    <div class="row g-2 text-center">
                        <div class="col-6">
                            <div style="font-size:1.4rem;font-weight:700;color:var(--primary);">{{ $course->enrollments->count() }}</div>
                            <div class="text-muted" style="font-size:.75rem;">Students</div>
                        </div>
                        <div class="col-6">
                            <div style="font-size:1.4rem;font-weight:700;color:var(--accent);">{{ number_format($course->avgRating(),1) }}</div>
                            <div class="text-muted" style="font-size:.75rem;">Rating</div>
                        </div>
                        <div class="col-6">
                            <div style="font-size:1.4rem;font-weight:700;color:var(--primary);">{{ $course->modules->count() }}</div>
                            <div class="text-muted" style="font-size:.75rem;">Modules</div>
                        </div>
                        <div class="col-6">
                            <div style="font-size:1.4rem;font-weight:700;color:var(--primary);">{{ $course->modules->sum(fn($m) => $m->lessons->count()) }}</div>
                            <div class="text-muted" style="font-size:.75rem;">Lessons</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Curriculum Tab -->
    <div class="tab-pane fade" id="tab-curriculum">
        <div class="row g-4">
            <div class="col-lg-8">
                <!-- Existing Modules -->
                @foreach($course->modules as $module)
                <div class="bg-white rounded-xl shadow-brand mb-3">
                    <div class="p-3 border-bottom d-flex justify-content-between align-items-center"
                         style="background:var(--primary);color:white;border-radius:12px 12px 0 0;">
                        <strong style="font-size:.9rem;">{{ $module->title() }}</strong>
                        <form method="POST" action="{{ route('instructor.modules.destroy', $module) }}">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-danger" onclick="return confirm('Delete this module and all its lessons?')">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </div>
                    <div class="p-3">
                        @foreach($module->lessons as $lesson)
                        <div class="d-flex align-items-center gap-2 mb-2 p-2 bg-light rounded">
                            <i class="bi {{ match($lesson->type) {'video'=>'bi-play-circle','text'=>'bi-file-text','quiz'=>'bi-question-circle','assignment'=>'bi-clipboard',default=>'bi-circle'} }} text-primary"></i>
                            <span style="font-size:.85rem;flex-grow:1;">{{ $lesson->title() }}</span>
                            @if($lesson->is_free_preview)
                            <span class="badge bg-success" style="font-size:.7rem;">Free Preview</span>
                            @endif
                            <form method="POST" action="{{ route('instructor.lessons.destroy', $lesson) }}">
                                @csrf @method('DELETE')
                                <button class="btn btn-outline-danger btn-sm py-0">&times;</button>
                            </form>
                        </div>
                        @endforeach
                        <!-- Add Lesson Form -->
                        <form method="POST" action="{{ route('instructor.courses.lessons.store', $course) }}"
                              class="mt-3 p-3 border rounded bg-white">
                            @csrf
                            <input type="hidden" name="module_id" value="{{ $module->id }}">
                            <div class="row g-2">
                                <div class="col-12">
                                    <input type="text" name="title" class="form-control form-control-sm"
                                           placeholder="Lesson title (EN)" required>
                                </div>
                                <div class="col-md-4">
                                    <select name="type" class="form-select form-select-sm">
                                        <option value="video">Video</option>
                                        <option value="text">Text/Article</option>
                                        <option value="quiz">Quiz</option>
                                        <option value="assignment">Assignment</option>
                                    </select>
                                </div>
                                <div class="col-md-8">
                                    <input type="text" name="video_url" class="form-control form-control-sm"
                                           placeholder="Video URL (YouTube/Vimeo or direct MP4)">
                                </div>
                                <div class="col-12">
                                    <textarea name="content" class="form-control form-control-sm" rows="2"
                                              placeholder="Lesson content / description"></textarea>
                                </div>
                                <div class="col-auto">
                                    <div class="form-check form-check-sm">
                                        <input type="checkbox" class="form-check-input" name="is_free_preview">
                                        <label class="form-check-label small">Free Preview</label>
                                    </div>
                                </div>
                                <div class="col-auto ms-auto">
                                    <button class="btn btn-primary btn-sm">Add Lesson</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                @endforeach

                <!-- Add Module Form -->
                <div class="bg-white rounded-xl shadow-brand p-4">
                    <h6 class="fw-bold mb-3">Add New Module</h6>
                    <form method="POST" action="{{ route('instructor.courses.modules.store', $course) }}">
                        @csrf
                        <div class="row g-2">
                            <div class="col-12">
                                <input type="text" name="title" class="form-control"
                                       placeholder="Module title (EN)" required>
                            </div>
                            <div class="col-12">
                                <input type="text" name="titles[fr]" class="form-control"
                                       placeholder="Module title (French)">
                            </div>
                            <div class="col-auto ms-auto">
                                <button class="btn btn-primary">Add Module</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
