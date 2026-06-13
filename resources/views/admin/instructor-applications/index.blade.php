@extends('layouts.admin')
@section('title', 'Instructor Applications')
@section('breadcrumb') <li class="breadcrumb-item active">Instructor Applications</li> @endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">Instructor Applications</h4>
</div>

<div class="bg-white rounded-xl shadow-brand p-3 mb-4">
    <form method="GET" class="d-flex gap-2">
        <select name="status" class="form-select form-select-sm" style="width:auto;">
            <option value="">All Status</option>
            <option value="pending" {{ request('status')=='pending'?'selected':'' }}>Pending</option>
            <option value="approved" {{ request('status')=='approved'?'selected':'' }}>Approved</option>
            <option value="rejected" {{ request('status')=='rejected'?'selected':'' }}>Rejected</option>
        </select>
        <button class="btn btn-primary btn-sm">Filter</button>
    </form>
</div>

<div class="bg-white rounded-xl shadow-brand overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr><th>Applicant</th><th>Expertise</th><th>Status</th><th>Applied</th><th>Actions</th></tr>
            </thead>
            <tbody>
                @forelse($applications as $app)
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <img src="{{ $app->user->avatarUrl() }}" class="rounded-circle" width="36" height="36" alt="{{ $app->user->name }}">
                            <div>
                                <div style="font-size:.88rem;font-weight:600;">{{ $app->user->name }}</div>
                                <div style="font-size:.75rem;color:var(--text-muted);">{{ $app->user->email }}</div>
                            </div>
                        </div>
                    </td>
                    <td style="font-size:.85rem;">{{ $app->expertise }}</td>
                    <td>
                        <span class="badge {{ match($app->status) {'pending'=>'bg-warning text-dark','approved'=>'bg-success',default=>'bg-danger'} }}">
                            {{ ucfirst($app->status) }}
                        </span>
                    </td>
                    <td style="font-size:.8rem;">{{ $app->created_at->format('M d, Y') }}</td>
                    <td>
                        <a href="{{ route('admin.applications.show', $app) }}" class="btn btn-primary btn-sm">
                            <i class="bi bi-eye"></i> Review
                        </a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center py-4 text-muted">No applications found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $applications->links() }}</div>
@endsection
