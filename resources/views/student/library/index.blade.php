@extends('layouts.app')
@section('title', __('learn.my_library'))
@section('robots', 'noindex, nofollow')

@section('content')
<div class="container py-5" style="max-width:980px">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
        <h1 class="h3 fw-bold mb-0"><x-icon name="book" class="text-primary me-2" />{{ __('learn.my_library') }}</h1>
        <a href="{{ route('offline') }}" class="btn btn-sm btn-outline-primary"><x-icon name="cloud-slash" class="me-1" />{{ __('lms.offline_courses') }}</a>
    </div>
    <p class="text-muted small mb-4">{{ __('learn.library_help') }}</p>

    <div id="librarySyncStatus" class="small text-muted mb-3" aria-live="polite"></div>

    @forelse($items as $item)
        @php $book = $item->book; @endphp
        <div class="library-card mb-2" data-library-book="{{ $book->id }}" data-url="{{ $book->url() }}">
            <div class="cover"><x-icon :name="$book->icon()" /></div>
            <div class="flex-grow-1 min-w-0">
                <div class="fw-semibold text-truncate">{{ $book->title }}</div>
                <div class="small text-muted text-truncate">{{ $book->author ? $book->author . ' · ' : '' }}{{ $book->course?->title() }} · {{ $book->sizeLabel() }}</div>
                <div class="d-flex align-items-center gap-2 mt-1">
                    <div class="progress flex-grow-1" style="height:4px;max-width:220px"><div class="progress-bar" style="width:{{ $item->progress_percent }}%"></div></div>
                    <span class="small text-muted" data-role="offline-state"></span>
                </div>
            </div>
            <button type="button" class="btn btn-sm btn-primary" data-open-media="{{ $book->url() }}" data-media-kind="{{ $book->kind() }}"
                    data-media-title="{{ $book->title }}" data-book-id="{{ $book->id }}" data-position="{{ $item->position }}">
                <x-icon name="book-half" class="me-1" />{{ $item->position ? __('learn.continue_reading') : __('learn.read') }}</button>
            <form method="POST" action="{{ route('student.library.remove', $book) }}" data-confirm="{{ __('learn.confirm_remove_book') }}">
                @csrf @method('DELETE')
                <button class="btn btn-sm btn-light text-danger" aria-label="{{ __('learn.remove_from_library') }}"><x-icon name="trash" /></button>
            </form>
        </div>
    @empty
        <div class="text-center bg-white rounded-xl shadow-brand py-5 px-3">
            <x-icon name="book" style="font-size:2.5rem;color:var(--bs-primary);opacity:.4" />
            <h2 class="h5 mt-3">{{ __('learn.library_empty') }}</h2>
            <p class="text-muted small mb-0">{{ __('learn.library_empty_help') }}</p>
        </div>
    @endforelse

    @if($suggested->isNotEmpty())
        <h2 class="h5 fw-bold mt-5 mb-3">{{ __('learn.books_of_my_courses') }}</h2>
        @foreach($suggested as $book)
            <div class="library-card mb-2">
                <div class="cover"><x-icon :name="$book->icon()" /></div>
                <div class="flex-grow-1 min-w-0">
                    <div class="fw-semibold text-truncate">{{ $book->title }}</div>
                    <div class="small text-muted text-truncate">{{ $book->author ? $book->author . ' · ' : '' }}{{ $book->course?->title() }} · {{ $book->sizeLabel() }}</div>
                </div>
                <form method="POST" action="{{ route('student.library.add', $book) }}">
                    @csrf
                    <button class="btn btn-sm btn-outline-primary"><x-icon name="cloud-arrow-down" class="me-1" />{{ __('learn.add_to_library') }}</button>
                </form>
            </div>
        @endforeach
    @endif
</div>
@endsection

@push('scripts')
@vite(['resources/js/library-page.js'])
@endpush
