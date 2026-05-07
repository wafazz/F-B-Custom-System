import { createInertiaApp, type ResolvedComponent } from '@inertiajs/react';
import createServer from '@inertiajs/react/server';
import ReactDOMServer from 'react-dom/server';

const appName = import.meta.env.VITE_APP_NAME || 'Star Coffee';

const pages = import.meta.glob<ResolvedComponent>('./pages/**/*.tsx');

createServer((page) =>
    createInertiaApp({
        page,
        render: ReactDOMServer.renderToString,
        title: (title) => (title ? `${title} — ${appName}` : appName),
        resolve: async (name) => {
            const importer = pages[`./pages/${name}.tsx`];
            if (!importer) throw new Error(`Page not found: ${name}`);
            return await importer();
        },
        setup: ({ App, props }) => <App {...props} />,
    }),
);
