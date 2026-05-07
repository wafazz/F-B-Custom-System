import './bootstrap';
import '../css/app.css';

import { createInertiaApp, type ResolvedComponent } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';

const appName = import.meta.env.VITE_APP_NAME || 'Star Coffee';

const pages = import.meta.glob<ResolvedComponent>('./pages/**/*.tsx');

createInertiaApp({
    title: (title) => (title ? `${title} — ${appName}` : appName),
    resolve: async (name) => {
        const importer = pages[`./pages/${name}.tsx`];
        if (!importer) throw new Error(`Page not found: ${name}`);
        return await importer();
    },
    setup({ el, App, props }) {
        createRoot(el).render(<App {...props} />);
    },
    progress: {
        color: '#7c4a1e',
    },
});
