@extends('layouts.instructor')
@section('title', __('Instructor Dashboard'))

@section('content')
<div class="mb-4">
    <h4 class="fw-bold">{{ __('Welcome back, :name!', ['name' => auth()->user()->name]) }}</h4>
    <p class="text-muted mb-0">{{ __('Manage your courses and track student progress from here.') }}</p>
</div>

@if($pendingSubmissions || $unansweredQuestions)
<div class="row g-3 mb-4">
    @if($pendingSubmissions)
    <div class="col-md-6">
        <a href="{{ route('instructor.submissions.index') }}" class="d-flex align-items-center gap-3 bg-white rounded-xl shadow-brand p-3 text-decoration-none border-start border-4 border-warning">
            <x-icon name="clipboard-check" class="fs-3 text-warning" />
            <div><div class="fw-bold text-dark">{{ trans_choice('lms.todo_submissions', $pendingSubmissions, ['count' => $pendingSubmissions]) }}</div>
                 <div class="small text-muted">{{ __('lms.todo_submissions_help') }}</div></div>
        </a>
    </div>
    @endif
    @if($unansweredQuestions)
    <div class="col-md-6">
        <a href="{{ route('instructor.qa.index') }}" class="d-flex align-items-center gap-3 bg-white rounded-xl shadow-brand p-3 text-decoration-none border-start border-4 border-info">
            <x-icon name="chat-dots" class="fs-3 text-info" />
            <div><div class="fw-bold text-dark">{{ trans_choice('lms.todo_questions', $unansweredQuestions, ['count' => $unansweredQuestions]) }}</div>
                 <div class="small text-muted">{{ __('lms.todo_questions_help') }}</div></div>
        </a>
    </div>
    @endif
</div>
@endif

