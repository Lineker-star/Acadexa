<?php

return [
    'name' => env('APP_NAME', 'ACADEXA'),
    'env' => env('APP_ENV', 'production'),
    'debug' => (bool) env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost'),
    'timezone' => env('APP_TIMEZONE', 'Africa/Douala'),
    'locale' => env('APP_LOCALE', 'en'),
    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),
    'faker_locale' => env('APP_FAKER_LOCALE', 'en_US'),
    'cipher' => 'AES-256-CBC',
    'key' => env('APP_KEY'),
    'previous_keys' => [
        ...array_filter(
            explode(',', env('APP_PREVIOUS_KEYS', ''))
        ),
    ],
    'maintenance' => ['driver' => 'file'],
    'supported_locales' => ['en', 'fr', 'es', 'pt', 'zh', 'ar'],
    'locale_names' => [
        'en' => 'English',
        'fr' => 'Français',
        'es' => 'Español',
        'pt' => 'Português',
        'zh' => '中文',
        'ar' => 'العربية',
    ],
    'locale_flags' => [
        'en' => '🇬🇧',
        'fr' => '🇫🇷',
        'es' => '🇪🇸',
        'pt' => '🇵🇹',
        'zh' => '🇨🇳',
        'ar' => '🇸🇦',
    ],
    'providers' => Illuminate\Support\ServiceProvider::defaultProviders()->merge([
        App\Providers\AppServiceProvider::class,
    ])->toArray(),
    'aliases' => Illuminate\Support\Facades\Facade::defaultAliases()->merge([
        'PDF' => Barryvdh\DomPDF\Facade\Pdf::class,
    ])->toArray(),
];
