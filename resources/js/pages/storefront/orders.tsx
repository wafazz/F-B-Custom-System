import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, ChevronRight, Package } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import StorefrontLayout from '@/layouts/storefront-layout';

interface OrderRow {
    id: number;
    number: string;
    status: 'pending' | 'preparing' | 'ready' | 'completed' | 'cancelled' | 'refunded';
    status_label: string;
    total: number;
    created_at: string | null;
}

interface Props {
    orders: OrderRow[];
}

export default function OrdersHistory({ orders }: Props) {
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
                        <li key={order.id}>
                            <Link
                                href={`/orders/${order.id}`}
                                className="border-border bg-card flex items-center justify-between gap-3 rounded-xl border p-4 shadow-sm transition-all hover:-translate-y-0.5 hover:shadow-md"
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
