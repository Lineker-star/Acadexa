@extends('layouts.admin')
@section('title', __('security.reports'))
@section('page-title', __('security.reports'))

@php $max = max(1, $rows->max(fn ($r) => max($r['registrations'], $r['enrollments']))); @endphp

@section('content')
<div class="row g-3 mb-4">
    @foreach([
        ['label' => __('security.total_students'), 'value' => $totals['students']],
        ['label' => __('security.active_30d'), 'value' => $totals['active_30d']],
        ['label' => __('security.total_enrollments'), 'value' => $totals['enrollments']],
        ['label' => __('security.completion_rate'), 'value' => $totals['completion'] . ' %'],
    ] as $card)
        <div class="col-6 col-lg-3"><div class="bg-white rounded-xl shadow-brand p-3">
            <div class="small text-muted">{{ $card['label'] }}</div><div class="fs-4 fw-bold text-primary">{{ $card['value'] }}</div>
        </div></div>
    @endforeach
</div>

<div class="bg-white rounded-xl shadow-brand p-4 mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h6 class="fw-bold mb-0">{{ __('security.last_12_months') }}</h6>
        <div class="d-flex flex-wrap gap-2">
            @foreach(['users', 'enrollments', 'certificates'] as $type)
                <a href="{{ route('admin.reports.export', $type) }}" class="btn btn-sm btn-outline-primary"><x-icon name="filetype-csv" class="me-1" />{{ __('security.export_' . $type) }}</a>
            @endforeach
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead class="small text-muted"><tr>
                <th>{{ __('security.month') }}</th><th>{{ __('security.registrations') }}</th><th>{{ __('security.enrollments') }}</th>
                <th>{{ __('security.completions') }}</th><th>{{ __('security.certificates') }}</th>
            </tr></thead>
            <tbody>
            @foreach($rows as $r)
                <tr>
                    <td class="small text-nowrap">{{ $r['label'] }}</td>
                    <td style="min-width:140px"><div class="d-flex align-items-center gap-2"><div class="bg-primary rounded" style="height:8px;width:{{ $r['registrations'] / $max * 100 }}%;min-width:2px"></div><span class="small">{{ $r['registrations'] }}</span></div></td>
                    <td style="min-width:140px"><div class="d-flex align-items-center gap-2"><div class="rounded" style="background:var(--bs-secondary);height:8px;width:{{ $r['enrollments'] / $max * 100 }}%;min-width:2px"></div><span class="small">{{ $r['enrollments'] }}</span></div></td>
                    <td class="small">{{ $r['completions'] }}</td>
                    <td class="small">{{ $r['certificates'] }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="bg-white rounded-xl shadow-brand p-4">
    <h6 class="fw-bold mb-3">{{ __('security.top_courses') }}</h6>
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead class="small text-muted"><tr><th>{{ __('lms.course') }}</th><th>{{ __('security.enrollments') }}</th><th>{{ __('security.completions') }}</th><th>{{ __('lms.rating') }}</th></tr></thead>
            <tbody>
            @foreach($topCourses as $c)
                <tr>
                    <td><a href="{{ route('admin.courses.show', $c) }}">{{ $c->title() }}</a></td>
                    <td>{{ $c->enrollments_count }}</td>
                    <td>{{ $c->completed_count }} @if($c->enrollments_count)<span class="text-muted small">({{ round($c->completed_count / $c->enrollments_count * 100) }} %)</span>@endif</td>
                    <td>{{ number_format((float) $c->reviews_avg_rating, 1) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
