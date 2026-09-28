@extends('layouts.admin')
@section('title', __('Edit Announcement'))
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.announcements.index') }}">{{ __('Announcements') }}</a></li>
    <li class="breadcrumb-item active">{{ __('Edit') }}</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="bg-white rounded-xl shadow-brand p-4">
            <h5 class="mb-4">{{ __('Edit Announcement') }}</h5>
            <form method="POST" action="{{ route('admin.announcements.update', $announcement) }}">
                @csrf @method('PUT')
                @include('admin.announcements.partials.fields', ['announcement' => $announcement])
                <div class="form-check mb-4">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" class="form-check-input" name="is_active" value="1" id="is_active" @checked(old('is_active', $announcement->is_active))>
                    <label class="form-check-label" for="is_active">{{ __('Active') }}</label>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">{{ __('Save Changes') }}</button>
                    <a href="{{ route('admin.announcements.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
