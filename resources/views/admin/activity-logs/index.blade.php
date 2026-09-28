@extends('layouts.admin')
@section('title', __('Activity Logs'))
@section('breadcrumb') <li class="breadcrumb-item active">{{ __('Activity Logs') }}</li> @endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">{{ __('Activity Logs') }}</h4>
    <form method="POST" action="{{ route('admin.activity-logs.clear') }}"
          data-confirm="{{ __('Clear all activity logs older than 30 days?') }}">
        @csrf
        <button class="btn btn-outline-danger btn-sm">
            <x-icon name="trash" class="me-1" />{{ __('Clear Old Logs') }}
        </button>
    </form>
</div>

<div class="bg-white rounded-xl shadow-brand p-3 mb-4">
    <form method="GET" class="d-flex gap-2 flex-wrap">
        <input type="text" name="search" class="form-control form-control-sm"
               style="max-width:200px;" placeholder="{{ __('Search action...') }}" value="{{ request('search') }}">
        <input type="date" name="date" class="form-control form-control-sm"
               style="max-width:160px;" value="{{ request('date') }}">
        <button class="btn btn-primary btn-sm">{{ __('Filter') }}</button>
    </form>
</div>

<div class="bg-white rounded-xl shadow-brand overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size:.82rem;">
            <thead class="table-light">
                <tr><th>{{ __('Action') }}</th><th>{{ __('Description') }}</th><th>{{ __('User') }}</th><th>{{ __('IP') }}</th><th>{{ __('Time') }}</th></tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                <tr>
                    <td><span class="badge bg-secondary">{{ $log->action }}</span></td>
                    <td style="max-width:300px;">{{ $log->description }}</td>
                    <td>{{ $log->user?->name ?? __('Guest') }}</td>
                    <td><code>{{ $log->ip_address }}</code></td>
                    <td class="text-muted">{{ $log->created_at->diffForHumans() }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center py-4 text-muted">{{ __('No logs found.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $logs->links() }}</div>
@endsection
