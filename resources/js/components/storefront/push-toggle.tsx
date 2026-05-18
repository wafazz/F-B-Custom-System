import { Bell, BellOff } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { isIOS } from '@/lib/platform';
import { pushSupported, subscribe, unsubscribe } from '@/lib/push';

/**
 * Minimal push toggle. We don't try to read the existing subscription on mount
 * (async + setState-in-effect is fragile) — clicking Enable is idempotent on
 * PushManager, so toggling reflects intent rather than current device state.
 */
export function PushToggle() {
    const supported = pushSupported();
    const [active, setActive] = useState(false);
    const [busy, setBusy] = useState(false);

    if (!supported) return null;

    async function handleEnable() {
        setBusy(true);
        try {
            const r = await fetch('/api/push/vapid-key', { credentials: 'same-origin' });
            const { public_key } = (await r.json()) as { public_key?: string };
            if (!public_key) {
                window.alert('Push notifications are not configured on this server yet.');
                return;
            }
            const result = await subscribe(public_key);
            if (result.ok) {
                setActive(true);
                return;
            }
            switch (result.reason) {
                case 'permission-denied':
                    window.alert(
                        "Notifications are blocked. Click the 🔒 padlock in the URL bar → Site settings → Notifications → Allow, then try again.",
                    );
                    break;
                case 'permission-default':
                    window.alert(
                        "Notification permission wasn't granted. Look for a small bell icon near the URL bar and click 'Allow', then try again.",
                    );
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
                    window.alert(
                        isIOS()
                            ? 'Push notifications require iOS 16.4 or newer. Please update iOS in Settings → General → Software Update.'
                            : 'This browser does not support web push notifications.',
                    );
                    break;
                case 'no-registration':
                    window.alert(
                        'The app is still starting up — please close and reopen Star Coffee from your Home Screen, then try again.',
                    );
                    break;
            }
        } finally {
            setBusy(false);
        }
    }

    async function handleDisable() {
        setBusy(true);
        try {
            await unsubscribe();
            setActive(false);
        } finally {
            setBusy(false);
        }
    }

    return active ? (
        <Button
            variant="outline"
            size="sm"
            onClick={handleDisable}
            disabled={busy}
            className="flex items-center gap-2"
        >
            <BellOff className="size-4" />
            Notifications on
        </Button>
    ) : (
        <Button
            onClick={handleEnable}
            disabled={busy}
            size="sm"
            className="flex items-center gap-2"
        >
            <Bell className="size-4" />
            Enable notifications
        </Button>
    );
}
