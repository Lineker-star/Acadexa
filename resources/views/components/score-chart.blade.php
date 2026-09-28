{{-- Score evolution (0–100 %) as an inline SVG line chart. $points: list of ['label' => string, 'score' => float, 'mode' => string]. --}}
@props(['points' => [], 'pass' => config('lms.assessment.pass_percent'), 'height' => 180])
@php
    $points = array_values(collect($points)->all());
    $n = count($points);
    $w = 600; $h = 200; $padL = 34; $padR = 14; $padT = 12; $padB = 30;
    $x = fn ($i) => $n > 1 ? $padL + $i * ($w - $padL - $padR) / ($n - 1) : $padL + ($w - $padL - $padR) / 2;
    $y = fn ($v) => $padT + (100 - max(0, min(100, $v))) * ($h - $padT - $padB) / 100;
    $line = collect($points)->map(fn ($p, $i) => round($x($i), 1) . ',' . round($y($p['score']), 1))->implode(' ');
    $colors = ['diagnostic' => '#6366F1', 'standard' => '#0A2A5E', 'retake' => '#0EA5E9'];
@endphp
<svg viewBox="0 0 {{ $w }} {{ $h }}" role="img" aria-label="{{ __('learn.knowledge_chart') }}" style="width:100%;height:{{ $height }}px" preserveAspectRatio="none" {{ $attributes }}>
    @foreach([0, 25, 50, 75, 100] as $g)
        <line x1="{{ $padL }}" x2="{{ $w - $padR }}" y1="{{ $y($g) }}" y2="{{ $y($g) }}" stroke="#E5E7EB" stroke-width="1" />
        <text x="{{ $padL - 6 }}" y="{{ $y($g) + 4 }}" text-anchor="end" font-size="11" fill="#9CA3AF">{{ $g }}</text>
    @endforeach
    <line x1="{{ $padL }}" x2="{{ $w - $padR }}" y1="{{ $y($pass) }}" y2="{{ $y($pass) }}" stroke="#10B981" stroke-width="1.5" stroke-dasharray="6 4" />
    @if($n > 1)
        <polyline points="{{ $line }}" fill="none" stroke="#0A2A5E" stroke-width="2.5" stroke-linejoin="round" />
    @endif
    @foreach($points as $i => $p)
        <circle cx="{{ $x($i) }}" cy="{{ $y($p['score']) }}" r="5" fill="{{ $colors[$p['mode'] ?? 'standard'] ?? '#0A2A5E' }}" stroke="#fff" stroke-width="2">
            <title>{{ $p['label'] }} — {{ $p['score'] }} %</title>
        </circle>
        <text x="{{ $x($i) }}" y="{{ $y($p['score']) - 10 }}" text-anchor="middle" font-size="11" font-weight="600" fill="#374151">{{ round($p['score']) }}</text>
        <text x="{{ $x($i) }}" y="{{ $h - 8 }}" text-anchor="middle" font-size="10" fill="#6B7280">{{ $p['label'] }}</text>
    @endforeach
</svg>
