@extends('layouts.app')
@section('title', __('My Certificates'))

@section('content')
<div class="py-5 bg-light-gray" style="min-height:calc(100vh - 64px);">
    <div class="container">
        <h2 class="mb-4"><x-icon name="trophy" class="me-1" />{{ __('My Certificates') }}</h2>
        @forelse($certificates as $cert)
        <div class="bg-white rounded-xl shadow-brand p-4 mb-3 d-flex align-items-center gap-4">
            <div style="font-size:3rem;"><x-icon name="mortarboard" /></div>
            <div class="flex-grow-1">
                <h5 class="mb-1">{{ $cert->course->title() }}</h5>
                <p class="text-muted small mb-1">{{ __('Issued on :date', ['date' => $cert->issued_at->isoFormat('LL')]) }}</p>
                <p class="text-muted small mb-0">{{ __('Code:') }} <code>{{ $cert->certificate_code }}</code></p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ $cert->verifyUrl() }}" target="_blank" class="btn btn-outline-primary btn-sm">
                    <x-icon name="shield-check" class="me-1" />{{ __('Verify') }}
                </a>
                <a href="{{ route('student.certificates.download', $cert) }}" class="btn btn-primary btn-sm">
                    <x-icon name="download" class="me-1" />{{ __('Download PDF') }}
                </a>
            </div>
        </div>
        @empty
        <div class="text-center py-5 bg-white rounded-xl shadow-brand">
            <div style="font-size:4rem;"><x-icon name="award" /></div>
            <h5 class="mt-3">{{ __('No certificates yet') }}</h5>
            <p class="text-muted">{{ __('Complete a course to earn your first certificate.') }}</p>
            <a href="{{ route('courses.index') }}" class="btn btn-primary">{{ __('Browse Courses') }}</a>
        </div>
        @endforelse
        {{ $certificates->links() }}
    </div>
</div>
@endsection
