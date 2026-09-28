@extends('layouts.instructor')
@section('title', __('lms.announcements'))
@section('page-title', __('lms.announcements'))

@section('content')
<a href="{{ route('instructor.courses.edit', $course) }}" class="small"><x-icon name="arrow-left" class="me-1" />{{ $course->title() }}</a>

<div class="row g-4 mt-1">
    <div class="col-lg-5">
        <form method="POST" action="{{ route('instructor.announcements.store', $course) }}" class="bg-white rounded-xl shadow-brand p-4">
            @csrf
            <h6 class="fw-bold">{{ __('lms.new_announcement') }}</h6>
            <p class="small text-muted">{{ __('lms.announcement_help') }}</p>
            <input type="text" name="title" class="form-control mb-2 @error('title') is-invalid @enderror" maxlength="255" required placeholder="{{ __('lms.title') }}" value="{{ old('title') }}">
            <textarea name="body" rows="7" class="form-control mb-3 @error('body') is-invalid @enderror" maxlength="10000" required placeholder="{{ __('lms.message') }}">{{ old('body') }}</textarea>
            <button class="btn btn-primary w-100"><x-icon name="megaphone" class="me-1" />{{ __('lms.publish_announcement') }}</button>
        </form>
    </div>
    <div class="col-lg-7">
        @forelse($announcements as $a)
            <div class="bg-white rounded-xl shadow-brand p-4 mb-3">
                <div class="d-flex justify-content-between gap-2">
                    <h6 class="fw-bold mb-1">{{ $a->title }}</h6>
                    <form method="POST" action="{{ route('instructor.announcements.destroy', $a) }}" data-confirm="{{ __('lms.confirm_delete') }}">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-link text-danger p-0" aria-label="{{ __('lms.delete') }}"><x-icon name="trash" /></button>
                    </form>
                </div>
                <div class="small text-muted mb-2">{{ $a->author?->name }} · {{ $a->created_at->diffForHumans() }}</div>
                <div style="white-space:pre-wrap">{{ $a->body }}</div>
            </div>
        @empty
            <div class="text-center text-muted py-5 bg-white rounded-xl shadow-brand">{{ __('lms.no_announcements') }}</div>
        @endforelse
        {{ $announcements->links() }}
    </div>
</div>
@endsection
