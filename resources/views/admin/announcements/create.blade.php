@extends('layouts.admin')
@section('title', 'New Announcement')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.announcements.index') }}">Announcements</a></li>
    <li class="breadcrumb-item active">New</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="bg-white rounded-xl shadow-brand p-4">
            <h5 class="mb-4">New Announcement</h5>
            <form method="POST" action="{{ route('admin.announcements.store') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label fw-bold">Title (English) *</label>
                    <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
                           value="{{ old('title') }}" required>
                    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Title (French)</label>
                    <input type="text" name="titles[fr]" class="form-control" value="{{ old('titles.fr') }}">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Content (English) *</label>
                    <textarea name="content" class="form-control @error('content') is-invalid @enderror"
                              rows="5" required>{{ old('content') }}</textarea>
                    @error('content')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Content (French)</label>
                    <textarea name="contents[fr]" class="form-control" rows="4">{{ old('contents.fr') }}</textarea>
                </div>
                <div class="mb-4">
                    <label class="form-label fw-bold">Audience *</label>
                    <select name="audience" class="form-select" required>
                        <option value="all" {{ old('audience')=='all'?'selected':'' }}>Everyone</option>
                        <option value="students" {{ old('audience')=='students'?'selected':'' }}>Students Only</option>
                        <option value="instructors" {{ old('audience')=='instructors'?'selected':'' }}>Instructors Only</option>
                    </select>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Publish Announcement</button>
                    <a href="{{ route('admin.announcements.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
