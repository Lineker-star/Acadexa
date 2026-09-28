@extends('layouts.app')
@section('title', __('learn.my_results') . ' — ' . $enrollment->course->title())
@section('robots', 'noindex, nofollow')

@php $course = $enrollment->course; $player = route('student.courses.player', $enrollment); @endphp

@section('content')
<div class="container py-5" style="max-width:980px">
    <nav aria-label="{{ __('Breadcrumb') }}" class="mb-2">
        <ol class="breadcrumb small mb-0">
            <li class="breadcrumb-item"><a href="{{ route('student.courses.index') }}">{{ __('navigation.my_courses') }}</a></li>
            <li class="breadcrumb-item active">{{ $course->title() }}</li>
        </ol>
    </nav>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <h1 class="h3 fw-bold mb-0"><x-icon name="graph-up-arrow" class="text-primary me-2" />{{ __('learn.my_results') }}</h1>
        <a href="{{ $player }}" class="btn btn-primary"><x-icon name="play-circle" class="me-1" />{{ __('messages.continue_learning') }}</a>
    </div>

    <div class="bg-white rounded-xl shadow-brand p-4 mb-4">
        <h2 class="h5 fw-bold mb-1">{{ __('learn.knowledge_evolution') }}</h2>
        <p class="small text-muted">{{ __('learn.knowledge_evolution_help') }}</p>

        @if(! $final || ! $final->isReady())
            <div class="alert alert-light border mb-0">{{ __('learn.no_final_evaluation') }}</div>
        @else
            @include('student.courses.partials.knowledge-summary', ['profile' => $profile])

            <div class="d-flex flex-wrap gap-2 mt-3">
                @if($canDiagnose)
                    <a href="{{ $player }}?assessment={{ $final->id }}&mode=diagnostic" class="btn btn-outline-primary btn-sm"><x-icon name="speedometer2" class="me-1" />{{ __('learn.diagnostic_start') }}</a>
                @endif
                @if($retakeAt)
                    @if($retakeAt->isPast())
                        <a href="{{ $player }}?assessment={{ $final->id }}&mode=retake" class="btn btn-primary btn-sm"><x-icon name="arrow-repeat" class="me-1" />{{ __('learn.retake_start') }}</a>
                    @else
                        <span class="small text-muted align-self-center">{{ __('learn.retake_available_on', ['date' => $retakeAt->isoFormat('LL')]) }}</span>
                    @endif
                @elseif(! $canDiagnose)
                    <span class="small text-muted align-self-center">{{ __('learn.retake_after_pass') }}</span>
                @endif
            </div>
        @endif
    </div>

    <div class="row g-4">
        <div class="col-md-7">
            <div class="bg-white rounded-xl shadow-brand p-4 h-100">
                <h2 class="h6 fw-bold mb-3">{{ __('learn.mastery_by_module') }}</h2>
                @foreach($profile['modules'] as $row)
                    @php $value = $row['evaluation'] ?? $row['exercise']; @endphp
                    <div class="mb-3">
                        <div class="d-flex justify-content-between small"><span class="text-truncate me-2">{{ $row['title'] }}</span>
                            <span class="fw-semibold">{{ $value !== null ? round($value) . ' %' : '—' }}</span></div>
                        <div class="progress" style="height:6px"><div class="progress-bar {{ $value !== null && $value < config('lms.assessment.pass_percent') ? 'bg-warning' : 'bg-success' }}" style="width:{{ $value ?? 0 }}%"></div></div>
                        <div class="small text-muted">
                            {{ __('learn.module_exercise') }} : {{ $row['exercise'] !== null ? round($row['exercise']) . ' %' : '—' }}
                            · {{ __('learn.final_evaluation') }} : {{ $row['evaluation'] !== null ? round($row['evaluation']) . ' %' : '—' }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="col-md-5">
            <div class="bg-white rounded-xl shadow-brand p-4 h-100">
                <h2 class="h6 fw-bold mb-3">{{ __('learn.while_studying') }}</h2>
                <dl class="row small mb-0">
                    <dt class="col-8 fw-normal text-muted">{{ __('learn.lesson_quiz_average') }}</dt><dd class="col-4 text-end fw-semibold">{{ $profile['lesson_avg'] !== null ? $profile['lesson_avg'] . ' %' : '—' }}</dd>
                    <dt class="col-8 fw-normal text-muted">{{ __('learn.module_exercise_average') }}</dt><dd class="col-4 text-end fw-semibold">{{ $profile['exercise_avg'] !== null ? $profile['exercise_avg'] . ' %' : '—' }}</dd>
                    <dt class="col-8 fw-normal text-muted">{{ __('lms.progress') }}</dt><dd class="col-4 text-end fw-semibold">{{ (float) $enrollment->progress_percent }} %</dd>
                </dl>
            </div>
        </div>
    </div>

    @if($profile['evaluations']->isNotEmpty())
    <div class="bg-white rounded-xl shadow-brand p-4 mt-4">
        <h2 class="h6 fw-bold mb-3">{{ __('learn.evaluation_history') }}</h2>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead><tr><th>{{ __('learn.date') }}</th><th>{{ __('learn.type') }}</th><th class="text-end">{{ __('lms.score') }}</th><th>{{ __('learn.result') }}</th></tr></thead>
                <tbody>
                @foreach($profile['evaluations']->reverse() as $e)
                    <tr>
                        <td>{{ $e['date']?->isoFormat('LL') }}</td>
                        <td>{{ __('learn.mode_' . $e['mode']) }}</td>
                        <td class="text-end fw-semibold">{{ $e['score'] }} %</td>
                        <td>{{ \App\Services\KnowledgeService::level($e['score']) ? __('learn.knowledge_level_' . \App\Services\KnowledgeService::level($e['score'])) : '' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@endsection
