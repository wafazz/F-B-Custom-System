import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import { VitePWA } from 'vite-plugin-pwa';
import path from 'node:path';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            ssr: 'resources/js/ssr.tsx',
            refresh: true,
        }),
        react(),
        tailwindcss(),
        VitePWA({
            registerType: 'autoUpdate',
            strategies: 'injectManifest',
            srcDir: 'resources/js',
            filename: 'sw.ts',
            injectRegister: 'auto',
            devOptions: { enabled: true, type: 'module' },
            includeAssets: ['favicon.png', 'apple-touch-icon.png', 'sounds/sc7.mp3'],
            manifest: {
                name: 'Star Coffee House',
                short_name: 'Star Coffee',
                description: 'Star Coffee — F&B order, loyalty & rewards',
                theme_color: '#000000',
                background_color: '#000000',
                display: 'standalone',
                orientation: 'portrait',
                start_url: '/',
                scope: '/',
                icons: [
                    {
                        src: '/icons/icon-192.png',
                        sizes: '192x192',
                        type: 'image/png',
                        purpose: 'any maskable',
                    },
                    {
                        src: '/icons/icon-512.png',
                        sizes: '512x512',
                        type: 'image/png',
                        purpose: 'any maskable',
                    },
                ],
            },
            injectManifest: {
                globPatterns: ['**/*.{js,css,html,ico,png,svg,woff2,mp3}'],
                globIgnores: ['**/registerSW.js'],
                additionalManifestEntries: [
                    { url: '/sounds/sc7.mp3', revision: '1' },
                ],
                // SW is served from origin root (/sw.js), but the actual
                // assets live under /build/. Prefix relative manifest
                // URLs so they resolve correctly. Skip entries that are
                // already absolute (start with /).
                manifestTransforms: [
                    async (entries) => ({
                        manifest: entries.map((entry) =>
                            entry.url.startsWith('/')
                                ? entry
                                : { ...entry, url: `/build/${entry.url}` },
                        ),
                        warnings: [],
                    }),
                ],
            },
        }),
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, './resources/js'),
        },
    },
    server: {
        host: '127.0.0.1',
        port: 5173,
        strictPort: false,
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
        hmr: {
            host: '127.0.0.1',
        },
    },
});
