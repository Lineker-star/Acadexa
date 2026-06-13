@extends('layouts.admin')
@section('title', 'Edit Announcement')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.announcements.index') }}">Announcements</a></li>
    <li class="breadcrumb-item active">Edit</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="bg-white rounded-xl shadow-brand p-4">
            <h5 class="mb-4">Edit Announcement</h5>
            <form method="POST" action="{{ route('admin.announcements.update', $announcement) }}">
                @csrf @method('PUT')
                <div class="mb-3">
                    <label class="form-label fw-bold">Title (English) *</label>
                    <input type="text" name="title" class="form-control" value="{{ old('title', $announcement->title('en')) }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Title (French)</label>
                    <input type="text" name="titles[fr]" class="form-control"
                           value="{{ old('titles.fr', $announcement->title('fr')) }}">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Content (English) *</label>
                    <textarea name="content" class="form-control" rows="5" required>{{ old('content', $announcement->content('en')) }}</textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Content (French)</label>
                    <textarea name="contents[fr]" class="form-control" rows="4">{{ old('contents.fr', $announcement->content('fr')) }}</textarea>
                </div>
                <div class="mb-4">
                    <label class="form-label fw-bold">Audience *</label>
                    <select name="audience" class="form-select" required>
                        <option value="all" {{ $announcement->audience=='all'?'selected':'' }}>Everyone</option>
                        <option value="students" {{ $announcement->audience=='students'?'selected':'' }}>Students Only</option>
                        <option value="instructors" {{ $announcement->audience=='instructors'?'selected':'' }}>Instructors Only</option>
                    </select>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                    <a href="{{ route('admin.announcements.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
