import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/player.js',
                'resources/js/course-builder.js',
                'resources/js/offline-app.js',
                'resources/js/library-page.js',
            ],
            refresh: true,
        }),
    ],
});
