function csrfToken(): string {
    const v = document.cookie
        .split('; ')
        .find((c) => c.startsWith('XSRF-TOKEN='))
        ?.substring('XSRF-TOKEN='.length);
    return v ? decodeURIComponent(v) : '';
}

/**
 * Report the customer's position so the server can fire any in-radius
 * proximity campaign. Web is foreground-only and we NEVER prompt — we report
 * only when location permission is already granted. Best-effort; never throws.
 * (The native app reports the same endpoint from background geofence events.)
 */
export async function pingLocation(): Promise<void> {
    if (typeof navigator === 'undefined' || !navigator.geolocation) return;

    try {
        if ('permissions' in navigator) {
            const status = await navigator.permissions.query({
                name: 'geolocation' as PermissionName,
            });
            if (status.state !== 'granted') return; // don't prompt
        }
    } catch {
        return;
    }

    navigator.geolocation.getCurrentPosition(
        (pos) => {
            void fetch('/api/location/ping', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-XSRF-TOKEN': csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ lat: pos.coords.latitude, lng: pos.coords.longitude }),
            }).catch(() => {});
        },
        () => {},
        { maximumAge: 60000, timeout: 10000 },
    );
}
