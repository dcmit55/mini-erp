import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/sass/app.scss',
                'resources/js/app.js',
                'resources/css/qc.css',
                'resources/js/qc/main.jsx',
            ],
            refresh: true,
        }),
        react(),
        tailwindcss(),
    ],
});
