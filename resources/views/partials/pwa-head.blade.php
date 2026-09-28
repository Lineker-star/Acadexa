{{-- PWA: manifest, icons and the strings used by resources/js/pwa.js --}}
<link rel="manifest" href="{{ route('pwa.manifest') }}">
<meta name="theme-color" content="#0A2A5E">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="{{ $siteSettings['site_name'] ?? 'ACADEXA' }}">
<link rel="apple-touch-icon" href="{{ route('pwa.icon', 'apple-touch-icon.png') }}">
<script>
    window.ACADEXA_I18N = @json(array_merge(trans('lms.js'), trans('learn.js')));
    window.ACADEXA_ICONS = @json(\App\Support\Icons::url());
</script>
