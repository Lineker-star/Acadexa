@extends('layouts.admin')
@section('title', __('Contact: :name', ['name' => $contact->name]))
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.contacts.index') }}">{{ __('Contacts') }}</a></li>
    <li class="breadcrumb-item active">{{ $contact->name }}</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="bg-white rounded-xl shadow-brand p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="mb-0">{{ __('Contact Submission') }}</h5>
                <span class="badge {{ $contact->is_read ? 'bg-secondary' : 'bg-warning text-dark' }}">
                    {{ $contact->is_read ? __('Read') : __('New') }}
                </span>
            </div>

            <dl class="row mb-4" style="font-size:.9rem;">
                <dt class="col-3 text-muted">{{ __('Name') }}</dt>
                <dd class="col-9">{{ $contact->name }}</dd>
                <dt class="col-3 text-muted">{{ __('Email') }}</dt>
                <dd class="col-9"><a href="mailto:{{ $contact->email }}">{{ $contact->email }}</a></dd>
                @if($contact->phone)
                <dt class="col-3 text-muted">{{ __('Phone') }}</dt>
                <dd class="col-9">{{ $contact->phone }}</dd>
                @endif
                <dt class="col-3 text-muted">{{ __('Subject') }}</dt>
                <dd class="col-9">{{ $contact->subject }}</dd>
                <dt class="col-3 text-muted">{{ __('Received') }}</dt>
                <dd class="col-9">{{ $contact->created_at->isoFormat('LLL') }}</dd>
            </dl>

            <div class="mb-4">
                <label class="form-label fw-bold text-muted small">{{ __('MESSAGE') }}</label>
                <div class="p-3 bg-light rounded" style="white-space:pre-wrap;font-size:.9rem;">{{ $contact->message }}</div>
            </div>

            <div class="d-flex gap-2">
                <a href="mailto:{{ $contact->email }}?subject=Re: {{ urlencode($contact->subject) }}"
                   class="btn btn-primary">
                    <x-icon name="reply" class="me-1" />{{ __('Reply by Email') }}
                </a>
                <a href="{{ route('admin.contacts.index') }}" class="btn btn-outline-secondary">
                    <x-icon name="arrow-left" class="me-1" />{{ __('Back') }}
                </a>
                <form method="POST" action="{{ route('admin.contacts.destroy', $contact) }}" class="ms-auto">
                    @csrf @method('DELETE')
                    <button class="btn btn-outline-danger btn-sm" data-confirm="{{ __('Delete this submission?') }}">
                        <x-icon name="trash" class="me-1" />{{ __('Delete') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
