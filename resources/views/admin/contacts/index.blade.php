@extends('layouts.admin')
@section('title', 'Contact Submissions')
@section('breadcrumb') <li class="breadcrumb-item active">Contact Submissions</li> @endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">Contact Submissions</h4>
</div>

<div class="bg-white rounded-xl shadow-brand overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr><th>Name</th><th>Email</th><th>Subject</th><th>Date</th><th>Status</th><th>Action</th></tr>
            </thead>
            <tbody>
                @forelse($contacts as $contact)
                <tr class="{{ !$contact->is_read ? 'fw-semibold' : '' }}">
                    <td style="font-size:.88rem;">{{ $contact->name }}</td>
                    <td style="font-size:.82rem;">{{ $contact->email }}</td>
                    <td style="font-size:.85rem;">{{ Str::limit($contact->subject, 40) }}</td>
                    <td style="font-size:.78rem;">{{ $contact->created_at->format('M d, Y H:i') }}</td>
                    <td>
                        @if(!$contact->is_read)
                        <span class="badge bg-warning text-dark">New</span>
                        @else
                        <span class="badge bg-secondary">Read</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('admin.contacts.show', $contact) }}" class="btn btn-primary btn-sm">
                            <i class="bi bi-eye me-1"></i>View
                        </a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center py-4 text-muted">No contact submissions yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $contacts->links() }}</div>
@endsection
