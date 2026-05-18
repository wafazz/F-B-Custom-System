import { Bell, X } from 'lucide-react';
import { useEffect, useState } from 'react';
import { pushSupported, subscribe } from '@/lib/push';

const DISMISS_KEY = 'sc.notif-prompt.dismissed-at';
const DISMISS_TTL_MS = 7 * 24 * 60 * 60 * 1000;

function recentlyDismissed(): boolean {
    if (typeof window === 'undefined') return false;
    const v = window.localStorage.getItem(DISMISS_KEY);
    if (!v) return false;
    const ts = Number(v);
    if (!Number.isFinite(ts)) return false;
    return Date.now() - ts < DISMISS_TTL_MS;
}

interface Props {
    /** Logged-in user gate — backend /api/push/subscribe needs auth */
    isAuthenticated: boolean;
}

export function NotificationPrompt({ isAuthenticated }: Props) {
    const [show, setShow] = useState(false);
    const [busy, setBusy] = useState(false);

    useEffect(() => {
        if (!isAuthenticated) return;
        if (!pushSupported()) return;
        if (recentlyDismissed()) return;
        if (typeof Notification === 'undefined') return;
        // Only prompt when the user has never decided. If they've denied we
        // can't re-prompt from JS; if they've already granted we don't need to.
        if (Notification.permission !== 'default') return;
        setShow(true);
    }, [isAuthenticated]);

    if (!show) return null;

    function dismiss() {
        try {
            window.localStorage.setItem(DISMISS_KEY, String(Date.now()));
        } catch {
            // private mode / quota — ignore
        }
        setShow(false);
    }

    async function handleEnable() {
        setBusy(true);
        try {
            const r = await fetch('/api/push/vapid-key', { credentials: 'same-origin' });
            const { public_key } = (await r.json()) as { public_key?: string };
            if (!public_key) {
                window.alert('Push notifications are not configured yet.');
                dismiss();
                return;
            }
            const result = await subscribe(public_key);
            if (result.ok) {
                dismiss();
                return;
            }
            switch (result.reason) {
                case 'permission-denied':
                    window.alert(
                        "Notifications are blocked. Open Site settings → Notifications → Allow, then try again.",
                    );
                    dismiss();
                    break;
                case 'permission-default':
                    // User dismissed the OS prompt without choosing — leave banner so they can try again.
                    break;
                case 'api-error':
                    if (result.status === 401) {
                        window.alert('Please log in first, then enable notifications.');
                    } else {
                        window.alert(
                            `Couldn't save your subscription (HTTP ${result.status ?? '?'}). ${result.message ?? ''}`.trim(),
                        );
                    }
                    break;
                case 'browser-subscribe-failed':
                    window.alert(`Browser refused the subscription: ${result.message ?? 'unknown error'}`);
                    break;
                case 'unsupported':
                case 'no-registration':
                    window.alert(
                        'This browser/device does not support web push. On iPhone, install the app to your Home Screen first (Share → Add to Home Screen).',
                    );
                    dismiss();
                    break;
            }
        } finally {
            setBusy(false);
        }
    }

    return (
        <div className="mb-4 flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-3.5 shadow-sm">
            <div className="mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-800">
                <Bell className="size-4" />
            </div>
            <div className="flex-1">
                <p className="text-card-foreground text-sm font-bold leading-tight">
                    Get notified when your order is ready
                </p>
                <p className="text-muted-foreground mt-0.5 text-xs leading-snug">
                    We'll ping your phone the moment it's ready for pickup.
                </p>
                <div className="mt-2.5 flex items-center gap-2">
                    <button
                        type="button"
                        onClick={handleEnable}
                        disabled={busy}
                        className="rounded-full bg-amber-800 px-3.5 py-1.5 text-[11px] font-bold uppercase tracking-wider text-amber-50 transition-colors hover:bg-amber-700 disabled:opacity-60"
                    >
                        {busy ? 'Enabling…' : 'Enable'}
                    </button>
                    <button
                        type="button"
                        onClick={dismiss}
                        className="text-muted-foreground rounded-full px-2 py-1 text-[11px] font-semibold hover:text-amber-900"
                    >
                        Not now
                    </button>
                </div>
            </div>
            <button
                type="button"
                onClick={dismiss}
                aria-label="Dismiss"
                className="text-muted-foreground -m-1 rounded-full p-1 hover:text-amber-900"
            >
                <X className="size-4" />
            </button>
        </div>
    );
}
