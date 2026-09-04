import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/admin.css',
                'resources/css/landing.css',
                'resources/js/app.js',
                'resources/js/echo.js',
                'resources/js/ejecutivo.jsx',
            ],
            refresh: true,
        }),
        react(),
        tailwindcss(),
    ],
});
