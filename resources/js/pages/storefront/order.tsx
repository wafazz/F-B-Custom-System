import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Check, Clock, CreditCard, Package, X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { Badge } from '@/components/ui/badge';
import StorefrontLayout from '@/layouts/storefront-layout';
import { getEcho } from '@/lib/echo';

interface OrderItem {
    id: number;
    product_name: string;
    unit_price: number;
    quantity: number;
    line_total: number;
    modifiers: { group_name: string; option_name: string }[];
}

interface OrderProp {
    id: number;
    number: string;
    status: 'pending' | 'preparing' | 'ready' | 'completed' | 'cancelled' | 'refunded';
    status_label: string;
    order_type: 'pickup' | 'dine_in';
    dine_in_table: string | null;
    pickup_at: string | null;
    branch: { id: number; code: string | null; name: string | null };
    subtotal: number;
    sst_amount: number;
    total: number;
    payment_status: 'unpaid' | 'paid' | 'failed' | 'refunded';
    payment_method: string | null;
    notes: string | null;
    created_at: string | null;
    can_pay_again: boolean;
    items: OrderItem[];
}

interface Props {
    order: OrderProp;
    reverb: { channel: string; event: string };
}

const STAGES: OrderProp['status'][] = ['pending', 'preparing', 'ready', 'completed'];

