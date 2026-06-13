@extends('layouts.admin')
@section('title', $user->name)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">Users</a></li>
    <li class="breadcrumb-item active">{{ $user->name }}</li>
@endsection

@section('content')
<div class="row g-4">
    <div class="col-lg-4">
        <div class="bg-white rounded-xl shadow-brand p-4 text-center">
            <img src="{{ $user->avatarUrl() }}" class="rounded-circle mb-3" width="100" height="100" alt="{{ $user->name }}">
            <h5>{{ $user->name }}</h5>
            <p class="text-muted small">{{ $user->email }}</p>
            <span class="badge bg-primary">{{ ucfirst($user->role) }}</span>
            @if(!$user->is_active)<span class="badge bg-danger ms-1">Inactive</span>@endif
            <hr>
            <p class="small text-start"><strong>Country:</strong> {{ $user->country ?? '—' }}</p>
            <p class="small text-start"><strong>Joined:</strong> {{ $user->created_at->format('F d, Y') }}</p>
            <p class="small text-start"><strong>Trial Ends:</strong> {{ $user->trialEndsAt()?->format('F d, Y') ?? '—' }}</p>
            <p class="small text-start"><strong>Instructor Status:</strong> {{ ucfirst($user->instructor_status) }}</p>
            @if($user->bio)<p class="small text-start"><strong>Bio:</strong> {{ $user->bio }}</p>@endif
        </div>

        <!-- Actions -->
        <div class="bg-white rounded-xl shadow-brand p-4 mt-3">
            <h6 class="fw-bold mb-3">Quick Actions</h6>
            @if($user->is_active)
            <form method="POST" action="{{ route('admin.users.deactivate', $user) }}" class="mb-2">
                @csrf
                <button class="btn btn-warning btn-sm w-100" onclick="return confirm('Deactivate this user?')">
                    <i class="bi bi-pause-circle me-1"></i>Deactivate
                </button>
            </form>
            <form method="POST" action="{{ route('admin.users.ban', $user) }}" class="mb-2">
                @csrf
                <button class="btn btn-danger btn-sm w-100" onclick="return confirm('Ban this user?')">
                    <i class="bi bi-slash-circle me-1"></i>Ban User
                </button>
            </form>
            @else
            <form method="POST" action="{{ route('admin.users.activate', $user) }}" class="mb-2">
                @csrf
                <button class="btn btn-success btn-sm w-100"><i class="bi bi-play-circle me-1"></i>Activate</button>
            </form>
            @endif

            <form method="POST" action="{{ route('admin.users.extend-trial', $user) }}" class="mt-3">
                @csrf
                <label class="form-label small fw-bold">Extend Trial (days)</label>
                <div class="input-group input-group-sm">
                    <input type="number" name="days" class="form-control" value="30" min="1" max="365">
                    <button class="btn btn-primary">Extend</button>
                </div>
            </form>

            <form method="POST" action="{{ route('admin.users.reset-password', $user) }}" class="mt-3">
                @csrf
                <label class="form-label small fw-bold">Reset Password</label>
                <input type="password" name="password" class="form-control form-control-sm mb-2" placeholder="New password" required>
                <input type="password" name="password_confirmation" class="form-control form-control-sm mb-2" placeholder="Confirm">
                <button class="btn btn-outline-danger btn-sm w-100">Reset Password</button>
            </form>
        </div>
    </div>

    <div class="col-lg-8">
        <!-- Enrollments -->
        <div class="bg-white rounded-xl shadow-brand p-4 mb-4">
            <h5 class="mb-3">Enrolled Courses ({{ $user->enrollments->count() }})</h5>
            @forelse($user->enrollments as $enr)
            <div class="d-flex gap-3 align-items-center mb-2 pb-2 border-bottom">
                <div class="flex-grow-1">
                    <div style="font-size:.9rem;font-weight:600;">{{ $enr->course->title() }}</div>
                    <div style="font-size:.75rem;color:var(--text-muted);">{{ $enr->enrolled_at->format('M d, Y') }}</div>
                </div>
                <span style="font-size:.8rem;">{{ $enr->progress_percent }}%</span>
                @if($enr->completed_at)<span class="badge bg-success">Completed</span>@endif
            </div>
            @empty
            <p class="text-muted small">No enrollments.</p>
            @endforelse
        </div>

        <!-- Certificates -->
        <div class="bg-white rounded-xl shadow-brand p-4">
            <h5 class="mb-3">Certificates ({{ $user->certificates->count() }})</h5>
            @forelse($user->certificates as $cert)
            <div class="d-flex gap-3 align-items-center mb-2 pb-2 border-bottom">
                <div class="flex-grow-1">
                    <div style="font-size:.9rem;font-weight:600;">{{ $cert->course->title() }}</div>
                    <div style="font-size:.75rem;color:var(--text-muted);">{{ $cert->certificate_code }}</div>
                </div>
                <span style="font-size:.8rem;">{{ $cert->issued_at->format('M d, Y') }}</span>
            </div>
            @empty
            <p class="text-muted small">No certificates yet.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
