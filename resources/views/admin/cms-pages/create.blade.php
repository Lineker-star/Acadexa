@extends('layouts.admin')
@section('title', __('New CMS Page'))
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.cms-pages.index') }}">{{ __('CMS Pages') }}</a></li>
    <li class="breadcrumb-item active">{{ __('New') }}</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="bg-white rounded-xl shadow-brand p-4">
            <h5 class="mb-4">{{ __('New CMS Page') }}</h5>
            <form method="POST" action="{{ route('admin.cms-pages.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="mb-3" style="max-width:420px">
                    <label class="form-label fw-bold" for="slug">{{ __('URL Slug *') }}</label>
                    <div class="input-group">
                        <span class="input-group-text text-muted" style="font-size:.85rem;">/page/</span>
                        <input type="text" name="slug" id="slug" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug') }}"
                               required pattern="[a-z0-9-]+" placeholder="{{ __('my-page') }}">
                        @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-text">{{ __('Lowercase letters, numbers, and hyphens only') }}</div>
                </div>

                @include('admin.partials.translation-tabs', [
                    'id' => 'page',
                    'fields' => [
                        'title'   => ['label' => __('Title')],
                        'content' => ['label' => __('Content (HTML allowed)'), 'type' => 'textarea', 'rows' => 14],
                    ],
                    'value' => fn ($loc, $field) => null,
                ])

                <div class="mb-4">
                    <label class="form-label fw-bold" for="hero_image">{{ __('Hero Image (optional)') }}</label>
                    <input type="file" name="hero_image" id="hero_image" class="form-control" accept="image/*">
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">{{ __('Create Page') }}</button>
                    <a href="{{ route('admin.cms-pages.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
