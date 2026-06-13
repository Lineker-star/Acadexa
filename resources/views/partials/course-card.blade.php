{{-- Usage: @include('partials.course-card', ['course' => $course]) --}}
<div class="course-card">
    <a href="{{ route('courses.show', $course) }}">
        <img src="{{ $course->thumbnailUrl() }}"
             alt="{{ e($course->title()) }}"
             loading="lazy">
    </a>
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start mb-2">
            <span class="badge-level {{ $course->level }}">{{ ucfirst($course->level) }}</span>
            @auth
                <button class="btn btn-link p-0 text-decoration-none" data-wishlist="{{ $course->id }}" aria-label="Wishlist">
                    <span class="wishlist-icon">
                        @if(auth()->user()->wishlist->contains($course->id)) ❤️ @else 🤍 @endif
                    </span>
                </button>
            @endauth
        </div>

        <a href="{{ route('courses.show', $course) }}" class="text-decoration-none">
            <div class="card-title">{{ $course->title() }}</div>
        </a>

        <div class="instructor-name mb-2">
            <i class="bi bi-person-circle me-1"></i>
            {{ $course->instructor->name ?? 'Instructor' }}
        </div>

        <div class="d-flex align-items-center gap-2 mb-2">
            <div class="stars">
                @for($s = 1; $s <= 5; $s++)
                    <i class="bi bi-star{{ $s <= round($course->avgRating()) ? '-fill' : '' }}"></i>
                @endfor
            </div>
            <span style="font-size:.8rem;color:var(--text-muted);">
                {{ $course->avgRating() }} ({{ $course->reviewCount() }})
            </span>
        </div>

        <div class="d-flex align-items-center gap-2 mt-auto">
            <span style="font-size:.8rem;color:var(--text-muted);">
                <i class="bi bi-clock me-1"></i>{{ $course->durationFormatted() }}
            </span>
            <span style="font-size:.8rem;color:var(--text-muted);">
                <i class="bi bi-people me-1"></i>{{ $course->enrollmentCount() }}
            </span>
        </div>

        <div class="d-flex justify-content-between align-items-center mt-3">
            <div class="price {{ $course->price == 0 ? 'free' : '' }}">
                {{ $course->price == 0 ? __('courses.free') : 'XAF ' . number_format($course->price) }}
            </div>
            <a href="{{ route('courses.show', $course) }}" class="btn btn-primary btn-sm">
                {{ __('courses.view_course') }}
            </a>
        </div>
    </div>
</div>
