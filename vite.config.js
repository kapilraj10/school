import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/home-legacy.css',
                'resources/js/app.js',
                'resources/js/pages/home.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
    // server: {
    //     hmr: {
    //         host: 'stellate-sincere-soledad.ngrok-free.dev', // Your ngrok URL
    //     },
    // },
});
