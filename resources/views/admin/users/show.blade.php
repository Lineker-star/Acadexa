@extends('layouts.admin')
@section('title', $user->name)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">{{ __('Users') }}</a></li>
    <li class="breadcrumb-item active">{{ $user->name }}</li>
@endsection

@section('content')
<div class="row g-4">
    <div class="col-lg-4">
        <div class="bg-white rounded-xl shadow-brand p-4 text-center">
            <img src="{{ $user->avatarUrl() }}" class="rounded-circle mb-3" width="100" height="100" alt="{{ $user->name }}">
            <h5>{{ $user->name }}</h5>
            <p class="text-muted small">{{ $user->email }}</p>
            <span class="badge bg-primary">{{ __('lms.role_' . $user->role) }}</span>
            @if(!$user->is_active)<span class="badge bg-danger ms-1">{{ __('Inactive') }}</span>@endif
            @if($user->isBanned())<span class="badge bg-dark ms-1">{{ __('security.banned_badge') }}</span>@endif
            @if($user->hasTwoFactorEnabled())<span class="badge bg-success ms-1"><x-icon name="shield-check" /> {{ __('2FA') }}</span>@endif
            <hr>
            <p class="small text-start"><strong>{{ __('Country:') }}</strong> {{ $user->country ?? '—' }}</p>
            <p class="small text-start"><strong>{{ __('Joined:') }}</strong> {{ $user->created_at->isoFormat('LL') }}</p>
            <p class="small text-start"><strong>{{ __('Trial Ends:') }}</strong> {{ $user->trialEndsAt()?->isoFormat('LL') ?? '—' }}</p>
            <p class="small text-start"><strong>{{ __('Instructor Status:') }}</strong> {{ __('lms.instructor_status_' . $user->instructor_status) }}</p>
            @if($user->bio)<p class="small text-start"><strong>{{ __('Bio:') }}</strong> {{ $user->bio }}</p>@endif
        </div>

        <!-- Actions -->
        <div class="bg-white rounded-xl shadow-brand p-4 mt-3">
            <h6 class="fw-bold mb-3">{{ __('Quick Actions') }}</h6>
            @if($user->isBanned())
            <div class="alert alert-danger small text-start py-2">
                <strong>{{ __('security.banned_since', ['date' => $user->banned_at->isoFormat('L')]) }}</strong>
                @if($user->ban_reason)<div>{{ $user->ban_reason }}</div>@endif
            </div>
            <form method="POST" action="{{ route('admin.users.unban', $user) }}" class="mb-2">
                @csrf
                <button class="btn btn-success btn-sm w-100"><x-icon name="unlock" class="me-1" />{{ __('security.unban') }}</button>
            </form>
            @endif
            @if($user->is_active)
            <form method="POST" action="{{ route('admin.users.deactivate', $user) }}" class="mb-2" data-confirm="{{ __('Deactivate this user?') }}">
                @csrf
                <button class="btn btn-warning btn-sm w-100">
                    <x-icon name="pause-circle" class="me-1" />{{ __('Deactivate') }}
                </button>
            </form>
            @unless($user->isBanned())
            <form method="POST" action="{{ route('admin.users.ban', $user) }}" class="mb-2 text-start" data-confirm="{{ __('security.confirm_ban') }}">
                @csrf
                <input type="text" name="reason" class="form-control form-control-sm mb-2" maxlength="500" placeholder="{{ __('security.ban_reason') }}">
                <button class="btn btn-danger btn-sm w-100">
                    <x-icon name="slash-circle" class="me-1" />{{ __('Ban User') }}
                </button>
            </form>
            @endunless
            @else
            <form method="POST" action="{{ route('admin.users.activate', $user) }}" class="mb-2">
                @csrf
                <button class="btn btn-success btn-sm w-100"><x-icon name="play-circle" class="me-1" />{{ __('Activate') }}</button>
            </form>
            @endif

            <form method="POST" action="{{ route('admin.users.extend-trial', $user) }}" class="mt-3">
                @csrf
                <label class="form-label small fw-bold">{{ __('Extend Trial (days)') }}</label>
                <div class="input-group input-group-sm">
                    <input type="number" name="days" class="form-control" value="30" min="1" max="365">
                    <button class="btn btn-primary">{{ __('Extend') }}</button>
                </div>
            </form>

            <form method="POST" action="{{ route('admin.users.reset-password', $user) }}" class="mt-3">
                @csrf
                <label class="form-label small fw-bold">{{ __('Reset Password') }}</label>
                <input type="password" name="password" class="form-control form-control-sm mb-2" placeholder="{{ __('New password') }}" required>
                <input type="password" name="password_confirmation" class="form-control form-control-sm mb-2" placeholder="{{ __('Confirm') }}">
                <button class="btn btn-outline-danger btn-sm w-100">{{ __('Reset Password') }}</button>
            </form>
        </div>

        @if(auth()->user()->isSuperAdmin() && auth()->id() !== $user->id && ! $user->isSuperAdmin())
        <div class="bg-white rounded-xl shadow-brand p-4 mt-3">
            <h6 class="fw-bold mb-3"><x-icon name="shield-lock" class="me-1" />{{ __('security.role_permissions') }}</h6>
            <form method="POST" action="{{ route('admin.users.permissions', $user) }}">
                @csrf
                <select name="role" class="form-select form-select-sm mb-3" id="roleSelect">
                    @foreach(['student', 'instructor', 'admin'] as $role)
                        <option value="{{ $role }}" @selected($user->role === $role)>{{ __('lms.role_' . $role) }}</option>
                    @endforeach
                </select>
                <div class="small text-muted mb-2">{{ __('security.permissions_help') }}</div>
                <div class="row row-cols-2 g-1 mb-3">
                    @foreach(\App\Models\User::ADMIN_PERMISSIONS as $perm)
                        <div class="col">
                            <div class="form-check small">
                                <input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $perm }}" id="perm-{{ $perm }}"
                                       @checked(in_array($perm, $user->admin_permissions ?? [], true))>
                                <label class="form-check-label" for="perm-{{ $perm }}">{{ __('security.perm_' . $perm) }}</label>
                            </div>
                        </div>
                    @endforeach
                </div>
                <button class="btn btn-sm btn-primary w-100">{{ __('security.save_permissions') }}</button>
            </form>
            @if($user->hasTwoFactorEnabled())
                <form method="POST" action="{{ route('admin.users.reset-2fa', $user) }}" class="mt-2" data-confirm="{{ __('security.confirm_reset_2fa') }}">
                    @csrf
                    <button class="btn btn-sm btn-outline-secondary w-100"><x-icon name="phone" class="me-1" />{{ __('security.reset_2fa') }}</button>
                </form>
            @endif
        </div>
        @endif
    </div>

    <div class="col-lg-8">
        <!-- Enrollments -->
        <div class="bg-white rounded-xl shadow-brand p-4 mb-4">
            <h5 class="mb-3">{{ __('Enrolled Courses') }} ({{ $user->enrollments->count() }})</h5>
            @forelse($user->enrollments as $enr)
            <div class="d-flex gap-3 align-items-center mb-2 pb-2 border-bottom">
                <div class="flex-grow-1">
                    <div style="font-size:.9rem;font-weight:600;">{{ $enr->course->title() }}</div>
                    <div style="font-size:.75rem;color:var(--text-muted);">{{ $enr->enrolled_at->isoFormat('ll') }}</div>
                </div>
                <span style="font-size:.8rem;">{{ $enr->progress_percent }}%</span>
                @if($enr->completed_at)<span class="badge bg-success">{{ __('Completed') }}</span>@endif
            </div>
            @empty
            <p class="text-muted small">{{ __('No enrollments.') }}</p>
            @endforelse
        </div>

        <!-- Certificates -->
        <div class="bg-white rounded-xl shadow-brand p-4">
            <h5 class="mb-3">{{ __('Certificates') }} ({{ $user->certificates->count() }})</h5>
            @forelse($user->certificates as $cert)
            <div class="d-flex gap-3 align-items-center mb-2 pb-2 border-bottom">
                <div class="flex-grow-1">
                    <div style="font-size:.9rem;font-weight:600;">{{ $cert->course->title() }}</div>
                    <div style="font-size:.75rem;color:var(--text-muted);">{{ $cert->certificate_code }}</div>
                </div>
                <span style="font-size:.8rem;">{{ $cert->issued_at->isoFormat('ll') }}</span>
            </div>
            @empty
            <p class="text-muted small">{{ __('No certificates yet.') }}</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
