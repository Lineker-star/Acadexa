@extends('layouts.admin')
@section('title', __('Announcements'))
@section('breadcrumb') <li class="breadcrumb-item active">{{ __('Announcements') }}</li> @endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">{{ __('Announcements') }}</h4>
    <a href="{{ route('admin.announcements.create') }}" class="btn btn-primary">
        <x-icon name="plus-lg" class="me-1" />{{ __('New Announcement') }}
    </a>
</div>

<div class="bg-white rounded-xl shadow-brand overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr><th>{{ __('Title') }}</th><th>{{ __('Audience') }}</th><th>{{ __('Created By') }}</th><th>{{ __('Date') }}</th><th>{{ __('Actions') }}</th></tr>
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
                            {{ __('lms.audience_' . $ann->audience) }}
                        </span>
                    </td>
                    <td style="font-size:.82rem;">{{ $ann->creator?->name }}</td>
                    <td style="font-size:.8rem;">{{ $ann->created_at->isoFormat('ll') }}</td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('admin.announcements.edit', $ann) }}" class="btn btn-primary btn-sm">
                                <x-icon name="pencil" />
                            </a>
                            <form method="POST" action="{{ route('admin.announcements.destroy', $ann) }}">
                                @csrf @method('DELETE')
                                <button class="btn btn-outline-danger btn-sm"
                                        data-confirm="{{ __('Delete this announcement?') }}">
                                    <x-icon name="trash" />
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center py-4 text-muted">{{ __('No announcements yet.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $announcements->links() }}</div>
@endsection
