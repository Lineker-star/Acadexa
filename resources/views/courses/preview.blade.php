@extends('layouts.app')
@section('title', __('lms.preview') . ' — ' . $lesson->title())

@php $kind = $lesson->videoKind(); @endphp

@section('content')
<div class="container py-4" style="max-width:960px">
    <a href="{{ route('courses.show', $course->slug) }}" class="small"><x-icon name="arrow-left" class="me-1" />{{ $course->title() }}</a>
    <div class="d-flex align-items-center gap-2 mt-2 mb-3">
        <span class="badge bg-success">{{ __('lms.free_preview') }}</span>
        <h1 class="h4 fw-bold mb-0">{{ $lesson->title() }}</h1>
    </div>

    @if($lesson->type === 'video' && $lesson->hasVideo())
        <div class="ratio ratio-16x9 rounded-xl overflow-hidden bg-dark mb-4">
            @if($kind === 'upload')
                <video controls playsinline preload="metadata" controlsList="nodownload" src="{{ route('media.lesson.video', $lesson) }}"></video>
            @elseif($kind === 'youtube')
                <iframe src="https://www.youtube-nocookie.com/embed/{{ $lesson->youtubeId() }}?rel=0&modestbranding=1&playsinline=1" title="{{ $lesson->title() }}"
                        allow="accelerometer; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
            @elseif($kind === 'vimeo')
                <iframe src="https://player.vimeo.com/video/{{ $lesson->vimeoId() }}?dnt=1" title="{{ $lesson->title() }}" allow="fullscreen; picture-in-picture" allowfullscreen></iframe>
            @else
                <video controls playsinline preload="metadata" src="{{ $lesson->video_url }}"></video>
            @endif
        </div>
    @endif

    @if($lesson->renderedBody())
        <div class="lesson-content bg-white rounded-xl shadow-brand p-4 mb-4">{!! $lesson->renderedBody() !!}</div>
    @endif

    <div class="bg-white rounded-xl shadow-brand p-4 d-flex flex-wrap align-items-center gap-3">
        <div class="flex-grow-1">
            <div class="fw-bold">{{ __('lms.preview_cta_title') }}</div>
            <div class="small text-muted">{{ $course->hoursLabel() }} · {{ __('lms.preview_cta_help') }}</div>
        </div>
        @auth
            <form method="POST" action="{{ route('student.enroll', $course) }}">@csrf<button class="btn btn-primary">{{ __('messages.enroll_now') }}</button></form>
        @else
            <a href="{{ route('register') }}" class="btn btn-primary">{{ __('messages.get_started') }}</a>
        @endauth
    </div>
</div>
@endsection
