@extends('layouts.instructor')
@section('title', __('lms.assignments'))
@section('page-title', __('lms.assignments'))

@section('content')
<form class="d-flex flex-wrap gap-2 mb-3" method="GET">
    <div class="btn-group btn-group-sm" role="group">
        @foreach(['submitted' => __('lms.to_grade'), 'graded' => __('lms.graded'), 'all' => __('lms.all')] as $key => $label)
            <a href="{{ request()->fullUrlWithQuery(['status' => $key, 'page' => null]) }}" class="btn {{ $status === $key ? 'btn-primary' : 'btn-outline-primary' }}">{{ $label }}</a>
        @endforeach
    </div>
    <select name="course" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
        <option value="">{{ __('lms.all_courses') }}</option>
        @foreach($courses as $c)
            <option value="{{ $c->id }}" @selected(request('course') == $c->id)>{{ \Illuminate\Support\Str::limit($c->title(), 50) }}</option>
        @endforeach
    </select>
    <input type="hidden" name="status" value="{{ $status }}">
</form>

<div class="bg-white rounded-xl shadow-brand">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="small text-muted">
                <tr><th>{{ __('lms.student') }}</th><th>{{ __('lms.lesson') }}</th><th>{{ __('lms.submitted_on') }}</th><th>{{ __('lms.status') }}</th><th></th></tr>
            </thead>
            <tbody>
            @forelse($submissions as $s)
                <tr>
                    <td><div class="fw-semibold">{{ $s->user->name }}</div><div class="small text-muted">{{ $s->user->email }}</div></td>
                    <td><div>{{ $s->lesson->title() }}</div><div class="small text-muted">{{ \Illuminate\Support\Str::limit($s->lesson->module->course->title(), 45) }}</div></td>
                    <td class="small">{{ $s->submitted_at?->isoFormat('L LT') }}</td>
                    <td>
                        @if($s->isGraded())
                            <span class="badge {{ $s->isPassed() ? 'bg-success' : 'bg-danger' }}">{{ rtrim(rtrim((string) $s->score, '0'), '.') }} / {{ $s->lesson->assignment_max_score }}</span>
                        @else
                            <span class="badge bg-warning text-dark">{{ __('lms.to_grade') }}</span>
                        @endif
                    </td>
                    <td class="text-end"><a href="{{ route('instructor.submissions.show', $s) }}" class="btn btn-sm btn-outline-primary">{{ $s->isGraded() ? __('lms.view') : __('lms.grade') }}</a></td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-5"><x-icon name="inbox" class="fs-3 d-block mb-2" />{{ __('lms.no_submissions') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $submissions->links() }}</div>
@endsection
