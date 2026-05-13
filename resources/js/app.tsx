import './bootstrap';
import '../css/app.css';

import { createInertiaApp, router, type ResolvedComponent } from '@inertiajs/react';
import { QueryClientProvider } from '@tanstack/react-query';
import { createElement } from 'react';
import { createRoot } from 'react-dom/client';
import { registerSW } from 'virtual:pwa-register';
import { queryClient } from '@/lib/query-client';

if ('serviceWorker' in navigator) {
    registerSW({ immediate: true });
}

const isPwa =
    typeof window !== 'undefined' &&
    (window.matchMedia?.('(display-mode: standalone)').matches ||
        (window.navigator as Navigator & { standalone?: boolean }).standalone === true);

router.on('before', (event) => {
    event.detail.visit.headers['X-Channel'] = isPwa ? 'pwa' : 'web';
});

const appName = import.meta.env.VITE_APP_NAME || 'Star Coffee';

const pages = import.meta.glob<ResolvedComponent>('./pages/**/*.tsx');

void createInertiaApp({
    title: (title) => (title ? `${title} — ${appName}` : appName),
    resolve: async (name) => {
        const importer = pages[`./pages/${name}.tsx`];
        if (!importer) throw new Error(`Page not found: ${name}`);
        return await importer();
    },
    setup({ el, App, props }) {
        createRoot(el).render(
            createElement(QueryClientProvider, { client: queryClient }, createElement(App, props)),
        );
    },
    progress: {
        color: '#7c4a1e',
    },
});
