@extends('layouts.admin')
@section('title', 'Categories')
@section('breadcrumb') <li class="breadcrumb-item active">Categories</li> @endsection

@section('content')
<div class="row g-4">
    <!-- Create Category Form -->
    <div class="col-lg-4">
        <div class="bg-white rounded-xl shadow-brand p-4">
            <h5 class="mb-4">Add Category</h5>
            <form method="POST" action="{{ route('admin.categories.store') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label small fw-bold">Name (English) *</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Name (French)</label>
                    <input type="text" name="names[fr]" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Icon (emoji)</label>
                    <input type="text" name="icon" class="form-control" placeholder="e.g. 💻">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Parent Category</label>
                    <select name="parent_id" class="form-select">
                        <option value="">None (root category)</option>
                        @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-primary w-100">Add Category</button>
            </form>
        </div>
    </div>

    <!-- Category List -->
    <div class="col-lg-8">
        <div class="bg-white rounded-xl shadow-brand p-4">
            <h5 class="mb-4">All Categories</h5>
            @foreach($categories as $cat)
            <div class="d-flex align-items-center gap-3 mb-3 pb-3 border-bottom">
                <span style="font-size:1.5rem;min-width:2rem;">{{ $cat->icon ?? '📁' }}</span>
                <div class="flex-grow-1">
                    <div class="fw-bold" style="font-size:.9rem;">{{ $cat->name }}</div>
                    <div class="text-muted" style="font-size:.75rem;">
                        {{ $cat->courses()->count() }} courses
                        @if($cat->children->count())
                        • {{ $cat->children->count() }} subcategories
                        @endif
                    </div>
                </div>
                <div class="d-flex gap-1">
                    <span class="badge {{ $cat->is_active ? 'bg-success' : 'bg-secondary' }}">
                        {{ $cat->is_active ? 'Active' : 'Inactive' }}
                    </span>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal"
                            data-bs-target="#editCat{{ $cat->id }}" title="Edit">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <form method="POST" action="{{ route('admin.categories.destroy', $cat) }}">
                        @csrf @method('DELETE')
                        <button class="btn btn-outline-danger btn-sm"
                                onclick="return confirm('Delete this category?')">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Children -->
            @foreach($cat->children as $child)
            <div class="d-flex align-items-center gap-3 mb-2 pb-2 border-bottom ms-4">
                <span style="font-size:1rem;min-width:1.5rem;">{{ $child->icon ?? '└' }}</span>
                <div class="flex-grow-1">
                    <div style="font-size:.85rem;">{{ $child->name }}</div>
                </div>
                <form method="POST" action="{{ route('admin.categories.destroy', $child) }}">
                    @csrf @method('DELETE')
                    <button class="btn btn-outline-danger btn-sm"
                            onclick="return confirm('Delete subcategory?')">
                        <i class="bi bi-trash"></i>
                    </button>
                </form>
            </div>
            @endforeach

            <!-- Edit Modal -->
            <div class="modal fade" id="editCat{{ $cat->id }}" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Edit: {{ $cat->name }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form method="POST" action="{{ route('admin.categories.update', $cat) }}">
                            @csrf @method('PUT')
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">Name (EN)</label>
                                    <input type="text" name="name" class="form-control" value="{{ $cat->name }}" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">Name (FR)</label>
                                    <input type="text" name="names[fr]" class="form-control"
                                           value="{{ $cat->translations->where('locale','fr')->first()?->name }}">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">Icon</label>
                                    <input type="text" name="icon" class="form-control" value="{{ $cat->icon }}">
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" name="is_active"
                                           {{ $cat->is_active ? 'checked' : '' }}>
                                    <label class="form-check-label small">Active</label>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
