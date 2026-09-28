@extends('layouts.admin')
@section('title', __('CMS Pages'))
@section('breadcrumb') <li class="breadcrumb-item active">{{ __('CMS Pages') }}</li> @endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">{{ __('CMS Pages') }}</h4>
    <a href="{{ route('admin.cms-pages.create') }}" class="btn btn-primary">
        <x-icon name="plus-lg" class="me-1" />{{ __('New Page') }}
    </a>
</div>

<div class="bg-white rounded-xl shadow-brand overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr><th>{{ __('Page') }}</th><th>{{ __('Slug') }}</th><th>{{ __('Updated') }}</th><th>{{ __('Actions') }}</th></tr>
            </thead>
            <tbody>
                @forelse($pages as $page)
                <tr>
                    <td>
                        <div style="font-size:.9rem;font-weight:600;">{{ $page->translation()?->title }}</div>
                    </td>
                    <td>
                        <code class="bg-light px-2 py-1 rounded" style="font-size:.78rem;">{{ $page->slug }}</code>
                    </td>
                    <td style="font-size:.8rem;">{{ $page->updated_at->isoFormat('ll') }}</td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('cms.page', $page->slug) }}" target="_blank" class="btn btn-outline-secondary btn-sm">
                                <x-icon name="eye" />
                            </a>
                            <a href="{{ route('admin.cms-pages.edit', $page) }}" class="btn btn-primary btn-sm">
                                <x-icon name="pencil" />
                            </a>
                            @if(!in_array($page->slug, ['about','privacy','terms']))
                            <form method="POST" action="{{ route('admin.cms-pages.destroy', $page) }}">
                                @csrf @method('DELETE')
                                <button class="btn btn-outline-danger btn-sm"
                                        data-confirm="{{ __('Delete this page?') }}">
                                    <x-icon name="trash" />
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="text-center py-4 text-muted">{{ __('No CMS pages found.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
