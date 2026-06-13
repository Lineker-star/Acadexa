@extends('layouts.admin')
@section('title', 'Contact: ' . $contact->name)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.contacts.index') }}">Contacts</a></li>
    <li class="breadcrumb-item active">{{ $contact->name }}</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="bg-white rounded-xl shadow-brand p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="mb-0">Contact Submission</h5>
                <span class="badge {{ $contact->is_read ? 'bg-secondary' : 'bg-warning text-dark' }}">
                    {{ $contact->is_read ? 'Read' : 'New' }}
                </span>
            </div>

            <dl class="row mb-4" style="font-size:.9rem;">
                <dt class="col-3 text-muted">Name</dt>
                <dd class="col-9">{{ $contact->name }}</dd>
                <dt class="col-3 text-muted">Email</dt>
                <dd class="col-9"><a href="mailto:{{ $contact->email }}">{{ $contact->email }}</a></dd>
                @if($contact->phone)
                <dt class="col-3 text-muted">Phone</dt>
                <dd class="col-9">{{ $contact->phone }}</dd>
                @endif
                <dt class="col-3 text-muted">Subject</dt>
                <dd class="col-9">{{ $contact->subject }}</dd>
                <dt class="col-3 text-muted">Received</dt>
                <dd class="col-9">{{ $contact->created_at->format('F d, Y H:i') }}</dd>
            </dl>

            <div class="mb-4">
                <label class="form-label fw-bold text-muted small">MESSAGE</label>
                <div class="p-3 bg-light rounded" style="white-space:pre-wrap;font-size:.9rem;">{{ $contact->message }}</div>
            </div>

            <div class="d-flex gap-2">
                <a href="mailto:{{ $contact->email }}?subject=Re: {{ urlencode($contact->subject) }}"
                   class="btn btn-primary">
                    <i class="bi bi-reply me-1"></i>Reply by Email
                </a>
                <a href="{{ route('admin.contacts.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Back
                </a>
                <form method="POST" action="{{ route('admin.contacts.destroy', $contact) }}" class="ms-auto">
                    @csrf @method('DELETE')
                    <button class="btn btn-outline-danger btn-sm" onclick="return confirm('Delete this submission?')">
                        <i class="bi bi-trash me-1"></i>Delete
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
