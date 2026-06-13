@extends('layouts.admin')
@section('title', 'Users')
@section('breadcrumb') <li class="breadcrumb-item active">Users</li> @endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">User Management</h4>
</div>

<!-- Filters -->
<div class="bg-white rounded-xl shadow-brand p-3 mb-4">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-4">
            <input type="text" name="search" class="form-control form-control-sm"
                   placeholder="Search name or email..." value="{{ request('search') }}">
        </div>
        <div class="col-md-3">
            <select name="role" class="form-select form-select-sm">
                <option value="">All Roles</option>
                @foreach(['student','instructor','admin','super_admin'] as $role)
                <option value="{{ $role }}" {{ request('role') == $role ? 'selected' : '' }}>{{ ucfirst($role) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <select name="status" class="form-select form-select-sm">
                <option value="">All Status</option>
                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
            </select>
        </div>
        <div class="col-md-2">
            <button class="btn btn-primary btn-sm w-100">Filter</button>
        </div>
    </form>
</div>

<!-- Table -->
<div class="bg-white rounded-xl shadow-brand overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>User</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Trial</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $u)
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <img src="{{ $u->avatarUrl() }}" class="rounded-circle" width="36" height="36" alt="{{ $u->name }}">
                            <div>
                                <div style="font-size:.88rem;font-weight:600;">{{ $u->name }}</div>
                                <div style="font-size:.75rem;color:var(--text-muted);">{{ $u->email }}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="badge" style="background:#EEF3FF;color:var(--primary);">{{ ucfirst(str_replace('_',' ',$u->role)) }}</span>
                        @if($u->instructor_status !== 'none')
                        <span class="badge {{ $u->instructor_status === 'confirmed' ? 'bg-success' : ($u->instructor_status === 'pending' ? 'bg-warning text-dark' : 'bg-danger') }}">
                            {{ ucfirst($u->instructor_status) }}
                        </span>
                        @endif
                    </td>
                    <td>
                        @if($u->is_active)
                            <span class="badge bg-success">Active</span>
                        @else
                            <span class="badge bg-danger">Inactive</span>
                        @endif
                    </td>
                    <td style="font-size:.8rem;">
                        @if($u->trial_started_at)
                            {{ $u->trialDaysLeft() > 0 ? $u->trialDaysLeft().' days left' : 'Expired' }}
                        @else
                            —
                        @endif
                    </td>
                    <td style="font-size:.8rem;">{{ $u->created_at->format('M d, Y') }}</td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('admin.users.show', $u) }}" class="btn btn-primary btn-sm"><i class="bi bi-eye"></i></a>
                            @if($u->is_active)
                            <form method="POST" action="{{ route('admin.users.deactivate', $u) }}">
                                @csrf
                                <button class="btn btn-warning btn-sm" title="Deactivate" onclick="return confirm('Deactivate?')">
                                    <i class="bi bi-pause-circle"></i>
                                </button>
                            </form>
                            @else
                            <form method="POST" action="{{ route('admin.users.activate', $u) }}">
                                @csrf
                                <button class="btn btn-success btn-sm" title="Activate"><i class="bi bi-play-circle"></i></button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center py-4 text-muted">No users found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $users->links() }}</div>
@endsection
