@extends('layouts.app')
@section('title', __('learn.course_books') . ' — ' . $course->title())
@section('robots', 'noindex, nofollow')

@section('content')
<div class="container py-5" style="max-width:980px">
    <nav aria-label="{{ __('Breadcrumb') }}" class="mb-2">
        <ol class="breadcrumb small mb-0">
            <li class="breadcrumb-item"><a href="{{ $enrollment ? route('student.courses.player', $enrollment) : route('courses.show', $course->slug) }}">{{ $course->title() }}</a></li>
            <li class="breadcrumb-item active">{{ __('learn.course_books') }}</li>
        </ol>
    </nav>
    <h1 class="h3 fw-bold mb-2"><x-icon name="book" class="text-primary me-2" />{{ __('learn.course_books') }}</h1>
    <p class="text-muted small mb-4">{{ __('learn.library_help') }}</p>

    @forelse($course->books as $book)
        <div class="library-card mb-2">
            <div class="cover"><x-icon :name="$book->icon()" /></div>
            <div class="flex-grow-1 min-w-0">
                <div class="fw-semibold">{{ $book->title }}</div>
                <div class="small text-muted">{{ $book->author ? $book->author . ' · ' : '' }}{{ $book->sizeLabel() }}</div>
                @if($book->description)<div class="small text-muted mt-1">{{ $book->description }}</div>@endif
            </div>
            <button type="button" class="btn btn-sm btn-light" data-open-media="{{ $book->url() }}" data-media-kind="{{ $book->kind() }}" data-media-title="{{ $book->title }}"><x-icon name="eye" class="me-1" />{{ __('learn.read') }}</button>
            @if(in_array($book->id, $inLibrary, true))
                <a href="{{ route('student.library.index') }}" class="btn btn-sm btn-success"><x-icon name="check2" class="me-1" />{{ __('learn.in_library') }}</a>
            @else
                <form method="POST" action="{{ route('student.library.add', $book) }}">
                    @csrf
                    <button class="btn btn-sm btn-primary"><x-icon name="cloud-arrow-down" class="me-1" />{{ __('learn.add_to_library') }}</button>
                </form>
            @endif
        </div>
    @empty
        <p class="text-muted">{{ __('learn.no_books') }}</p>
    @endforelse
</div>
@endsection
