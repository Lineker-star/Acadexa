@extends('layouts.admin')
@section('title', __('Application: :name', ['name' => $application->user->name]))
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.applications.index') }}">{{ __('Applications') }}</a></li>
    <li class="breadcrumb-item active">{{ $application->user->name }}</li>
@endsection

@section('content')
<div class="row g-4">
    <div class="col-lg-4">
        <div class="bg-white rounded-xl shadow-brand p-4 text-center">
            <img src="{{ $application->user->avatarUrl() }}" class="rounded-circle mb-3" width="80" height="80" alt="{{ $application->user->name }}">
            <h5>{{ $application->user->name }}</h5>
            <p class="text-muted small">{{ $application->user->email }}</p>
            <span class="badge {{ match($application->status) {'pending'=>'bg-warning text-dark','approved'=>'bg-success',default=>'bg-danger'} }}">
                {{ __('lms.application_status_' . $application->status) }}
            </span>
            <p class="text-muted small mt-2">{{ __('Applied on :date', ['date' => $application->created_at->isoFormat('LL')]) }}</p>
        </div>

        @if($application->status === 'pending')
        <div class="bg-white rounded-xl shadow-brand p-4 mt-3">
            <h6 class="fw-bold mb-3">{{ __('Decision') }}</h6>
            <form method="POST" action="{{ route('admin.applications.approve', $application) }}" class="mb-3">
                @csrf
                <button class="btn btn-success w-100" data-confirm="{{ __('Approve this application? The user will become a confirmed instructor.') }}">
                    <x-icon name="check-circle-fill" class="me-1" />{{ __('Approve Application') }}
                </button>
            </form>
            <form method="POST" action="{{ route('admin.applications.reject', $application) }}">
                @csrf
                <div class="mb-2">
                    <label class="form-label small fw-bold">{{ __('Notes for Applicant (optional)') }}</label>
                    <textarea name="admin_notes" class="form-control form-control-sm" rows="3"
                              placeholder="{{ __('Reason for rejection...') }}"></textarea>
                </div>
                <button class="btn btn-danger w-100" data-confirm="{{ __('Reject this application?') }}">
                    <x-icon name="x-circle-fill" class="me-1" />{{ __('Reject Application') }}
                </button>
            </form>
        </div>
        @endif
    </div>

    <div class="col-lg-8">
        <div class="bg-white rounded-xl shadow-brand p-4 mb-4">
            <h5 class="mb-4">{{ __('Application Details') }}</h5>
            <div class="mb-4">
                <label class="form-label fw-bold">{{ __('Area of Expertise') }}</label>
                <p class="text-muted">{{ $application->expertise }}</p>
            </div>
            <div class="mb-4">
                <label class="form-label fw-bold">{{ __('Professional Bio') }}</label>
                <p class="text-muted" style="white-space:pre-line;">{{ $application->bio }}</p>
            </div>
            <div class="mb-4">
                <label class="form-label fw-bold">{{ __('Credentials & Qualifications') }}</label>
                <p class="text-muted" style="white-space:pre-line;">{{ $application->credentials }}</p>
            </div>
            @if($application->sample_content)
            <div class="mb-4">
                <label class="form-label fw-bold">{{ __('Sample Content / Course Ideas') }}</label>
                <p class="text-muted" style="white-space:pre-line;">{{ $application->sample_content }}</p>
            </div>
            @endif
            @if($application->admin_notes)
            <div class="alert alert-warning">
                <strong>{{ __('Admin Notes:') }}</strong> {{ $application->admin_notes }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
