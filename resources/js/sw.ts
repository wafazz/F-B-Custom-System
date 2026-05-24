/// <reference lib="webworker" />
import { precacheAndRoute } from 'workbox-precaching';

declare const self: ServiceWorkerGlobalScope;

// eslint-disable-next-line @typescript-eslint/no-explicit-any
precacheAndRoute((self as any).__WB_MANIFEST ?? []);

self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', (event) => event.waitUntil(self.clients.claim()));

interface PushPayload {
    title?: string;
    body?: string;
    url?: string;
    tag?: string;
    icon?: string;
    badge?: string;
}

function parsePayload(data: PushMessageData | null): PushPayload {
    if (!data) return {};
    try {
        return (data.json() as PushPayload) ?? {};
    } catch {
        return { title: 'Star Coffee', body: data.text() };
    }
}

self.addEventListener('push', (event) => {
    const data = parsePayload(event.data);
    const title = data.title ?? 'Star Coffee';
    // renotify is valid at runtime but missing from lib.dom's NotificationOptions.
    const options: NotificationOptions & { renotify?: boolean } = {
        body: data.body ?? '',
        icon: data.icon ?? '/icons/icon-192.png',
        badge: data.badge ?? '/icons/icon-192.png',
        tag: data.tag,
        // Re-alert when a new push reuses an existing tag (e.g. repeat test
        // sends) instead of silently replacing it. renotify requires a tag.
        renotify: data.tag ? true : undefined,
        data: { url: data.url ?? '/' },
    };

    event.waitUntil(
        self.registration.showNotification(title, options).catch((err: unknown) => {
            // Permission can revoke between subscribe + push delivery.
            // Swallow rather than crash the SW.
            console.warn('push showNotification failed', err);
        }),
    );
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const url = (event.notification.data as { url?: string } | null)?.url ?? '/';
    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((all) => {
            for (const client of all) {
                if (client.url.endsWith(url) && 'focus' in client) return client.focus();
            }
            return self.clients.openWindow(url);
        }),
    );
});
