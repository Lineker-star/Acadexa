@extends('layouts.app')
@section('title', 'My Certificates')

@section('content')
<div class="py-5 bg-light-gray" style="min-height:calc(100vh - 64px);">
    <div class="container">
        <h2 class="mb-4">🏆 My Certificates</h2>
        @forelse($certificates as $cert)
        <div class="bg-white rounded-xl shadow-brand p-4 mb-3 d-flex align-items-center gap-4">
            <div style="font-size:3rem;">🎓</div>
            <div class="flex-grow-1">
                <h5 class="mb-1">{{ $cert->course->title() }}</h5>
                <p class="text-muted small mb-1">Issued: {{ $cert->issued_at->format('F d, Y') }}</p>
                <p class="text-muted small mb-0">Code: <code>{{ $cert->certificate_code }}</code></p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ $cert->verifyUrl() }}" target="_blank" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-shield-check me-1"></i>Verify
                </a>
                <a href="{{ route('student.certificates.download', $cert) }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-download me-1"></i>Download PDF
                </a>
            </div>
        </div>
        @empty
        <div class="text-center py-5 bg-white rounded-xl shadow-brand">
            <div style="font-size:4rem;">🏅</div>
            <h5 class="mt-3">No certificates yet</h5>
            <p class="text-muted">Complete a course to earn your first certificate.</p>
            <a href="{{ route('courses.index') }}" class="btn btn-primary">Browse Courses</a>
        </div>
        @endforelse
        {{ $certificates->links() }}
    </div>
</div>
@endsection
