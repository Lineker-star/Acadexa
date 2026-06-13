@extends('layouts.admin')
@section('title', 'Edit Page')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.cms-pages.index') }}">CMS Pages</a></li>
    <li class="breadcrumb-item active">{{ $page->slug }}</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="bg-white rounded-xl shadow-brand p-4">
            <h5 class="mb-4">Edit Page: <code>{{ $page->slug }}</code></h5>
            <form method="POST" action="{{ route('admin.cms-pages.update', $page) }}" enctype="multipart/form-data">
                @csrf @method('PUT')

                <!-- English (required) -->
                <div class="card border-primary mb-3">
                    <div class="card-header bg-primary text-white py-2">English</div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Page Title *</label>
                            <input type="text" name="title" class="form-control"
                                   value="{{ old('title', $page->title('en')) }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Hero Subtitle</label>
                            <input type="text" name="subtitle" class="form-control"
                                   value="{{ old('subtitle', $page->translations->where('locale','en')->first()?->subtitle) }}">
                        </div>
                        <div class="mb-0">
                            <label class="form-label fw-bold">Content (HTML allowed)</label>
                            <textarea name="content" class="form-control" rows="12">{{ old('content', $page->content('en')) }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- French -->
                <div class="card border-secondary mb-3">
                    <div class="card-header bg-light py-2">French</div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Page Title (FR)</label>
                            <input type="text" name="titles[fr]" class="form-control"
                                   value="{{ old('titles.fr', $page->title('fr')) }}">
                        </div>
                        <div class="mb-0">
                            <label class="form-label fw-bold">Content (FR)</label>
                            <textarea name="contents[fr]" class="form-control" rows="8">{{ old('contents.fr', $page->content('fr')) }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- Hero Image -->
                <div class="mb-4">
                    <label class="form-label fw-bold">Hero Background Image</label>
                    @if($page->hero_image)
                    <div class="mb-2">
                        <img src="{{ Storage::url($page->hero_image) }}" alt="Hero"
                             style="height:120px;object-fit:cover;border-radius:8px;">
                    </div>
                    @endif
                    <input type="file" name="hero_image" class="form-control" accept="image/*">
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Save Page</button>
                    <a href="{{ route('admin.cms-pages.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    <a href="{{ route('page', $page->slug) }}" target="_blank" class="btn btn-outline-secondary ms-auto">
                        <i class="bi bi-eye me-1"></i>Preview
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
