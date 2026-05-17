import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, ChevronRight, CreditCard, Package } from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import StorefrontLayout from '@/layouts/storefront-layout';

interface OrderRow {
    id: number;
    number: string;
    status: 'pending' | 'preparing' | 'ready' | 'completed' | 'cancelled' | 'refunded';
    status_label: string;
    total: number;
    payment_status: 'unpaid' | 'paid' | 'failed' | 'refunded';
    can_pay_again: boolean;
    created_at: string | null;
}

interface Props {
    orders: OrderRow[];
}

export default function OrdersHistory({ orders }: Props) {
    const [payingId, setPayingId] = useState<number | null>(null);

    function payAgain(id: number) {
        setPayingId(id);
        router.post(
            `/orders/${id}/pay`,
            {},
            {
                preserveScroll: true,
                onFinish: () => setPayingId(null),
            },
        );
    }

    return (
        <StorefrontLayout>
            <Head title="Your Orders" />

            <div className="mb-4 flex items-center gap-3">
                <Link
                    href="/profile"
                    className="bg-card text-card-foreground hover:bg-amber-50 flex size-9 shrink-0 items-center justify-center rounded-full border border-amber-100 shadow-sm transition-colors"
                    aria-label="Back to profile"
                >
                    <ArrowLeft className="size-4" />
                </Link>
                <h1 className="text-xl font-bold">Your Orders</h1>
            </div>

            {orders.length === 0 ? (
                <div className="border-border bg-card text-muted-foreground flex flex-col items-center gap-2 rounded-xl border border-dashed p-8 text-sm">
                    <Package className="size-10 opacity-40" />
                    <p>No orders yet.</p>
                </div>
            ) : (
                <ul className="space-y-2">
                    {orders.map((order) => (
                        <li
                            key={order.id}
                            className="border-border bg-card overflow-hidden rounded-xl border shadow-sm transition-all hover:-translate-y-0.5 hover:shadow-md"
                        >
                            <Link
                                href={`/orders/${order.id}`}
                                className="flex items-center justify-between gap-3 p-4"
                            >
                                <div className="flex-1">
                                    <p className="text-sm font-semibold">{order.number}</p>
                                    <p className="text-muted-foreground text-[11px]">
                                        {order.created_at
                                            ? new Date(order.created_at).toLocaleString()
                                            : '—'}
                                    </p>
                                </div>
                                <Badge variant={variantFor(order.status)}>
                                    {order.status_label}
                                </Badge>
                                <span className="text-primary text-sm font-semibold">
                                    RM{order.total.toFixed(2)}
                                </span>
                                <ChevronRight className="text-muted-foreground size-4" />
                            </Link>
                            {order.can_pay_again && (
                                <button
                                    type="button"
                                    onClick={() => payAgain(order.id)}
                                    disabled={payingId === order.id}
                                    className="bg-primary text-primary-foreground hover:bg-primary/90 flex w-full items-center justify-center gap-2 px-4 py-2.5 text-xs font-bold uppercase tracking-wider transition-colors disabled:opacity-60"
                                >
                                    <CreditCard className="size-4" />
                                    {payingId === order.id
                                        ? 'Redirecting…'
                                        : `Pay now — RM${order.total.toFixed(2)}`}
                                </button>
                            )}
                        </li>
                    ))}
                </ul>
            )}
        </StorefrontLayout>
    );
}

function variantFor(status: OrderRow['status']): 'default' | 'success' | 'danger' | 'warning' {
    if (status === 'completed' || status === 'ready') return 'success';
    if (status === 'cancelled' || status === 'refunded') return 'danger';
    if (status === 'preparing') return 'warning';
    return 'default';
}