<!-- Stat Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="bg-white rounded-xl shadow-brand p-3 text-center">
            <div style="font-size:1.6rem;font-weight:700;color:var(--primary);">{{ $totalCourses }}</div>
            <div class="text-muted small">{{ __('My Courses') }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="bg-white rounded-xl shadow-brand p-3 text-center">
            <div style="font-size:1.6rem;font-weight:700;color:var(--primary);">{{ $publishedCourses }}</div>
            <div class="text-muted small">{{ __('Published') }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="bg-white rounded-xl shadow-brand p-3 text-center">
            <div style="font-size:1.6rem;font-weight:700;color:var(--accent);">{{ $totalStudents }}</div>
            <div class="text-muted small">{{ __('Total Students') }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="bg-white rounded-xl shadow-brand p-3 text-center">
            <div style="font-size:1.6rem;font-weight:700;color:#F59E0B;">{{ number_format($avgRating ?? 0, 1) }} <x-icon name="star-fill" /></div>
            <div class="text-muted small">{{ __('Avg Rating') }}</div>
        </div>
    </div>
</div>

@if($knowledgeRows->isNotEmpty())
<div class="bg-white rounded-xl shadow-brand p-4 mb-4">
    <h5 class="mb-1"><x-icon name="graph-up-arrow" class="text-primary me-1" />{{ __('learn.students_knowledge') }}</h5>
    <p class="small text-muted">{{ __('learn.students_knowledge_help') }}</p>
    <div class="row g-4">
        <div class="{{ $regressions->isNotEmpty() ? 'col-lg-7' : 'col-12' }}">
            <div class="table-responsive">
                <table class="table table-sm align-middle small mb-0">
                    <thead class="text-muted"><tr>
                        <th>{{ __('lms.course') }}</th>
                        <th class="text-end">{{ __('learn.evaluated_students') }}</th>
                        <th class="text-end">{{ __('learn.average_level') }}</th>
                        <th class="text-end">{{ __('learn.average_gain') }}</th>
                        <th class="text-center"><x-icon name="graph-up-arrow" class="text-success" /> / <x-icon name="graph-down-arrow" class="text-danger" /></th>
                        <th></th>
                    </tr></thead>
                    <tbody>
                    @foreach($knowledgeRows as $row)
                        @php $k = $row['summary']; @endphp
                        <tr>
                            <td class="fw-semibold">{{ \Illuminate\Support\Str::limit($row['course']->title(), 40) }}</td>
                            <td class="text-end">{{ $k['evaluated'] }} / {{ $k['students'] }}</td>
                            <td class="text-end">{{ $k['avg_current'] !== null ? $k['avg_current'] . ' %' : '—' }}</td>
                            <td class="text-end fw-semibold {{ ($k['avg_gain'] ?? 0) < 0 ? 'text-danger' : 'text-success' }}">{{ \App\Support\Format::points($k['avg_gain']) }}</td>
                            <td class="text-center"><span class="text-success">{{ $k['progress'] }}</span> / <span class="text-danger">{{ $k['regression'] }}</span></td>
                            <td class="text-end"><a href="{{ route('instructor.students.knowledge', $row['course']) }}" class="btn btn-sm btn-outline-primary">{{ __('lms.details') }}</a></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @if($regressions->isNotEmpty())
        <div class="col-lg-5">
            <h6 class="fw-bold small text-danger"><x-icon name="exclamation-triangle" class="me-1" />{{ __('learn.students_in_regression') }}</h6>
            <ul class="list-unstyled small mb-0">
                @foreach($regressions as $r)
                    <li class="d-flex align-items-center gap-2 py-1 border-bottom">
                        <a href="{{ route('instructor.students.show', [$r['course'], $r['enrollment']]) }}" class="flex-grow-1 text-truncate">{{ $r['enrollment']->user?->name }}</a>
                        <span class="text-muted text-truncate" style="max-width:40%">{{ $r['course']->title() }}</span>
                        <span class="text-danger fw-semibold">{{ \App\Support\Format::points($r['profile']['delta']) }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
        @endif
    </div>
</div>
@endif

<div class="row g-4">
    <!-- My Courses -->
    <div class="col-lg-8">
        <div class="bg-white rounded-xl shadow-brand p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">{{ __('My Courses') }}</h5>
                <a href="{{ route('instructor.courses.create') }}" class="btn btn-primary btn-sm">
                    <x-icon name="plus-lg" class="me-1" />{{ __('New Course') }}
                </a>
            </div>
            @forelse($courses as $course)
            <div class="d-flex align-items-center gap-3 mb-3 pb-3 border-bottom">
                <img src="{{ $course->thumbnailUrl() }}" alt="{{ e($course->title()) }}"
                     class="rounded" style="width:56px;height:40px;object-fit:cover;">
                <div class="flex-grow-1">
                    <div style="font-size:.88rem;font-weight:600;">{{ $course->title() }}</div>
                    <div class="text-muted" style="font-size:.75rem;">
                        {{ trans_choice(':count student|:count students', $course->enrollments->count(), ['count' => $course->enrollments->count()]) }}
                        · {{ trans_choice('lms.modules_count', $course->modules->count(), ['count' => $course->modules->count()]) }}
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge {{ match($course->status) {'published'=>'bg-success','pending'=>'bg-warning text-dark','rejected'=>'bg-danger',default=>'bg-secondary'} }}">
                        {{ __('lms.status_' . $course->status) }}
                    </span>
                    <a href="{{ route('instructor.courses.edit', $course) }}" class="btn btn-outline-primary btn-sm">
                        {{ __('Edit') }}
                    </a>
                </div>
            </div>
            @empty
            <div class="text-center py-5 text-muted">
                <x-icon name="collection-play" style="font-size:2rem;" />
                <p class="mt-2">{{ __('No courses yet. Create your first course!') }}</p>
                <a href="{{ route('instructor.courses.create') }}" class="btn btn-primary">{{ __('Create Course') }}</a>
            </div>
            @endforelse
        </div>
    </div>

    <!-- Recent Reviews & Announcements -->
    <div class="col-lg-4">
        <div class="bg-white rounded-xl shadow-brand p-4 mb-3">
            <h6 class="fw-bold mb-3">{{ __('Recent Reviews') }}</h6>
            @forelse($recentReviews as $review)
            <div class="mb-3 pb-3 border-bottom">
                <div class="d-flex gap-1 mb-1" style="color:#F59E0B;font-size:.8rem;">
                    @for($s=1;$s<=5;$s++)<x-icon :name="'star' . ($s<=$review->rating?'-fill':'')" />@endfor
                </div>
                <p class="mb-1" style="font-size:.82rem;">{{ Str::limit($review->comment, 80) }}</p>
                <div class="text-muted" style="font-size:.75rem;">
                    {{ __(':name on :course', ['name' => $review->user?->name, 'course' => Str::limit($review->course?->title(), 30)]) }}
                </div>
            </div>
            @empty
            <p class="text-muted small">{{ __('No reviews yet.') }}</p>
            @endforelse
        </div>

        <div class="bg-white rounded-xl shadow-brand p-4">
            <h6 class="fw-bold mb-3">{{ __('Announcements') }}</h6>
            @forelse($announcements as $ann)
            <div class="mb-2 pb-2 border-bottom">
                <div style="font-size:.85rem;font-weight:600;">{{ $ann->title() }}</div>
                <div class="text-muted" style="font-size:.75rem;">{{ $ann->created_at->diffForHumans() }}</div>
            </div>
            @empty
            <p class="text-muted small">{{ __('No announcements.') }}</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
