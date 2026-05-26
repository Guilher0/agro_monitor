import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    server: {
        host: '0.0.0.0',
        port: 5175,
        strictPort: true,
        origin: 'http://localhost:5175',
        cors: {
            origin: ['http://localhost:8095', 'http://127.0.0.1:8095'],
            credentials: true,
        },
        hmr: {
            host: 'localhost',
            port: 5175,
        },
    },
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
});
