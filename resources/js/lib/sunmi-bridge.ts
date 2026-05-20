// Localhost print bridge running on a SUNMI device's built-in printer.
// The bridge app (sunmi-print-bridge) listens on http://127.0.0.1:8765 and
// forwards JSON receipts to the SUNMI AIDL printer service.
//
// Chrome treats localhost as a secure context even when the page is HTTPS,
// so a fetch from https://starcoffee.my/pos to http://127.0.0.1:8765 is
// allowed (no mixed-content block).

const BRIDGE_URL = 'http://127.0.0.1:8765';

// Cache only the success case. A previous `false` would lock the whole
// session into browser-print fallback even after the cashier finally
// starts the bridge APK — re-probe so the bridge is picked up as soon
// as it comes online. The 800 ms abort keeps the re-probe cheap.
let bridgeUpSince: number | null = null;

export interface BridgePrintPayload {
    order: unknown;
    branch: unknown;
}

export async function isBridgeAvailable(): Promise<boolean> {
    if (bridgeUpSince !== null) return true;
    try {
        const controller = new AbortController();
        const timer = setTimeout(() => controller.abort(), 800);
        const res = await fetch(`${BRIDGE_URL}/ping`, { signal: controller.signal });
        clearTimeout(timer);
        if (res.ok) {
            bridgeUpSince = Date.now();
            return true;
        }
    } catch {
        // bridge unreachable; fall through to false and re-probe next call
    }
    return false;
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
