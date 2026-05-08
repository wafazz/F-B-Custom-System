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
    const options: NotificationOptions = {
        body: data.body ?? '',
        icon: data.icon ?? '/icons/icon-192.png',
        badge: data.badge ?? '/icons/icon-192.png',
        tag: data.tag,
        data: { url: data.url ?? '/' },
    };

    event.waitUntil(self.registration.showNotification(title, options));
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
