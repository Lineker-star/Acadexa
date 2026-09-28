@extends('layouts.admin')
@section('title', __('Categories'))
@section('breadcrumb') <li class="breadcrumb-item active">{{ __('Categories') }}</li> @endsection

@php
    $locales = config('app.supported_locales');
    $localeNames = config('app.locale_names');
    $icons = config('lms.category_icons');
@endphp

@section('content')
<div class="row g-4">
    {{-- Create --}}
    <div class="col-lg-4">
        <div class="bg-white rounded-xl shadow-brand p-4">
            <h5 class="mb-4">{{ __('Add Category') }}</h5>
            <form method="POST" action="{{ route('admin.categories.store') }}">
                @csrf
                @foreach($locales as $loc)
                    <div class="mb-2">
                        <label class="form-label small fw-bold" for="new-name-{{ $loc }}">
                            {{ __('Name') }} — {{ $localeNames[$loc] }} @if($loc === 'en') * @endif
                        </label>
                        <input type="text" id="new-name-{{ $loc }}" name="names[{{ $loc }}]" class="form-control form-control-sm" maxlength="255" @required($loc === 'en') dir="{{ $loc === 'ar' ? 'rtl' : 'ltr' }}">
                    </div>
                @endforeach
                <div class="mb-3 mt-3">
                    <label class="form-label small fw-bold">{{ __('Icon') }}</label>
                    @include('admin.categories.partials.icon-picker', ['field' => 'new', 'selected' => 'bi-folder'])
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold" for="parent_id">{{ __('Parent Category') }}</label>
                    <select name="parent_id" id="parent_id" class="form-select">
                        <option value="">{{ __('None (root category)') }}</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name() }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-primary w-100">{{ __('Add Category') }}</button>
            </form>
        </div>
    </div>

    {{-- List --}}
    <div class="col-lg-8">
        <div class="bg-white rounded-xl shadow-brand p-4">
            <h5 class="mb-4">{{ __('All Categories') }}</h5>
            @foreach($categories as $cat)
                <div class="d-flex align-items-center gap-3 mb-3 pb-3 border-bottom">
                    <x-icon :name="$cat->icon ?: 'folder'" class="text-primary" style="font-size:1.5rem" />
                    <div class="flex-grow-1">
                        <div class="fw-bold" style="font-size:.9rem;">{{ $cat->name() }}</div>
                        <div class="text-muted" style="font-size:.75rem;">
                            {{ trans_choice(':count course|:count courses', $cat->courses_count, ['count' => $cat->courses_count]) }}
                            @if($cat->children->count())
                                · {{ trans_choice(':count subcategory|:count subcategories', $cat->children->count(), ['count' => $cat->children->count()]) }}
                            @endif
                            · {{ __(':done of :total languages', ['done' => $cat->translations->count(), 'total' => count($locales)]) }}
                        </div>
                    </div>
                    <div class="d-flex gap-1">
                        <span class="badge {{ $cat->is_active ? 'bg-success' : 'bg-secondary' }}">
                            {{ $cat->is_active ? __('Active') : __('Inactive') }}
                        </span>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editCat{{ $cat->id }}" title="{{ __('Edit') }}" aria-label="{{ __('Edit') }}">
                            <x-icon name="pencil" />
                        </button>
                        <form method="POST" action="{{ route('admin.categories.destroy', $cat) }}" data-confirm="{{ __('Delete this category?') }}">
                            @csrf @method('DELETE')
                            <button class="btn btn-outline-danger btn-sm" aria-label="{{ __('Delete') }}"><x-icon name="trash" /></button>
                        </form>
                    </div>
                </div>

                @foreach($cat->children as $child)
                    <div class="d-flex align-items-center gap-3 mb-2 pb-2 border-bottom ms-4">
                        <x-icon :name="$child->icon ?: 'arrow-return-right'" class="text-muted" />
                        <div class="flex-grow-1" style="font-size:.85rem;">{{ $child->name() }}</div>
                        <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#editCat{{ $child->id }}" aria-label="{{ __('Edit') }}"><x-icon name="pencil" /></button>
                        <form method="POST" action="{{ route('admin.categories.destroy', $child) }}" data-confirm="{{ __('Delete subcategory?') }}">
                            @csrf @method('DELETE')
                            <button class="btn btn-outline-danger btn-sm" aria-label="{{ __('Delete') }}"><x-icon name="trash" /></button>
                        </form>
                    </div>
                @endforeach
            @endforeach
        </div>
    </div>
</div>

{{-- Edit modals (categories and subcategories) --}}
@foreach($categories->flatMap(fn ($c) => collect([$c])->merge($c->children)) as $cat)
    <div class="modal fade" id="editCat{{ $cat->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable">
            <form method="POST" action="{{ route('admin.categories.update', $cat) }}" class="modal-content">
                @csrf @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Edit: :name', ['name' => $cat->name()]) }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                </div>
                <div class="modal-body">
                    @foreach($locales as $loc)
                        <div class="mb-2">
                            <label class="form-label small fw-bold" for="name-{{ $cat->id }}-{{ $loc }}">{{ __('Name') }} — {{ $localeNames[$loc] }} @if($loc === 'en') * @endif</label>
                            <input type="text" id="name-{{ $cat->id }}-{{ $loc }}" name="names[{{ $loc }}]" class="form-control form-control-sm" maxlength="255"
                                   value="{{ $cat->translations->firstWhere('locale', $loc)?->name ?? ($loc === 'en' ? $cat->name : '') }}"
                                   @required($loc === 'en') dir="{{ $loc === 'ar' ? 'rtl' : 'ltr' }}">
                        </div>
                    @endforeach
                    <div class="mb-3 mt-3">
                        <label class="form-label small fw-bold">{{ __('Icon') }}</label>
                        @include('admin.categories.partials.icon-picker', ['field' => $cat->id, 'selected' => $cat->icon ?: 'bi-folder'])
                    </div>
                    <div class="form-check">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" class="form-check-input" name="is_active" value="1" id="active-{{ $cat->id }}" @checked($cat->is_active)>
                        <label class="form-check-label small" for="active-{{ $cat->id }}">{{ __('Active') }}</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Save Changes') }}</button>
                </div>
            </form>
        </div>
    </div>
@endforeach
@endsection
