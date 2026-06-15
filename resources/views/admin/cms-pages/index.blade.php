@extends('layouts.admin')
@section('title', 'CMS Pages')
@section('breadcrumb') <li class="breadcrumb-item active">CMS Pages</li> @endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">CMS Pages</h4>
    <a href="{{ route('admin.cms-pages.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i>New Page
    </a>
</div>

<div class="bg-white rounded-xl shadow-brand overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr><th>Page</th><th>Slug</th><th>Updated</th><th>Actions</th></tr>
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
                    <td style="font-size:.8rem;">{{ $page->updated_at->format('M d, Y') }}</td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('cms.page', $page->slug) }}" target="_blank" class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="{{ route('admin.cms-pages.edit', $page) }}" class="btn btn-primary btn-sm">
                                <i class="bi bi-pencil"></i>
                            </a>
                            @if(!in_array($page->slug, ['about','privacy','terms']))
                            <form method="POST" action="{{ route('admin.cms-pages.destroy', $page) }}">
                                @csrf @method('DELETE')
                                <button class="btn btn-outline-danger btn-sm"
                                        onclick="return confirm('Delete this page?')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="text-center py-4 text-muted">No CMS pages found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
