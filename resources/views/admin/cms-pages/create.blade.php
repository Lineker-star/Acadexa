@extends('layouts.admin')
@section('title', 'New Page')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.cms-pages.index') }}">CMS Pages</a></li>
    <li class="breadcrumb-item active">New Page</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="bg-white rounded-xl shadow-brand p-4">
            <h5 class="mb-4">New CMS Page</h5>
            <form method="POST" action="{{ route('admin.cms-pages.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <label class="form-label fw-bold">URL Slug *</label>
                    <div class="input-group">
                        <span class="input-group-text text-muted" style="font-size:.85rem;">/page/</span>
                        <input type="text" name="slug" class="form-control" placeholder="my-page" required
                               pattern="[a-z0-9-]+" title="Lowercase letters, numbers, and hyphens only">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Title (English) *</label>
                    <input type="text" name="title" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Content (English) *</label>
                    <textarea name="content" class="form-control" rows="10" required></textarea>
                </div>
                <div class="mb-4">
                    <label class="form-label fw-bold">Hero Image (optional)</label>
                    <input type="file" name="hero_image" class="form-control" accept="image/*">
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Create Page</button>
                    <a href="{{ route('admin.cms-pages.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
