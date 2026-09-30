@extends('layouts.admin')
@section('title', __('Courses'))
@section('breadcrumb') <li class="breadcrumb-item active">{{ __('Courses') }}</li> @endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">{{ __('Course Management') }}</h4>
</div>

<!-- Filters -->
<div class="bg-white rounded-xl shadow-brand p-3 mb-4">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-5">
            <input type="text" name="search" class="form-control form-control-sm"
                   placeholder="{{ __('Search courses...') }}" value="{{ request('search') }}">
        </div>
        <div class="col-md-3">
            <select name="status" class="form-select form-select-sm">
                <option value="">{{ __('All Status') }}</option>
                @foreach(['draft','pending','published','rejected','unpublished'] as $s)
                <option value="{{ $s }}" {{ request('status') == $s ? 'selected' : '' }}>{{ __('lms.status_' . $s) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <button class="btn btn-primary btn-sm w-100">{{ __('Filter') }}</button>
        </div>
        <div class="col-md-2">
            <a href="{{ route('admin.courses.index') }}" class="btn btn-outline-secondary btn-sm w-100">{{ __('Reset') }}</a>
        </div>
    </form>
</div>

<div class="bg-white rounded-xl shadow-brand overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr><th>{{ __('Course') }}</th><th>{{ __('Instructor') }}</th><th>{{ __('Status') }}</th><th>{{ __('Enrollments') }}</th><th>{{ __('Rating') }}</th><th>{{ __('Actions') }}</th></tr>
            </thead>
            <tbody>
                @forelse($courses as $course)
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <img src="{{ $course->thumbnailUrl() }}" class="rounded" width="48" height="36" style="object-fit:cover;" alt="{{ $course->title() }}">
                            <div>
                                <div style="font-size:.88rem;font-weight:600;">{{ Str::limit($course->title(), 40) }}</div>
                                <div style="font-size:.75rem;color:var(--text-muted);">{{ __('messages.' . $course->level) }} • {{ $course->hoursLabel() }}</div>
                            </div>
                        </div>
                    </td>
                    <td style="font-size:.85rem;">{{ $course->instructor?->name }}</td>
                    <td>
                        <span class="badge {{ match($course->status) {
                            'published' => 'bg-success',
                            'pending'   => 'bg-warning text-dark',
                            'rejected'  => 'bg-danger',
                            'draft'     => 'bg-secondary',
                            default     => 'bg-secondary'
                        } }}">{{ __('lms.status_' . $course->status) }}</span>
                        @if($course->featured)<span class="badge bg-info text-dark"><x-icon name="star-fill" class="me-1" />{{ __('Featured') }}</span>@endif
                    </td>
                    <td style="font-size:.85rem;">{{ $course->enrollments_count }}</td>
                    <td style="font-size:.85rem;">{{ $course->avgRating() }}/5</td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('admin.courses.show', $course) }}" class="btn btn-primary btn-sm"><x-icon name="eye" /></a>
                            @if($course->status !== 'published')
                            <form method="POST" action="{{ route('admin.courses.approve', $course) }}" data-confirm="{{ __('learn.publish_confirm') }}">
                                @csrf
                                <button class="btn btn-success btn-sm" title="{{ $course->status === 'pending' ? __('Approve') : __('learn.publish_now') }}"><x-icon name="check" /></button>
                            </form>
                            @endif
                            <form method="POST" action="{{ route('admin.courses.feature', $course) }}">
                                @csrf
                                <button class="btn btn-{{ $course->featured ? 'warning' : 'outline-warning' }} btn-sm" title="{{ __('Feature') }}">
                                    <x-icon :name="'star' . ($course->featured ? '-fill' : '')" />
                                </button>
                            </form>
                            <form method="POST" action="{{ route('admin.courses.destroy', $course) }}">
                                @csrf @method('DELETE')
                                <button class="btn btn-outline-danger btn-sm" data-confirm="{{ __('Delete this course?') }}"><x-icon name="trash" /></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center py-4 text-muted">{{ __('No courses found.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $courses->links() }}</div>
@endsection
