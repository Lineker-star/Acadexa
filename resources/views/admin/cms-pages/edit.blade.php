@extends('layouts.admin')
@section('title', __('Edit Page'))
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.cms-pages.index') }}">{{ __('CMS Pages') }}</a></li>
    <li class="breadcrumb-item active">{{ $cmsPage->slug }}</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="bg-white rounded-xl shadow-brand p-4">
            <h5 class="mb-4">{{ __('Edit Page:') }} <code>{{ $cmsPage->slug }}</code></h5>
            <form method="POST" action="{{ route('admin.cms-pages.update', $cmsPage) }}" enctype="multipart/form-data">
                @csrf @method('PUT')

                @include('admin.partials.translation-tabs', [
                    'id' => 'page',
                    'fields' => [
                        'title'   => ['label' => __('Title')],
                        'content' => ['label' => __('Content (HTML allowed)'), 'type' => 'textarea', 'rows' => 14],
                    ],
                    'value' => fn ($loc, $field) => $cmsPage->translations->firstWhere('locale', $loc)?->{$field},
                ])

                <div class="mb-4">
                    <label class="form-label fw-bold" for="hero_image">{{ __('Hero Background Image') }}</label>
                    @if($cmsPage->hero_image)
                        <div class="mb-2">
                            <img src="{{ Storage::url($cmsPage->hero_image) }}" alt="" style="height:120px;object-fit:cover;border-radius:8px;">
                        </div>
                    @endif
                    <input type="file" name="hero_image" id="hero_image" class="form-control" accept="image/*">
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><x-icon name="save" class="me-1" />{{ __('Save Page') }}</button>
                    <a href="{{ route('admin.cms-pages.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
                    <a href="{{ route('cms.page', $cmsPage->slug) }}" target="_blank" class="btn btn-outline-secondary ms-auto">
                        <x-icon name="eye" class="me-1" />{{ __('Preview') }}
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
