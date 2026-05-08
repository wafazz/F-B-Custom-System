function urlBase64ToArrayBuffer(base64String: string): ArrayBuffer {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const raw = window.atob(base64);
    const buf = new ArrayBuffer(raw.length);
    const view = new Uint8Array(buf);
    for (let i = 0; i < raw.length; ++i) view[i] = raw.charCodeAt(i);
    return buf;
}

export function pushSupported(): boolean {
    return typeof window !== 'undefined' && 'serviceWorker' in navigator && 'PushManager' in window;
}

export async function getActiveRegistration(): Promise<ServiceWorkerRegistration | null> {
    if (!pushSupported()) return null;
    return (await navigator.serviceWorker.getRegistration()) ?? null;
}

export async function getCurrentSubscription(): Promise<PushSubscription | null> {
    const reg = await getActiveRegistration();
    if (!reg) return null;
    return reg.pushManager.getSubscription();
}

export async function subscribe(vapidPublicKey: string): Promise<PushSubscription | null> {
    if (!pushSupported()) return null;
    const reg = await getActiveRegistration();
    if (!reg) return null;

    const permission = await Notification.requestPermission();
    if (permission !== 'granted') return null;

    const sub = await reg.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: urlBase64ToArrayBuffer(vapidPublicKey),
    });

    const csrf = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';
    const json = sub.toJSON();
    await fetch('/api/push/subscribe', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrf,
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({
            endpoint: json.endpoint,
            keys: json.keys,
            content_encoding: 'aesgcm',
        }),
    });

    return sub;
}

export async function unsubscribe(): Promise<void> {
    const sub = await getCurrentSubscription();
    if (!sub) return;
    const csrf = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';
    await fetch('/api/push/subscribe', {
        method: 'DELETE',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrf,
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({ endpoint: sub.endpoint }),
    });
    await sub.unsubscribe();
}
