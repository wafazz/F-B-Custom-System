const FINGERPRINT_KEY = 'pwa-device-fingerprint';
const INSTALL_REPORTED_KEY = 'pwa-install-reported';
const HEARTBEAT_INTERVAL_MS = 5 * 60 * 1000; // 5 min

function getFingerprint(): string {
    let fp = localStorage.getItem(FINGERPRINT_KEY);
    if (!fp) {
        // crypto.randomUUID is available in all browsers we target (PWA-capable
        // = modern); fall back to Math.random for ancient ones.
        fp =
            typeof crypto !== 'undefined' && 'randomUUID' in crypto
                ? crypto.randomUUID()
                : `${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 11)}`;
        localStorage.setItem(FINGERPRINT_KEY, fp);
    }
    return fp;
}

function csrfToken(): string {
    const v = document.cookie
        .split('; ')
        .find((c) => c.startsWith('XSRF-TOKEN='))
        ?.substring('XSRF-TOKEN='.length);
    return v ? decodeURIComponent(v) : '';
}

function detectPlatform(): string {
    const ua = navigator.userAgent;
    if (/Android/i.test(ua)) return 'android';
    if (/iPhone|iPad|iPod/i.test(ua)) return 'ios';
    if (/Windows/i.test(ua)) return 'windows';
    if (/Mac/i.test(ua)) return 'mac';
    return 'web';
}

function isStandalone(): boolean {
    if (typeof window === 'undefined') return false;
    if (window.matchMedia('(display-mode: standalone)').matches) return true;
    // iOS Safari
    return Boolean((navigator as unknown as { standalone?: boolean }).standalone);
}

async function postJson(url: string, body: Record<string, unknown>): Promise<void> {
    try {
        await fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-XSRF-TOKEN': csrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(body),
        });
    } catch {
        // swallow — analytics must never throw
    }
}

let started = false;

export function initPwaTracking(): void {
    if (started || typeof window === 'undefined') return;
    started = true;

    const fp = getFingerprint();
    const platform = detectPlatform();

    const reportInstall = () => {
        if (localStorage.getItem(INSTALL_REPORTED_KEY) === fp) return;
        postJson('/api/pwa/installed', {
            device_fingerprint: fp,
            platform,
        }).then(() => localStorage.setItem(INSTALL_REPORTED_KEY, fp));
    };

    // Already installed (returning PWA visit)
    if (isStandalone()) {
        reportInstall();
    }

    // Fired the moment the browser installs the PWA
    window.addEventListener('appinstalled', () => {
        // Force re-report even if previously marked, since this is a real install event.
        localStorage.removeItem(INSTALL_REPORTED_KEY);
        reportInstall();
    });

    // Heartbeat — only for installed PWA, keeps last_active_at fresh
    if (isStandalone()) {
        const beat = () => postJson('/api/pwa/heartbeat', { device_fingerprint: fp });
        beat();
        window.setInterval(beat, HEARTBEAT_INTERVAL_MS);
    }
}
