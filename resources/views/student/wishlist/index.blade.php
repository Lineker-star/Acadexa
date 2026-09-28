@extends('layouts.app')
@section('title', __('My Wishlist'))

@section('content')
<div class="container py-5">
    <h2 class="fw-bold mb-4">{{ __('My Wishlist') }}</h2>

    @forelse($wishlist as $course)
    <div class="d-flex gap-3 bg-white rounded-xl shadow-brand mb-3 overflow-hidden">
        <img src="{{ $course->thumbnailUrl() }}" alt="{{ e($course->title()) }}"
             class="rounded-start" style="width:120px;height:80px;object-fit:cover;flex-shrink:0;">
        <div class="flex-grow-1 py-2 pe-3">
            <div style="font-size:.95rem;font-weight:700;">{{ $course->title() }}</div>
            <div class="text-muted small">{{ $course->instructor?->name }}</div>
        </div>
        <div class="d-flex align-items-center gap-2 pe-3">
            <a href="{{ route('courses.show', $course->slug) }}" class="btn btn-primary btn-sm">{{ __('View') }}</a>
            <button class="btn btn-outline-danger btn-sm" data-wishlist="{{ $course->id }}" title="{{ __('Remove from wishlist') }}">
                <span class="wishlist-icon"><x-icon name="heart-fill" /></span>
            </button>
        </div>
    </div>
    @empty
    <div class="text-center py-5">
        <x-icon name="heart" style="font-size:3rem;color:var(--accent);opacity:.3;" />
        <h4 class="mt-3 text-muted">{{ __('Your wishlist is empty') }}</h4>
        <p class="text-muted">{{ __('Save courses you want to take later.') }}</p>
        <a href="{{ route('courses.index') }}" class="btn btn-primary">{{ __('Explore Courses') }}</a>
    </div>
    @endforelse

    {{ $wishlist->links() }}
</div>
@endsection
