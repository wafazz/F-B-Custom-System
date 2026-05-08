import { Head, router } from '@inertiajs/react';
import { Hash, ShoppingBag, Store } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import StorefrontLayout from '@/layouts/storefront-layout';
import { cartTotals, useCartStore } from '@/stores/cart-store';
import type { BranchContext } from '@/types/menu';
import { cn } from '@/lib/utils';

interface Props {
    branch: BranchContext;
}

type OrderType = 'pickup' | 'dine_in';

export default function Checkout({ branch }: Props) {
    const lines = useCartStore((s) => s.lines);
    const notes = useCartStore((s) => s.notes);
    const clear = useCartStore((s) => s.clear);
    const cartBranchId = useCartStore((s) => s.branchId);

    const [orderType, setOrderType] = useState<OrderType>('pickup');
    const [tableNumber, setTableNumber] = useState('');
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const { subtotal } = cartTotals(lines);
    const sst = branch.sst_enabled ? subtotal * (branch.sst_rate / 100) : 0;
    const total = subtotal + sst;

    const canSubmit =
        lines.length > 0 &&
        (cartBranchId === null || cartBranchId === branch.id) &&
        branch.accepts_orders &&
        (orderType === 'pickup' || tableNumber.trim().length > 0);

    async function handlePlace() {
        if (!canSubmit) return;
        setSubmitting(true);
        setError(null);
        try {
            const csrf =
                document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';
            const response = await fetch('/api/orders', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify({
                    branch_id: branch.id,
                    order_type: orderType,
                    dine_in_table: orderType === 'dine_in' ? tableNumber : null,
                    notes,
                    lines: lines.map((line) => ({
                        product_id: line.product_id,
                        quantity: line.quantity,
                        modifier_option_ids: line.modifiers.map((m) => m.option_id),
                        notes: line.notes,
                    })),
                }),
            });

            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                setError(data?.message ?? 'Failed to place order. Please try again.');
                return;
            }

            clear();
            const paymentUrl = data?.payment?.url as string | undefined;
            if (paymentUrl) {
                window.location.href = paymentUrl;
                return;
            }
            router.visit(`/orders/${data.order.id}`);
        } catch {
            setError('Network error. Please try again.');
        } finally {
            setSubmitting(false);
        }
    }

    return (
        <StorefrontLayout>
            <Head title="Checkout" />

            <h1 className="mb-4 text-xl font-bold">Checkout</h1>

            <section className="border-border bg-card mb-4 rounded-xl border p-4 shadow-sm">
                <h2 className="text-sm font-semibold">Order Type</h2>
                <div className="mt-3 grid grid-cols-2 gap-2">
                    <TypeCard
                        label="Pickup"
                        icon={<ShoppingBag className="size-5" />}
                        active={orderType === 'pickup'}
                        onClick={() => setOrderType('pickup')}
                    />
                    <TypeCard
                        label="Dine-in"
                        icon={<Store className="size-5" />}
                        active={orderType === 'dine_in'}
                        onClick={() => setOrderType('dine_in')}
                    />
                </div>
                {orderType === 'dine_in' && (
                    <div className="mt-3">
                        <label className="text-muted-foreground text-xs">Table number</label>
                        <div className="border-border bg-background mt-1 flex items-center gap-2 rounded-md border px-3 py-2">
                            <Hash className="text-muted-foreground size-4" />
                            <input
                                value={tableNumber}
                                onChange={(e) => setTableNumber(e.target.value)}
                                placeholder="e.g. 12"
                                className="w-full bg-transparent text-sm outline-none"
                            />
                        </div>
                    </div>
                )}
            </section>

            <section className="border-border bg-card mb-4 rounded-xl border p-4 shadow-sm">
                <h2 className="text-sm font-semibold">Items ({lines.length})</h2>
                <ul className="mt-2 space-y-1.5 text-xs">
                    {lines.map((line) => (
                        <li key={line.id} className="flex justify-between gap-2">
                            <span className="text-foreground">
                                {line.quantity}× {line.name}
                                {line.modifiers.length > 0 && (
                                    <span className="text-muted-foreground">
                                        {' '}
                                        ({line.modifiers.map((m) => m.option_name).join(', ')})
                                    </span>
                                )}
                            </span>
                            <span className="font-medium">
                                RM{(line.unit_price * line.quantity).toFixed(2)}
                            </span>
                        </li>
                    ))}
                </ul>
            </section>

            <section className="border-border bg-card mb-4 space-y-2 rounded-xl border p-4 text-sm shadow-sm">
                <div className="text-muted-foreground flex justify-between">
                    <span>Subtotal</span>
                    <span>RM{subtotal.toFixed(2)}</span>
                </div>
                {branch.sst_enabled && (
                    <div className="text-muted-foreground flex justify-between">
                        <span>SST ({branch.sst_rate.toFixed(0)}%)</span>
                        <span>RM{sst.toFixed(2)}</span>
                    </div>
                )}
                <div className="border-border flex justify-between border-t pt-2 font-semibold">
                    <span>Total</span>
                    <span className="text-primary">RM{total.toFixed(2)}</span>
                </div>
            </section>

            {error && <p className="mb-3 rounded-md bg-red-50 p-3 text-xs text-red-700">{error}</p>}

            <Button onClick={handlePlace} disabled={!canSubmit || submitting} className="w-full">
                {submitting ? 'Placing order…' : `Place order — RM${total.toFixed(2)}`}
            </Button>
        </StorefrontLayout>
    );
}

function TypeCard({
    label,
    icon,
    active,
    onClick,
}: {
    label: string;
    icon: React.ReactNode;
    active: boolean;
    onClick: () => void;
}) {
    return (
        <button
            type="button"
            onClick={onClick}
            className={cn(
                'flex flex-col items-center gap-2 rounded-lg border p-4 transition-colors',
                active
                    ? 'border-primary bg-primary/5 text-primary'
                    : 'border-border hover:bg-secondary/50',
            )}
        >
            {icon}
            <span className="text-sm font-medium">{label}</span>
        </button>
    );
}
