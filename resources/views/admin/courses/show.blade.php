@extends('layouts.admin')
@section('title', $course->title())
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.courses.index') }}">{{ __('Courses') }}</a></li>
    <li class="breadcrumb-item active">{{ Str::limit($course->title(), 30) }}</li>
@endsection

@section('content')
<div class="row g-4">
    <div class="col-lg-4">
        <div class="bg-white rounded-xl shadow-brand p-4">
            <img src="{{ $course->thumbnailUrl() }}" alt="{{ e($course->title()) }}"
                 class="img-fluid rounded mb-3" style="height:180px;object-fit:cover;width:100%;">
            <h5>{{ $course->title() }}</h5>
            <p class="text-muted small">{{ __('by :name', ['name' => $course->instructor?->name]) }}</p>
            <div class="d-flex gap-2 flex-wrap mb-3">
                <span class="badge {{ match($course->status) {'published'=>'bg-success','pending'=>'bg-warning text-dark','rejected'=>'bg-danger',default=>'bg-secondary'} }}">
                    {{ __('lms.status_' . $course->status) }}
                </span>
                @if($course->featured)<span class="badge bg-info text-dark"><x-icon name="star-fill" class="me-1" />{{ __('Featured') }}</span>@endif
            </div>

            <!-- Admin Feedback (for rejection) -->
            @if($course->status === 'pending')
            <form method="POST" action="{{ route('admin.courses.approve', $course) }}" class="mb-2">
                @csrf
                <button class="btn btn-success w-100 mb-2"><x-icon name="check-circle-fill" class="me-1" />{{ __('Approve & Publish') }}</button>
            </form>
            <form method="POST" action="{{ route('admin.courses.reject', $course) }}">
                @csrf
                <div class="mb-2">
                    <textarea name="feedback" class="form-control form-control-sm" rows="2"
                              placeholder="{{ __('Reason for rejection...') }}" required></textarea>
                </div>
                <button class="btn btn-danger w-100"><x-icon name="x-circle-fill" class="me-1" />{{ __('Reject with Feedback') }}</button>
            </form>
            @endif

            @if($course->status === 'published')
            <form method="POST" action="{{ route('admin.courses.unpublish', $course) }}">
                @csrf
                <button class="btn btn-warning w-100 mt-2" data-confirm="{{ __('Unpublish this course?') }}">{{ __('Unpublish') }}</button>
            </form>
            @endif

            <form method="POST" action="{{ route('admin.courses.feature', $course) }}" class="mt-2">
                @csrf
                <button class="btn btn-outline-secondary w-100">
                    <x-icon :name="$course->featured ? 'star-fill' : 'star'" class="me-1" />{{ $course->featured ? __('Unfeature') : __('Feature') }}
                </button>
            </form>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="bg-white rounded-xl shadow-brand p-4 mb-4">
            <h5 class="mb-3">{{ __('Course Details') }}</h5>
            <div class="row g-3">
                <div class="col-6"><strong>{{ __('Level:') }}</strong><br><span class="text-muted small">{{ __('messages.' . $course->level) }}</span></div>
                <div class="col-6"><strong>{{ __('Price:') }}</strong><br><span class="text-muted small">{{ $course->price == 0 ? __('courses.free') : number_format($course->price, 0, ',', ' ') . ' FCFA' }}</span></div>
                <div class="col-6"><strong>{{ __('Duration:') }}</strong><br><span class="text-muted small">{{ $course->hoursLabel() }}</span></div>
                <div class="col-6"><strong>{{ __('Enrollments:') }}</strong><br><span class="text-muted small">{{ $course->enrollments->count() }}</span></div>
                <div class="col-12"><strong>{{ __('Description (EN):') }}</strong><br>
                    <p class="text-muted small mt-1">{{ $course->description() }}</p>
                </div>
                @if($course->admin_feedback)
                <div class="col-12">
                    <div class="alert alert-warning mb-0">
                        <strong>{{ __('Admin Feedback:') }}</strong> {{ $course->admin_feedback }}
                    </div>
                </div>
                @endif
            </div>
        </div>

        <!-- Curriculum Summary -->
        <div class="bg-white rounded-xl shadow-brand p-4 mb-4">
            <h5 class="mb-3">{{ __('Curriculum') }} ({{ trans_choice('lms.modules_count', $course->modules->count(), ['count' => $course->modules->count()]) }})</h5>
            @foreach($course->modules as $module)
            <div class="mb-2 p-2 bg-light rounded">
                <strong style="font-size:.9rem;">{{ $module->title(app()->getLocale()) }}</strong>
                <span class="text-muted small ms-2">{{ trans_choice('lms.lessons_count', $module->lessons->count(), ['count' => $module->lessons->count()]) }}</span>
                <ul class="mb-0 mt-1 ps-3">
                    @foreach($module->lessons as $lesson)
                    <li style="font-size:.82rem;color:var(--text-muted);">{{ $lesson->title() }}</li>
                    @endforeach
                </ul>
            </div>
            @endforeach
        </div>

        <!-- Reviews -->
        <div class="bg-white rounded-xl shadow-brand p-4">
            <h5 class="mb-3">{{ __('Reviews') }} ({{ $course->reviews->count() }})</h5>
            @forelse($course->reviews->take(5) as $review)
            <div class="d-flex gap-3 mb-3 pb-3 border-bottom">
                <img src="{{ $review->user->avatarUrl() }}" class="rounded-circle" width="36" height="36" alt="{{ __('reviewer') }}">
                <div class="flex-grow-1">
                    <div style="font-size:.85rem;font-weight:600;">{{ $review->user->name }}</div>
                    <div class="stars" style="font-size:.8rem;color:#F59E0B;">
                        @for($s=1;$s<=5;$s++)<x-icon :name="'star' . ($s<=$review->rating?'-fill':'')" />@endfor
                    </div>
                    <p style="font-size:.85rem;margin:.25rem 0;">{{ $review->comment }}</p>
                </div>
                <form method="POST" action="{{ route('admin.reviews.destroy', $review) }}">
                    @csrf @method('DELETE')
                    <button class="btn btn-outline-danger btn-sm"><x-icon name="trash" /></button>
                </form>
            </div>
            @empty
            <p class="text-muted small">{{ __('No reviews yet.') }}</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
