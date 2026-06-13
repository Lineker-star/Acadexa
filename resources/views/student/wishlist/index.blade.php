@extends('layouts.app')
@section('title', 'My Wishlist')

@section('content')
<div class="container py-5">
    <h2 class="fw-bold mb-4">My Wishlist</h2>

    @forelse($wishlistItems as $item)
    @php $course = $item->course; @endphp
    <div class="d-flex gap-3 bg-white rounded-xl shadow-brand mb-3 overflow-hidden">
        <img src="{{ $course->thumbnailUrl() }}" alt="{{ e($course->title()) }}"
             class="rounded-start" style="width:120px;height:80px;object-fit:cover;flex-shrink:0;">
        <div class="flex-grow-1 py-2 pe-3">
            <div style="font-size:.95rem;font-weight:700;">{{ $course->title() }}</div>
            <div class="text-muted small">{{ $course->instructor?->name }}</div>
        </div>
        <div class="d-flex align-items-center gap-2 pe-3">
            <a href="{{ route('courses.show', $course->slug) }}" class="btn btn-primary btn-sm">View</a>
            <button class="btn btn-outline-danger btn-sm wishlist-toggle"
                    data-course="{{ $course->id }}" title="Remove from wishlist">
                <i class="bi bi-heart-fill"></i>
            </button>
        </div>
    </div>
    @empty
    <div class="text-center py-5">
        <i class="bi bi-heart" style="font-size:3rem;color:var(--accent);opacity:.3;"></i>
        <h4 class="mt-3 text-muted">Your wishlist is empty</h4>
        <p class="text-muted">Save courses you want to take later.</p>
        <a href="{{ route('courses.index') }}" class="btn btn-primary">Explore Courses</a>
    </div>
    @endforelse
</div>
@endsection
