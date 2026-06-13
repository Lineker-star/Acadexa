@extends('layouts.app')
@section('title', 'My Dashboard')

@section('content')
<div class="py-5 bg-light-gray" style="min-height:calc(100vh - 64px);">
    <div class="container">
        <!-- Welcome Header -->
        <div class="d-flex align-items-center gap-4 mb-5">
            <img src="{{ $user->avatarUrl() }}" class="rounded-circle" width="72" height="72" alt="avatar">
            <div>
                <h2 class="mb-1">{{ __('dashboard.welcome') }}, {{ Str::words($user->name, 1, '') }}! 👋</h2>
                <p class="text-muted mb-0">
                    @if($user->isTrialActive())
                        <span class="badge" style="background:#D1FAE5;color:#065F46;">
                            ✅ Trial Active — {{ $user->trialDaysLeft() }} days left
                        </span>
                    @else
                        <span class="badge" style="background:#FEE2E2;color:#991B1B;">
                            ⚠️ Trial Expired
                        </span>
                        <a href="{{ route('student.subscription') }}" class="btn btn-sm btn-secondary ms-2">Subscribe Now</a>
                    @endif
                </p>
            </div>
        </div>

        <!-- Stats Row -->
        <div class="row g-4 mb-5">
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon" style="background:#EEF3FF;"><span style="font-size:1.5rem;">📚</span></div>
                    <div>
                        <div class="stat-number">{{ $enrollments->count() }}</div>
                        <div class="stat-label">Enrolled Courses</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon" style="background:#D1FAE5;"><span style="font-size:1.5rem;">✅</span></div>
                    <div>
                        <div class="stat-number">{{ $completedCourses }}</div>
                        <div class="stat-label">Completed</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon" style="background:#FEF3C7;"><span style="font-size:1.5rem;">🏆</span></div>
                    <div>
                        <div class="stat-number">{{ $certificates->count() }}</div>
                        <div class="stat-label">Certificates</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon" style="background:#FEE2E2;"><span style="font-size:1.5rem;">❤️</span></div>
                    <div>
                        <div class="stat-number">{{ $user->wishlist()->count() }}</div>
                        <div class="stat-label">Wishlist</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Continue Learning -->
            <div class="col-lg-8">
                <div class="bg-white rounded-xl shadow-brand p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="mb-0">Continue Learning</h5>
                        <a href="{{ route('student.courses.index') }}" class="btn btn-outline-primary btn-sm">All Courses</a>
                    </div>

                    @forelse($enrollments as $enrollment)
                    <div class="d-flex gap-3 align-items-center mb-4 pb-4 border-bottom">
                        <img src="{{ $enrollment->course->thumbnailUrl() }}"
                             class="rounded" style="width:80px;height:56px;object-fit:cover;"
                             alt="{{ e($enrollment->course->title()) }}">
                        <div class="flex-grow-1">
                            <div class="fw-bold" style="font-size:.9rem;color:var(--primary);">
                                {{ $enrollment->course->title() }}
                            </div>
                            @if($enrollment->lastLesson)
                            <div class="text-muted" style="font-size:.8rem;">
                                Last: {{ $enrollment->lastLesson->title() }}
                            </div>
                            @endif
                            <div class="d-flex align-items-center gap-2 mt-1">
                                <div class="progress-bar-custom flex-grow-1">
                                    <div class="bar" style="width:{{ $enrollment->progress_percent }}%;"></div>
                                </div>
                                <span style="font-size:.75rem;color:var(--text-muted);">{{ $enrollment->progress_percent }}%</span>
                            </div>
                        </div>
                        <a href="{{ route('student.courses.player', $enrollment) }}"
                           class="btn btn-primary btn-sm">
                            <i class="bi bi-play-fill"></i>
                        </a>
                    </div>
                    @empty
                    <div class="text-center py-4">
                        <div style="font-size:3rem;">📭</div>
                        <p class="text-muted mt-2">You're not enrolled in any courses yet.</p>
                        <a href="{{ route('courses.index') }}" class="btn btn-primary">Browse Courses</a>
                    </div>
                    @endforelse
                </div>

                <!-- Announcements -->
                @if($announcements->count())
                <div class="bg-white rounded-xl shadow-brand p-4 mt-4">
                    <h5 class="mb-4">📢 Announcements</h5>
                    @foreach($announcements as $ann)
                    <div class="mb-3 pb-3 border-bottom">
                        <div class="fw-bold" style="color:var(--primary);">{{ $ann->titleFor() }}</div>
                        <p class="text-muted small mb-1">{{ Str::limit(strip_tags($ann->bodyFor()), 120) }}</p>
                        <small class="text-muted">{{ $ann->created_at->diffForHumans() }}</small>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>

            <!-- Right Sidebar -->
            <div class="col-lg-4">
                <!-- Certificates -->
                <div class="bg-white rounded-xl shadow-brand p-4 mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0">🏆 Certificates</h6>
                        <a href="{{ route('student.certificates.index') }}" class="btn btn-outline-primary btn-sm">View All</a>
                    </div>
                    @forelse($certificates as $cert)
                    <div class="d-flex align-items-center gap-3 mb-2 pb-2 border-bottom">
                        <div style="font-size:1.5rem;">🎓</div>
                        <div class="flex-grow-1">
                            <div style="font-size:.85rem;font-weight:600;">{{ $cert->course->title() }}</div>
                            <div style="font-size:.75rem;color:var(--text-muted);">{{ $cert->issued_at->format('M d, Y') }}</div>
                        </div>
                        <a href="{{ route('student.certificates.download', $cert) }}"
                           class="btn btn-outline-primary btn-sm" title="Download">
                            <i class="bi bi-download"></i>
                        </a>
                    </div>
                    @empty
                    <p class="text-muted small">Complete a course to earn certificates.</p>
                    @endforelse
                </div>

                <!-- Quick Links -->
                <div class="bg-white rounded-xl shadow-brand p-4">
                    <h6 class="mb-3">Quick Links</h6>
                    <div class="list-group list-group-flush">
                        <a href="{{ route('student.profile.edit') }}" class="list-group-item list-group-item-action d-flex align-items-center gap-2 border-0 py-2 px-0">
                            <i class="bi bi-person text-primary"></i> Edit Profile
                        </a>
                        <a href="{{ route('student.wishlist.index') }}" class="list-group-item list-group-item-action d-flex align-items-center gap-2 border-0 py-2 px-0">
                            <i class="bi bi-heart text-primary"></i> My Wishlist
                        </a>
                        <a href="{{ route('courses.index') }}" class="list-group-item list-group-item-action d-flex align-items-center gap-2 border-0 py-2 px-0">
                            <i class="bi bi-search text-primary"></i> Explore Courses
                        </a>
                        @if(!auth()->user()->isPendingInstructor() && !auth()->user()->isInstructor())
                        <a href="{{ route('become-instructor') }}" class="list-group-item list-group-item-action d-flex align-items-center gap-2 border-0 py-2 px-0">
                            <i class="bi bi-mortarboard text-primary"></i> Become Instructor
                        </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
