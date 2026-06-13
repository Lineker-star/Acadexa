<?php

return [
    'default' => env('FILESYSTEM_DISK', 'local'),

    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
        ],

        // Custom disks for organized storage
        'avatars' => [
            'driver' => 'local',
            'root' => storage_path('app/public/avatars'),
            'url' => env('APP_URL').'/storage/avatars',
            'visibility' => 'public',
        ],

        'thumbnails' => [
            'driver' => 'local',
            'root' => storage_path('app/public/thumbnails'),
            'url' => env('APP_URL').'/storage/thumbnails',
            'visibility' => 'public',
        ],

        'certificates' => [
            'driver' => 'local',
            'root' => storage_path('app/public/certificates'),
            'url' => env('APP_URL').'/storage/certificates',
            'visibility' => 'public',
        ],
    ],

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],
];
