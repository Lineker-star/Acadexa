@extends('layouts.instructor')
@section('title', __('lms.students_and_stats'))
@section('page-title', __('lms.students_and_stats'))

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
    <a href="{{ route('instructor.courses.edit', $course) }}" class="small"><x-icon name="arrow-left" class="me-1" />{{ $course->title() }}</a>
    <a href="{{ route('instructor.students.knowledge', $course) }}" class="btn btn-sm btn-primary"><x-icon name="graph-up-arrow" class="me-1" />{{ __('learn.knowledge_tracking') }}</a>
</div>

<div class="row g-3 my-2">
    @foreach([
        ['label' => __('lms.enrolled'), 'value' => $stats['total'], 'icon' => 'bi-people'],
        ['label' => __('lms.completion_rate'), 'value' => $stats['completion_rate'] . ' %', 'icon' => 'bi-trophy'],
        ['label' => __('lms.avg_progress'), 'value' => $stats['avg_progress'] . ' %', 'icon' => 'bi-graph-up'],
        ['label' => __('lms.avg_quiz'), 'value' => $stats['avg_quiz'] !== null ? $stats['avg_quiz'] . ' %' : '—', 'icon' => 'bi-patch-check'],
    ] as $card)
        <div class="col-6 col-lg-3">
            <div class="bg-white rounded-xl shadow-brand p-3 h-100">
                <div class="small text-muted"><x-icon :name="$card['icon']" class="me-1" />{{ $card['label'] }}</div>
                <div class="fs-4 fw-bold text-primary">{{ $card['value'] }}</div>
            </div>
        </div>
    @endforeach
</div>

<div class="row g-4">
    <div class="col-xl-8">
        <div class="bg-white rounded-xl shadow-brand p-3">
            <form class="d-flex flex-wrap gap-2 mb-3" method="GET">
                <input type="search" name="search" value="{{ request('search') }}" class="form-control form-control-sm" style="max-width:260px" placeholder="{{ __('lms.search_student') }}">
                <select name="filter" class="form-select form-select-sm w-auto">
                    <option value="">{{ __('lms.all') }}</option>
                    <option value="not_started" @selected(request('filter') === 'not_started')>{{ __('lms.not_started') }}</option>
                    <option value="in_progress" @selected(request('filter') === 'in_progress')>{{ __('lms.in_progress') }}</option>
                    <option value="completed" @selected(request('filter') === 'completed')>{{ __('lms.completed') }}</option>
                </select>
                <button class="btn btn-sm btn-outline-primary">{{ __('lms.filter') }}</button>
                <a href="{{ route('instructor.students.export', $course) }}" class="btn btn-sm btn-light ms-auto"><x-icon name="filetype-csv" class="me-1" />{{ __('lms.export_csv') }}</a>
            </form>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="small text-muted"><tr><th>{{ __('lms.student') }}</th><th>{{ __('lms.progress') }}</th><th>{{ __('learn.current_level') }}</th><th>{{ __('lms.last_lesson') }}</th><th>{{ __('lms.enrolled_on') }}</th><th></th></tr></thead>
                    <tbody>
                    @forelse($enrollments as $e)
                        <tr>
                            <td><div class="fw-semibold">{{ $e->user->name }}</div><div class="small text-muted">{{ $e->user->email }}</div></td>
                            <td style="min-width:140px">
                                <div class="progress" style="height:6px"><div class="progress-bar {{ $e->progress_percent >= 100 ? 'bg-success' : '' }}" style="width:{{ $e->progress_percent }}%"></div></div>
                                <div class="small text-muted">{{ (float) $e->progress_percent }} % · {{ $e->lesson_progress_count }} {{ __('lms.lessons') }}</div>
                            </td>
                            <td class="small">@include('instructor.students.partials.level-cell', ['p' => $profiles[$e->id]])</td>
                            <td class="small">{{ \Illuminate\Support\Str::limit($e->lastLesson?->title() ?? '—', 35) }}</td>
                            <td class="small">{{ $e->enrolled_at?->isoFormat('L') }}</td>
                            <td class="text-end"><a href="{{ route('instructor.students.show', [$course, $e]) }}" class="btn btn-sm btn-outline-primary">{{ __('lms.details') }}</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-5">{{ __('lms.no_students') }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $enrollments->links() }}</div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="bg-white rounded-xl shadow-brand p-4">
            <h6 class="fw-bold mb-1">{{ __('lms.completion_by_lesson') }}</h6>
            <p class="small text-muted">{{ __('lms.completion_by_lesson_help') }}</p>
            @forelse($stats['funnel'] as $row)
                <div class="mb-2">
                    <div class="d-flex justify-content-between small"><span class="text-truncate me-2">{{ $loop->iteration }}. {{ $row['title'] }}</span><strong>{{ $row['percent'] }} %</strong></div>
                    <div class="progress" style="height:5px"><div class="progress-bar" style="width:{{ $row['percent'] }}%"></div></div>
                </div>
            @empty
                <p class="small text-muted mb-0">—</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
