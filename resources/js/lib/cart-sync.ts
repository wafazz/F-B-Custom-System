import { cartTotals } from '@/stores/cart-store';
import type { CartLine } from '@/types/menu';

function csrfToken(): string {
    const v = document.cookie
        .split('; ')
        .find((c) => c.startsWith('XSRF-TOKEN='))
        ?.substring('XSRF-TOKEN='.length);
    return v ? decodeURIComponent(v) : '';
}

/**
 * Mirror the browser cart to the server so an abandoned cart can be detected.
 * Best-effort — only meaningful for logged-in customers; never throws.
 */
export async function syncCart(lines: CartLine[], branchId: number | null): Promise<void> {
    const { itemCount, subtotal } = cartTotals(lines);
    try {
        await fetch('/api/cart/sync', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-XSRF-TOKEN': csrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                branch_id: branchId,
                item_count: itemCount,
                subtotal,
                items: lines.slice(0, 100).map((l) => ({ name: l.name, quantity: l.quantity })),
            }),
        });
    } catch {
        // best-effort — abandoned-cart tracking must never break the UI
    }
}
