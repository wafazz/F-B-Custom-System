import type { PageProps as AppPageProps } from '@/types';
import type { route as routeFn } from 'ziggy-js';

declare global {
    const route: typeof routeFn;
    interface Window {
        route: typeof routeFn;
    }
}

declare module '@inertiajs/core' {
    interface PageProps extends AppPageProps {}
}

declare module '@inertiajs/react' {
    export function usePage<T extends object = AppPageProps>(): {
        props: T;
        url: string;
        component: string;
        version: string | null;
    };
}

export {};
