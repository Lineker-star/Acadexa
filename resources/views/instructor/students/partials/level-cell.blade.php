{{-- Current knowledge level and trend of one student (final evaluation). --}}
@php
    $trendIcon = ['progress' => ['graph-up-arrow', 'text-success'], 'regression' => ['graph-down-arrow', 'text-danger'], 'stable' => ['arrow-right', 'text-secondary']][$p['trend']] ?? null;
@endphp
@if($p['current'] !== null)
    <span class="fw-semibold">{{ round($p['current']) }} %</span>
    <span class="text-muted">· {{ __('learn.knowledge_level_' . $p['level']) }}</span>
    @if($trendIcon)
        <span class="{{ $trendIcon[1] }} ms-1" title="{{ __('learn.trend_' . $p['trend']) }}"><x-icon :name="$trendIcon[0]" />{{ \App\Support\Format::points($p['delta']) }}</span>
    @endif
@else
    <span class="text-muted">{{ __('learn.not_evaluated') }}</span>
@endif
