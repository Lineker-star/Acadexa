{{-- Knowledge level measured with the final evaluation: start, now, trend. --}}
@php
    $compact ??= false;
    $trendMeta = [
        'progress'   => ['graph-up-arrow', 'text-success', __('learn.trend_progress')],
        'regression' => ['graph-down-arrow', 'text-danger', __('learn.trend_regression')],
        'stable'     => ['arrow-right', 'text-secondary', __('learn.trend_stable')],
        'single'     => ['dot', 'text-secondary', __('learn.trend_single')],
        'none'       => ['dash', 'text-muted', __('learn.trend_none')],
    ][$profile['trend']];
@endphp
<div class="row g-2 mb-3 text-center">
    <div class="col-6 col-md-3">
        <div class="border rounded-3 p-2 h-100 bg-white">
            <div class="small text-muted">{{ $profile['baseline_is_diagnostic'] ? __('learn.starting_level') : __('learn.first_evaluation') }}</div>
            <div class="fs-5 fw-bold">{{ $profile['baseline'] !== null ? round($profile['baseline']) . ' %' : '—' }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="border rounded-3 p-2 h-100 bg-white">
            <div class="small text-muted">{{ __('learn.current_level') }}</div>
            <div class="fs-5 fw-bold">{{ $profile['current'] !== null ? round($profile['current']) . ' %' : '—' }}</div>
            @if($profile['level'])<div class="small">{{ __('learn.knowledge_level_' . $profile['level']) }}</div>@endif
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="border rounded-3 p-2 h-100 bg-white">
            <div class="small text-muted">{{ __('learn.knowledge_gain') }}</div>
            <div class="fs-5 fw-bold {{ ($profile['gain'] ?? 0) < 0 ? 'text-danger' : 'text-success' }}">
                {{ \App\Support\Format::points($profile['gain']) }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="border rounded-3 p-2 h-100 bg-white">
            <div class="small text-muted">{{ __('learn.trend') }}</div>
            <div class="fs-6 fw-bold {{ $trendMeta[1] }}"><x-icon :name="$trendMeta[0]" class="me-1" />{{ $trendMeta[2] }}</div>
            @if($profile['delta'] !== null)<div class="small text-muted">{{ \App\Support\Format::points($profile['delta']) }}</div>@endif
        </div>
    </div>
</div>
@if(! $compact && $profile['evaluations']->count() > 0)
    <x-score-chart :points="$profile['evaluations']->map(fn ($e) => ['label' => $e['date']?->isoFormat('D MMM'), 'score' => $e['score'], 'mode' => $e['mode']])" />
    <div class="small text-muted d-flex flex-wrap gap-3 mt-1">
        <span><span class="legend-dot" style="background:#6366F1"></span>{{ __('learn.mode_diagnostic') }}</span>
        <span><span class="legend-dot" style="background:#0A2A5E"></span>{{ __('learn.mode_standard') }}</span>
        <span><span class="legend-dot" style="background:#0EA5E9"></span>{{ __('learn.mode_retake') }}</span>
        <span><span class="legend-dot" style="background:#10B981"></span>{{ __('learn.pass_line', ['score' => config('lms.assessment.pass_percent')]) }}</span>
    </div>
@endif
