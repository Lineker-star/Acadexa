@extends('layouts.admin')
@section('title', __('Translations'))
@section('breadcrumb') <li class="breadcrumb-item active">{{ __('Translations') }}</li> @endsection

@php $localeNames = config('app.locale_names'); @endphp

@section('content')
<h4 class="fw-bold mb-3">{{ __('Site Translations') }}</h4>

{{-- Interface coverage --}}
<div class="row g-3 mb-4">
    @foreach($coverage as $locale => $row)
        @php $pct = $row['total'] ? (int) floor($row['translated'] / $row['total'] * 100) : 100; @endphp
        <div class="col-6 col-lg-2">
            <div class="bg-white rounded-xl shadow-brand p-3 h-100">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge bg-primary-subtle text-primary">{{ strtoupper($locale) }}</span>
                    <strong class="small">{{ $localeNames[$locale] ?? $locale }}</strong>
                </div>
                <div class="progress mb-1" style="height:6px"><div class="progress-bar {{ $pct === 100 ? 'bg-success' : 'bg-warning' }}" style="width:{{ $pct }}%"></div></div>
                <div class="small text-muted">{{ __(':count% of the interface', ['count' => $pct]) }}</div>
                @if($row['missing'])
                    <details class="small mt-1">
                        <summary>{{ trans_choice(':count missing string|:count missing strings', count($row['missing']), ['count' => count($row['missing'])]) }}</summary>
                        <ul class="ps-3 mb-0 mt-1" style="max-height:160px;overflow:auto">
                            @foreach(array_slice($row['missing'], 0, 50) as $key)<li><code>{{ $key }}</code></li>@endforeach
                        </ul>
                    </details>
                @endif
            </div>
        </div>
    @endforeach
</div>

{{-- Content editor --}}
<div class="bg-white rounded-xl shadow-brand p-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h5 class="mb-0">{{ __('Content translations') }}</h5>
        <div class="btn-group btn-group-sm">
            <a href="{{ route('admin.translations.index', ['type' => 'categories']) }}" class="btn {{ $type === 'categories' ? 'btn-primary' : 'btn-outline-primary' }}">{{ __('Categories') }}</a>
            <a href="{{ route('admin.translations.index', ['type' => 'cms-pages']) }}" class="btn {{ $type === 'cms-pages' ? 'btn-primary' : 'btn-outline-primary' }}">{{ __('CMS Pages') }}</a>
        </div>
    </div>
    <p class="small text-muted">{{ __('Empty fields fall back to English. Page contents are edited in CMS Pages; course contents are translated by their instructor.') }}</p>

    <form method="POST" action="{{ route('admin.translations.update') }}">
        @csrf
        <input type="hidden" name="type" value="{{ $type }}">
        <div class="table-responsive">
            <table class="table table-sm align-middle">
                <thead class="small text-muted">
                    <tr>
                        <th style="min-width:40px"></th>
                        @foreach($locales as $locale)<th style="min-width:170px">{{ $localeNames[$locale] }}</th>@endforeach
                    </tr>
                </thead>
                <tbody>
                @foreach($items as $item)
                    <tr>
                        <td>
                            @if($type === 'categories')
                                <x-icon :name="$item->icon ?: 'folder'" class="text-primary" />
                            @else
                                <code class="small">{{ $item->slug }}</code>
                            @endif
                        </td>
                        @foreach($locales as $locale)
                            @php $tr = $item->translations->firstWhere('locale', $locale); @endphp
                            <td>
                                <input type="text" class="form-control form-control-sm" maxlength="255" dir="{{ $locale === 'ar' ? 'rtl' : 'ltr' }}"
                                       name="translations[{{ $item->id }}][{{ $locale }}]"
                                       value="{{ $type === 'categories' ? $tr?->name : $tr?->title }}"
                                       aria-label="{{ $localeNames[$locale] }}">
                            </td>
                        @endforeach
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <button class="btn btn-primary"><x-icon name="save" class="me-1" />{{ __('Save') }}</button>
    </form>
</div>
@endsection
