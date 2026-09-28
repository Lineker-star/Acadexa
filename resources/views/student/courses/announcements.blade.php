@extends('layouts.app')
@section('title', __('lms.announcements') . ' — ' . $course->title())

@section('content')
<div class="container py-5" style="max-width:860px">
    @if($enrolled)
        <a href="{{ route('student.courses.player', $enrolled) }}" class="small"><x-icon name="arrow-left" class="me-1" />{{ __('lms.back_to_course') }}</a>
    @endif
    <h2 class="fw-bold mt-2 mb-1">{{ __('lms.announcements') }}</h2>
    <p class="text-muted mb-4">{{ $course->title() }}</p>

    @forelse($announcements as $a)
        <article class="bg-white rounded-xl shadow-brand p-4 mb-3">
            <h5 class="fw-bold mb-1">{{ $a->title }}</h5>
            <div class="small text-muted mb-3">{{ $a->author?->name }} · {{ $a->created_at->translatedFormat('d F Y, H:i') }}</div>
            <div style="white-space:pre-wrap">{{ $a->body }}</div>
        </article>
    @empty
        <div class="text-center text-muted py-5">{{ __('lms.no_announcements') }}</div>
    @endforelse
    {{ $announcements->links() }}
</div>
@endsection
