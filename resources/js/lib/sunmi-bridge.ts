// Localhost print bridge running on a SUNMI device's built-in printer.
// The bridge app (sunmi-print-bridge) listens on http://127.0.0.1:8765 and
// forwards JSON receipts to the SUNMI AIDL printer service.
//
// Chrome treats localhost as a secure context even when the page is HTTPS,
// so a fetch from https://starcoffee.my/pos to http://127.0.0.1:8765 is
// allowed (no mixed-content block).

const BRIDGE_URL = 'http://127.0.0.1:8765';

let cachedAvailable: boolean | null = null;

export interface BridgePrintPayload {
    order: unknown;
    branch: unknown;
}

/** Probe the bridge once per session. Cached after the first call. */
export async function isBridgeAvailable(): Promise<boolean> {
    if (cachedAvailable !== null) return cachedAvailable;
    try {
        const controller = new AbortController();
        const timer = setTimeout(() => controller.abort(), 800);
        const res = await fetch(`${BRIDGE_URL}/ping`, { signal: controller.signal });
        clearTimeout(timer);
        cachedAvailable = res.ok;
    } catch {
        cachedAvailable = false;
    }
    return cachedAvailable;
}

/** Send a receipt to the SUNMI bridge. Throws on network/printer failure. */
export async function printViaBridge(payload: BridgePrintPayload): Promise<void> {
    const res = await fetch(`${BRIDGE_URL}/print`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
    });
    if (!res.ok) {
        const text = await res.text().catch(() => '');
        throw new Error(`Bridge HTTP ${res.status}: ${text}`);
    }
}