export default function Order({ order, reverb }: Props) {
    const [status, setStatus] = useState<OrderProp['status']>(order.status);
    const [statusLabel, setStatusLabel] = useState(order.status_label);
    const [paying, setPaying] = useState(false);
    const audioRef = useRef<HTMLAudioElement | null>(null);

    function payAgain() {
        setPaying(true);
        router.post(
            `/orders/${order.id}/pay`,
            {},
            {
                preserveScroll: true,
                onFinish: () => setPaying(false),
            },
        );
    }

    useEffect(() => {
        const echo = getEcho();
        const channel = echo.channel(reverb.channel);
        const handler = (event: { status: OrderProp['status'] }) => {
            setStatus((prev) => {
                if (event.status === 'ready' && prev !== 'ready') {
                    audioRef.current?.play().catch(() => {});
                }
                return event.status;
            });
            setStatusLabel(prettify(event.status));
        };
        channel.listen(`.${reverb.event}`, handler);
        return () => {
            channel.stopListening(`.${reverb.event}`, handler);
            echo.leaveChannel(reverb.channel);
        };
    }, [reverb.channel, reverb.event]);

    const progressIndex = STAGES.indexOf(status);
    const isCancelled = status === 'cancelled' || status === 'refunded';

    return (
        <StorefrontLayout hideStats>
            <Head title={`Order ${order.number}`} />
            <audio ref={audioRef} preload="auto" src="/sounds/sc7.mp3" />

            <div className="mb-2 flex items-center gap-3">
                <Link
                    href="/orders"
                    className="bg-card text-card-foreground hover:bg-amber-50 inline-flex items-center gap-1.5 rounded-full border border-amber-100 px-3 py-1.5 text-xs font-medium shadow-sm transition-colors"
                >
                    <ArrowLeft className="size-4" />
                    <span>Back to My Orders</span>
                </Link>
            </div>

            <div className="mb-4">
                <h1 className="text-2xl font-bold">{order.number}</h1>
                <p className="text-muted-foreground text-xs">
                    {order.branch.name} ·{' '}
                    {order.order_type === 'dine_in'
                        ? `Dine-in (Table ${order.dine_in_table})`
                        : 'Pickup'}
                </p>
            </div>

            <div className="border-border bg-card mb-4 rounded-xl border p-4 shadow-sm">
                <div className="flex items-center justify-between gap-2">
                    <span className="text-sm font-semibold">{statusLabel}</span>
                    <Badge variant={statusVariant(status)}>{order.payment_status}</Badge>
                </div>

                {!isCancelled ? (
                    <ol className="mt-4 grid grid-cols-4 gap-1 text-[10px]">
                        {STAGES.map((stage, idx) => {
                            const reached = idx <= progressIndex;
                            return (
                                <li key={stage} className="flex flex-col items-center gap-1">
                                    <span
                                        className={`flex size-7 items-center justify-center rounded-full text-[10px] ${
                                            reached
                                                ? 'bg-primary text-primary-foreground'
                                                : 'bg-secondary text-muted-foreground'
                                        }`}
                                    >
                                        {reached ? <Check className="size-3.5" /> : idx + 1}
                                    </span>
                                    <span
                                        className={
                                            reached ? 'text-card-foreground' : 'text-muted-foreground'
                                        }
                                    >
                                        {prettify(stage)}
                                    </span>
                                </li>
                            );
                        })}
                    </ol>
                ) : (
                    <p className="mt-3 flex items-center gap-2 text-sm text-red-600">
                        <X className="size-4" /> {prettify(status)}
                    </p>
                )}
            </div>

            {order.can_pay_again && (
                <section className="border-amber-200 bg-amber-50 mb-4 rounded-xl border p-4 shadow-sm">
                    <div className="mb-3">
                        <p className="text-amber-900 text-sm font-bold">
                            Payment pending — finish it now
                        </p>
                        <p className="text-amber-800/80 mt-0.5 text-[11px]">
                            This order is still waiting for payment. If it isn't paid by end of
                            today it will be automatically cancelled.
                        </p>
                    </div>
                    <button
                        type="button"
                        onClick={payAgain}
                        disabled={paying}
                        className="bg-primary text-primary-foreground hover:bg-primary/90 flex w-full items-center justify-center gap-2 rounded-lg px-4 py-3 text-sm font-bold uppercase tracking-wider shadow-sm transition-colors disabled:opacity-60"
                    >
                        <CreditCard className="size-4" />
                        {paying ? 'Redirecting…' : `Pay now — RM${order.total.toFixed(2)}`}
                    </button>
                </section>
            )}

            <section className="border-border bg-card mb-4 rounded-xl border p-4 shadow-sm">
                <h2 className="mb-2 flex items-center gap-2 text-sm font-semibold">
                    <Package className="size-4" /> Items
                </h2>
                <ul className="space-y-2 text-xs">
                    {order.items.map((item) => (
                        <li key={item.id} className="flex justify-between gap-2">
                            <div>
                                <p className="font-medium">
                                    {item.quantity}× {item.product_name}
                                </p>
                                {item.modifiers.length > 0 && (
                                    <p className="text-muted-foreground">
                                        {item.modifiers.map((m) => m.option_name).join(' · ')}
                                    </p>
                                )}
                            </div>
                            <span className="font-medium">RM{item.line_total.toFixed(2)}</span>
                        </li>
                    ))}
                </ul>
            </section>

            <section className="border-border bg-card mb-4 space-y-1 rounded-xl border p-4 text-sm shadow-sm">
                <div className="text-muted-foreground flex justify-between">
                    <span>Subtotal</span>
                    <span>RM{order.subtotal.toFixed(2)}</span>
                </div>
                {order.sst_amount > 0 && (
                    <div className="text-muted-foreground flex justify-between">
                        <span>SST</span>
                        <span>RM{order.sst_amount.toFixed(2)}</span>
                    </div>
                )}
                <div className="border-border flex justify-between border-t pt-2 font-semibold">
                    <span>Total</span>
                    <span className="text-primary">RM{order.total.toFixed(2)}</span>
                </div>
            </section>

            {order.notes && (
                <section className="border-border bg-card rounded-xl border p-4 text-xs shadow-sm">
                    <p className="font-semibold">Notes</p>
                    <p className="text-muted-foreground mt-1">{order.notes}</p>
                </section>
            )}

            <p className="text-muted-foreground mt-4 flex items-center gap-1 text-[10px]">
                <Clock className="size-3" /> Placed{' '}
                {order.created_at ? new Date(order.created_at).toLocaleString() : '—'}
            </p>
        </StorefrontLayout>
    );
}

function prettify(status: string): string {
    return status
        .split('_')
        .map((s) => s.charAt(0).toUpperCase() + s.slice(1))
        .join(' ');
}

function statusVariant(status: OrderProp['status']): 'default' | 'success' | 'danger' | 'warning' {
    if (status === 'completed' || status === 'ready') return 'success';
    if (status === 'cancelled' || status === 'refunded') return 'danger';
    if (status === 'preparing') return 'warning';
    return 'default';
}
