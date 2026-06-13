@extends('layouts.admin')
@section('title', 'Certificates')
@section('breadcrumb') <li class="breadcrumb-item active">Certificates</li> @endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">Issued Certificates</h4>
    <span class="badge bg-primary" style="font-size:.9rem;">{{ $certificates->total() }} total</span>
</div>

<div class="bg-white rounded-xl shadow-brand p-3 mb-4">
    <form method="GET" class="d-flex gap-2 flex-wrap">
        <input type="text" name="search" class="form-control form-control-sm" style="max-width:220px;"
               placeholder="Search student or course..." value="{{ request('search') }}">
        <button class="btn btn-primary btn-sm">Search</button>
    </form>
</div>

<div class="bg-white rounded-xl shadow-brand overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr><th>Certificate Code</th><th>Student</th><th>Course</th><th>Issued</th><th>Actions</th></tr>
            </thead>
            <tbody>
                @forelse($certificates as $cert)
                <tr>
                    <td>
                        <code class="text-primary" style="font-size:.85rem;">{{ $cert->certificate_code }}</code>
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <img src="{{ $cert->user?->avatarUrl() }}" class="rounded-circle"
                                 width="32" height="32" alt="{{ $cert->user?->name }}">
                            <span style="font-size:.85rem;">{{ $cert->user?->name }}</span>
                        </div>
                    </td>
                    <td style="font-size:.85rem;">{{ Str::limit($cert->course?->title(), 45) }}</td>
                    <td style="font-size:.8rem;">{{ $cert->issued_at->format('M d, Y') }}</td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('student.certificates.download', $cert->certificate_code) }}"
                               class="btn btn-outline-secondary btn-sm" target="_blank">
                                <i class="bi bi-download"></i>
                            </a>
                            <a href="{{ route('verify.certificate', $cert->certificate_code) }}"
                               class="btn btn-outline-secondary btn-sm" target="_blank">
                                <i class="bi bi-patch-check"></i>
                            </a>
                            <form method="POST" action="{{ route('admin.certificates.destroy', $cert) }}">
                                @csrf @method('DELETE')
                                <button class="btn btn-outline-danger btn-sm"
                                        onclick="return confirm('Revoke this certificate?')">
                                    <i class="bi bi-x-circle"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center py-4 text-muted">No certificates issued yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $certificates->links() }}</div>
@endsection
