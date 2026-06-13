@extends('layouts.admin')
@section('title', 'Translations')
@section('breadcrumb') <li class="breadcrumb-item active">Translations</li> @endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">Site Translations</h4>
</div>

<div class="row g-4">
    @php
        $locales = config('app.supported_locales', []);
        $localeNames = config('app.locale_names', []);
        $localeFlags = config('app.locale_flags', []);
    @endphp

    @foreach($locales as $locale)
    <div class="col-md-6 col-lg-4">
        <div class="bg-white rounded-xl shadow-brand p-4 text-center">
            <div style="font-size:2rem;">{{ $localeFlags[$locale] ?? '🌐' }}</div>
            <h5 class="mt-2">{{ $localeNames[$locale] ?? strtoupper($locale) }}</h5>
            <p class="text-muted small mb-3">
                <code>{{ $locale }}</code>
                @if($locale === config('app.fallback_locale'))
                <span class="badge bg-primary ms-1">Default</span>
                @endif
            </p>
            <div class="text-muted small mb-3">
                @php
                    $keyCount = 0;
                    foreach (['messages','navigation','auth','courses','dashboard'] as $file) {
                        $path = resource_path("lang/{$locale}/{$file}.php");
                        if (file_exists($path)) {
                            $keys = include $path;
                            $keyCount += is_array($keys) ? count($keys, COUNT_RECURSIVE) : 0;
                        }
                    }
                @endphp
                {{ $keyCount }} keys loaded
            </div>
            <a href="{{ route('admin.translations.index', ['locale' => $locale]) }}" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-translate me-1"></i>Edit Strings
            </a>
                <i class="bi bi-translate me-1"></i>Edit Strings
            </a>
        </div>
    </div>
    @endforeach
</div>

<div class="bg-white rounded-xl shadow-brand p-4 mt-4">
    <h5 class="mb-3">Notes on Translations</h5>
    <ul class="text-muted small mb-0">
        <li>Language files are located in <code>resources/lang/{locale}/</code></li>
        <li>English (en) is the fallback — all other languages fall back to English when a key is missing.</li>
        <li>Content translations (course descriptions, page content) are stored in the database in <code>*_translations</code> tables.</li>
        <li>RTL layout is automatically applied when the locale is set to Arabic (ar).</li>
        <li>To add a new language, contact your system administrator to add the locale to <code>config/app.php</code> and create the lang folder.</li>
    </ul>
</div>
@endsection
