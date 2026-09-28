@extends('layouts.instructor')
@section('title', __('learn.knowledge_tracking') . ' — ' . $course->title())
@section('page-title', __('learn.knowledge_tracking'))

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
    <a href="{{ route('instructor.students.index', $course) }}" class="small"><x-icon name="arrow-left" class="me-1" />{{ $course->title() }} — {{ __('lms.students') }}</a>
    <a href="{{ route('instructor.students.export', $course) }}" class="btn btn-sm btn-light"><x-icon name="filetype-csv" class="me-1" />{{ __('lms.export_csv') }}</a>
</div>
<p class="small text-muted">{{ __('learn.knowledge_tracking_help') }}</p>

@if(! $course->finalExam || ! $course->finalExam->isReady())
    <div class="alert alert-warning d-flex flex-wrap align-items-center gap-2">
        <x-icon name="exclamation-triangle" />
        <span class="flex-grow-1">{{ __('learn.final_needed_for_tracking', ['min' => config('lms.assessment.min_questions.course')]) }}</span>
        <a href="{{ route('instructor.assessments.final', $course) }}" class="btn btn-sm btn-warning">{{ __('learn.edit_final_evaluation') }}</a>
    </div>
@endif

<div class="row g-3 mb-3">
    @foreach([
        ['label' => __('learn.evaluated_students'), 'value' => $summary['evaluated'] . ' / ' . $summary['students'], 'icon' => 'people', 'class' => 'text-primary'],
        ['label' => __('learn.average_level'), 'value' => $summary['avg_current'] !== null ? $summary['avg_current'] . ' %' : '—', 'icon' => 'speedometer2', 'class' => 'text-primary'],
        ['label' => __('learn.average_gain'), 'value' => \App\Support\Format::points($summary['avg_gain']), 'icon' => 'graph-up', 'class' => ($summary['avg_gain'] ?? 0) < 0 ? 'text-danger' : 'text-success'],
        ['label' => __('learn.in_progression') . ' / ' . __('learn.in_regression'), 'value' => $summary['progress'] . ' / ' . $summary['regression'], 'icon' => 'arrow-down-up', 'class' => 'text-primary'],
    ] as $card)
        <div class="col-6 col-lg-3">
            <div class="bg-white rounded-xl shadow-brand p-3 h-100">
                <div class="small text-muted"><x-icon :name="$card['icon']" class="me-1" />{{ $card['label'] }}</div>
                <div class="fs-4 fw-bold {{ $card['class'] }}">{{ $card['value'] }}</div>
            </div>
        </div>
    @endforeach
</div>

<div class="row g-4">
    <div class="col-xl-8">
        <div class="bg-white rounded-xl shadow-brand p-3">
            <div class="d-flex flex-wrap gap-2 mb-3">
                @foreach(['' => __('lms.all'), 'progress' => __('learn.trend_progress'), 'regression' => __('learn.trend_regression'), 'stable' => __('learn.trend_stable'), 'single' => __('learn.trend_single'), 'none' => __('learn.trend_none')] as $value => $label)
                    <a href="{{ route('instructor.students.knowledge', array_filter(['course' => $course->id, 'trend' => $value])) }}"
                       class="btn btn-sm {{ (string) $trend === (string) $value ? 'btn-primary' : 'btn-outline-secondary' }}">{{ $label }}</a>
                @endforeach
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0 small">
                    <thead class="text-muted">
                        <tr>
                            <th>{{ __('lms.student') }}</th>
                            <th>{{ __('lms.progress') }}</th>
                            <th class="text-end">{{ __('learn.starting_level') }}</th>
                            <th>{{ __('learn.current_level') }}</th>
                            <th class="text-end">{{ __('learn.knowledge_gain') }}</th>
                            <th class="text-end">{{ __('learn.lesson_quiz_average') }}</th>
                            <th class="text-end">{{ __('learn.module_exercise_average') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($enrollments as $e)
                        @php $p = $profiles[$e->id]; @endphp
                        <tr class="{{ $p['trend'] === 'regression' ? 'table-danger' : '' }}">
                            <td><div class="fw-semibold">{{ $e->user->name }}</div><div class="text-muted">{{ $p['last_date']?->isoFormat('LL') }}</div></td>
                            <td style="min-width:90px"><div class="progress" style="height:5px"><div class="progress-bar" style="width:{{ $e->progress_percent }}%"></div></div>{{ (float) $e->progress_percent }} %</td>
                            <td class="text-end">{{ $p['baseline'] !== null ? round($p['baseline']) . ' %' : '—' }}</td>
                            <td>@include('instructor.students.partials.level-cell', ['p' => $p])</td>
                            <td class="text-end fw-semibold {{ ($p['gain'] ?? 0) < 0 ? 'text-danger' : 'text-success' }}">{{ \App\Support\Format::points($p['gain']) }}</td>
                            <td class="text-end">{{ $p['lesson_avg'] !== null ? $p['lesson_avg'] . ' %' : '—' }}</td>
                            <td class="text-end">{{ $p['exercise_avg'] !== null ? $p['exercise_avg'] . ' %' : '—' }}</td>
                            <td class="text-end"><a href="{{ route('instructor.students.show', [$course, $e]) }}" class="btn btn-sm btn-outline-primary">{{ __('lms.details') }}</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-5">{{ __('lms.no_students') }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $enrollments->links() }}</div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="bg-white rounded-xl shadow-brand p-4">
            <h6 class="fw-bold mb-1">{{ __('learn.mastery_by_module') }}</h6>
            <p class="small text-muted">{{ __('learn.mastery_by_module_help') }}</p>
            @foreach($moduleAverages as $row)
                <div class="mb-3">
                    <div class="d-flex justify-content-between small"><span class="text-truncate me-2">{{ $loop->iteration }}. {{ $row['title'] }}</span><strong>{{ $row['avg'] !== null ? $row['avg'] . ' %' : '—' }}</strong></div>
                    <div class="progress" style="height:6px"><div class="progress-bar {{ $row['avg'] !== null && $row['avg'] < config('lms.assessment.pass_percent') ? 'bg-warning' : 'bg-success' }}" style="width:{{ $row['avg'] ?? 0 }}%"></div></div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
