@extends('layouts.admin')
@section('title', __('Certificates'))
@section('breadcrumb') <li class="breadcrumb-item active">{{ __('Certificates') }}</li> @endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">{{ __('Issued Certificates') }}</h4>
    <span class="badge bg-primary" style="font-size:.9rem;">{{ trans_choice(':count certificate|:count certificates', $certificates->total(), ['count' => $certificates->total()]) }}</span>
</div>

<div class="bg-white rounded-xl shadow-brand p-3 mb-4">
    <form method="GET" class="d-flex gap-2 flex-wrap">
        <input type="text" name="search" class="form-control form-control-sm" style="max-width:220px;"
               placeholder="{{ __('Search student or course...') }}" value="{{ request('search') }}">
        <button class="btn btn-primary btn-sm">{{ __('Search') }}</button>
    </form>
</div>

<div class="bg-white rounded-xl shadow-brand overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr><th>{{ __('Certificate Code') }}</th><th>{{ __('Student') }}</th><th>{{ __('Course') }}</th><th>{{ __('Issued') }}</th><th>{{ __('Actions') }}</th></tr>
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
                    <td style="font-size:.8rem;">{{ $cert->issued_at->isoFormat('ll') }}</td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('student.certificates.download', $cert->certificate_code) }}"
                               class="btn btn-outline-secondary btn-sm" target="_blank">
                                <x-icon name="download" />
                            </a>
                            <a href="{{ route('verify.certificate', $cert->certificate_code) }}"
                               class="btn btn-outline-secondary btn-sm" target="_blank">
                                <x-icon name="patch-check" />
                            </a>
                            <form method="POST" action="{{ route('admin.certificates.destroy', $cert) }}">
                                @csrf @method('DELETE')
                                <button class="btn btn-outline-danger btn-sm"
                                        data-confirm="{{ __('Revoke this certificate?') }}">
                                    <x-icon name="x-circle" />
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center py-4 text-muted">{{ __('No certificates issued yet.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $certificates->links() }}</div>
@endsection
