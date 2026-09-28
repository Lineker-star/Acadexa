{{-- Usage: @include('partials.course-card', ['course' => $course]) --}}
<div class="course-card">
    <a href="{{ route('courses.show', $course) }}">
        <img src="{{ $course->thumbnailUrl() }}"
             alt="{{ e($course->title()) }}"
             loading="lazy">
    </a>
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start mb-2">
            <span class="badge-level {{ $course->level }}">{{ __('messages.' . $course->level) }}</span>
            @auth
                <button class="btn btn-link p-0 text-decoration-none" data-wishlist="{{ $course->id }}" aria-label="{{ __('Wishlist') }}">
                    <span class="wishlist-icon">
                        @if(auth()->user()->wishlist->contains($course->id)) <x-icon name="heart-fill" class="me-1" />@else <x-icon name="heart" class="me-1" />@endif
                    </span>
                </button>
            @endauth
        </div>

        <a href="{{ route('courses.show', $course) }}" class="text-decoration-none">
            <div class="card-title">{{ $course->title() }}</div>
        </a>

        <div class="instructor-name mb-2">
            <x-icon name="person-circle" class="me-1" />
            {{ $course->instructor->name ?? __('Instructor') }}
        </div>

        <div class="d-flex align-items-center gap-2 mb-2">
            <div class="stars">
                @for($s = 1; $s <= 5; $s++)
                    <x-icon :name="'star' . ($s <= round($course->avgRating()) ? '-fill' : '')" />
                @endfor
            </div>
            <span style="font-size:.8rem;color:var(--text-muted);">
                {{ $course->avgRating() }} ({{ $course->reviewCount() }})
            </span>
        </div>

        <div class="d-flex align-items-center gap-2 mt-auto">
            <span style="font-size:.8rem;color:var(--text-muted);">
                <x-icon name="clock" class="me-1" />{{ $course->hoursLabel() }}
            </span>
            <span style="font-size:.8rem;color:var(--text-muted);">
                <x-icon name="people" class="me-1" />{{ $course->enrollmentCount() }}
            </span>
        </div>

        <div class="d-flex justify-content-between align-items-center mt-3">
            <div class="price {{ $course->price == 0 ? 'free' : '' }}">
                {{ $course->price == 0 ? __('courses.free') : number_format($course->price, 0, ',', ' ') . ' FCFA' }}
            </div>
            <a href="{{ route('courses.show', $course) }}" class="btn btn-primary btn-sm">
                {{ __('courses.view_course') }}
            </a>
        </div>
    </div>
</div>
