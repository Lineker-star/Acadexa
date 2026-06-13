@extends('layouts.admin')
@section('title', 'Announcements')
@section('breadcrumb') <li class="breadcrumb-item active">Announcements</li> @endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">Announcements</h4>
    <a href="{{ route('admin.announcements.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i>New Announcement
    </a>
</div>

<div class="bg-white rounded-xl shadow-brand overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr><th>Title</th><th>Audience</th><th>Created By</th><th>Date</th><th>Actions</th></tr>
            </thead>
            <tbody>
                @forelse($announcements as $ann)
                <tr>
                    <td>
                        <div style="font-size:.9rem;font-weight:600;">{{ $ann->title() }}</div>
                        <div class="text-muted" style="font-size:.78rem;">{{ Str::limit($ann->content(), 80) }}</div>
                    </td>
                    <td>
                        <span class="badge {{ match($ann->audience) {'all'=>'bg-primary','students'=>'bg-info text-dark',default=>'bg-warning text-dark'} }}">
                            {{ ucfirst($ann->audience) }}
                        </span>
                    </td>
                    <td style="font-size:.82rem;">{{ $ann->creator?->name }}</td>
                    <td style="font-size:.8rem;">{{ $ann->created_at->format('M d, Y') }}</td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('admin.announcements.edit', $ann) }}" class="btn btn-primary btn-sm">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form method="POST" action="{{ route('admin.announcements.destroy', $ann) }}">
                                @csrf @method('DELETE')
                                <button class="btn btn-outline-danger btn-sm"
                                        onclick="return confirm('Delete this announcement?')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center py-4 text-muted">No announcements yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $announcements->links() }}</div>
@endsection
