@extends('layouts.admin')
@section('title', __('New Announcement'))
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.announcements.index') }}">{{ __('Announcements') }}</a></li>
    <li class="breadcrumb-item active">{{ __('New') }}</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="bg-white rounded-xl shadow-brand p-4">
            <h5 class="mb-4">{{ __('New Announcement') }}</h5>
            <form method="POST" action="{{ route('admin.announcements.store') }}">
                @csrf
                @include('admin.announcements.partials.fields', ['announcement' => null])
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">{{ __('Publish Announcement') }}</button>
                    <a href="{{ route('admin.announcements.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
