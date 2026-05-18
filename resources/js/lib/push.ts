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
    // Race navigator.serviceWorker.ready (waits for activation) against a 5s
    // timeout so the UI doesn't hang if the SW never installs.
    return Promise.race([
        navigator.serviceWorker.ready,
        new Promise<null>((resolve) => setTimeout(() => resolve(null), 5000)),
    ]);
}

export async function getCurrentSubscription(): Promise<PushSubscription | null> {
    const reg = await getActiveRegistration();
    if (!reg) return null;
    return reg.pushManager.getSubscription();
}

export type SubscribeResult =
    | { ok: true; subscription: PushSubscription }
    | {
          ok: false;
          reason:
              | 'unsupported'
              | 'no-registration'
              | 'permission-denied'
              | 'permission-default'
              | 'browser-subscribe-failed'
              | 'api-error';
          status?: number;
          message?: string;
      };

export async function subscribe(vapidPublicKey: string): Promise<SubscribeResult> {
    if (!pushSupported()) {
        return { ok: false, reason: 'unsupported' };
    }
    const reg = await getActiveRegistration();
    if (!reg) {
        return { ok: false, reason: 'no-registration' };
    }

    const permission = await Notification.requestPermission();
    if (permission === 'denied') return { ok: false, reason: 'permission-denied' };
    if (permission !== 'granted') return { ok: false, reason: 'permission-default' };

    let sub: PushSubscription;
    try {
        sub = await reg.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToArrayBuffer(vapidPublicKey),
        });
    } catch (err) {
        return {
            ok: false,
            reason: 'browser-subscribe-failed',
            message: err instanceof Error ? err.message : String(err),
        };
    }

    const json = sub.toJSON();
    // Refresh XSRF-TOKEN cookie. In a freshly installed PWA the meta-tag
    // CSRF can be stale and the cookie may never have been issued.
    try {
        await fetch('/sanctum/csrf-cookie', { credentials: 'same-origin' });
    } catch {
        /* non-fatal — we'll fall through and surface any 419 below */
    }

    let res: Response;
    try {
        res = await fetch('/api/push/subscribe', {
            method: 'POST',
            credentials: 'same-origin',
            redirect: 'error',
            headers: csrfHeaders(),
            body: JSON.stringify({
                endpoint: json.endpoint,
                keys: json.keys,
                content_encoding: 'aesgcm',
            }),
        });
    } catch (err) {
        // redirect:'error' throws TypeError on unexpected 3xx (the pre-fix
        // 419→back() path used to land here). Treat as api-error.
        try {
            await sub.unsubscribe();
        } catch {
            /* ignore */
        }
        return {
            ok: false,
            reason: 'api-error',
            message: err instanceof Error ? err.message : String(err),
        };
    }

    if (!res.ok) {
        try {
            await sub.unsubscribe();
        } catch {
            /* ignore */
        }
        const message = await res.text().catch(() => '');
        return { ok: false, reason: 'api-error', status: res.status, message };
    }

    return { ok: true, subscription: sub };
}

export async function unsubscribe(): Promise<void> {
    const sub = await getCurrentSubscription();
    if (!sub) return;
    await fetch('/api/push/subscribe', {
        method: 'DELETE',
        credentials: 'same-origin',
        redirect: 'error',
        headers: csrfHeaders(),
        body: JSON.stringify({ endpoint: sub.endpoint }),
    });
    await sub.unsubscribe();
}

function csrfHeaders(): Record<string, string> {
    const headers: Record<string, string> = {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    };
    // Laravel rotates XSRF-TOKEN on every response, so reading the cookie
    // gives us the live token. Fall back to the meta tag which can be
    // stale in a long-running PWA.
    const cookieToken = readCookie('XSRF-TOKEN');
    if (cookieToken) {
        headers['X-XSRF-TOKEN'] = decodeURIComponent(cookieToken);
    }
    const metaToken = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content;
    if (metaToken) {
        headers['X-CSRF-TOKEN'] = metaToken;
    }
    return headers;
}

function readCookie(name: string): string | null {
    if (typeof document === 'undefined') return null;
    const target = `${name}=`;
    for (const part of document.cookie.split('; ')) {
        if (part.startsWith(target)) return part.substring(target.length);
    }
    return null;
}
