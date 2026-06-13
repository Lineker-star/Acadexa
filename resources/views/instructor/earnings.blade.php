@extends('layouts.instructor')
@section('title', 'Earnings')

@section('content')
<div class="mb-4">
    <h4 class="fw-bold">Earnings</h4>
    <p class="text-muted mb-0">Monetization features are coming soon.</p>
</div>

<!-- Coming Soon Banner -->
<div class="bg-white rounded-xl shadow-brand p-5 text-center mb-4">
    <div style="font-size:3rem;">💰</div>
    <h3 class="mt-3" style="color:var(--primary);">Payment & Earnings — Coming Soon</h3>
    <p class="text-muted mt-2 mb-4" style="max-width:500px;margin:0 auto;">
        We are building a powerful earnings dashboard with course revenue tracking,
        payout management, and detailed analytics. This feature will be available in a future update.
    </p>
    <a href="mailto:{{ \App\Models\Setting::get('contact_email', 'info@ztfuniversity.com') }}"
       class="btn btn-primary">
        <i class="bi bi-envelope me-1"></i>Contact Us for More Info
    </a>
</div>

<!-- Current Enrollment Stats -->
<div class="bg-white rounded-xl shadow-brand p-4">
    <h5 class="mb-3">Your Course Enrollments</h5>
    <div class="table-responsive">
        <table class="table align-middle mb-0" style="font-size:.88rem;">
            <thead class="table-light">
                <tr><th>Course</th><th>Status</th><th>Enrollments</th><th>Avg Rating</th></tr>
            </thead>
            <tbody>
                @forelse($courses as $course)
                <tr>
                    <td>{{ $course->title() }}</td>
                    <td>
                        <span class="badge {{ $course->status==='published'?'bg-success':'bg-secondary' }}">
                            {{ ucfirst($course->status) }}
                        </span>
                    </td>
                    <td>{{ $course->enrollments_count ?? $course->enrollments->count() }}</td>
                    <td>{{ number_format($course->avgRating(), 1) }} / 5.0</td>
                </tr>
                @empty
                <tr><td colspan="4" class="text-center py-3 text-muted">No courses yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
