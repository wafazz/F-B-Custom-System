import { Head, router, usePage } from '@inertiajs/react';
import { Bell, Clock, ChefHat, Check, MapPin, Printer, ShoppingBag } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import PosLayout from '@/layouts/pos-layout';
import { getEcho } from '@/lib/echo';
import { printOrderLabels } from '@/lib/print-labels';
import { printOrderReceipt, type ReceiptOrder } from '@/lib/print-receipt';

interface FlashReceipt extends ReceiptOrder {
    branch: {
        name: string;
        address: string | null;
        receipt_header: string | null;
        receipt_footer: string | null;
        sst_rate: number;
        label_size: '58mm' | '80mm';
    };
}

interface QueueOrder {
    id: number;
    number: string;
    status: 'pending' | 'preparing' | 'ready';
    order_type: 'pickup' | 'dine_in';
    dine_in_table: string | null;
    pickup_at: string | null;
    created_at: string | null;
    total: number;
    notes: string | null;
    items: {
        id: number;
        name: string;
        quantity: number;
        modifiers: { group_name: string; option_name: string }[];
        notes: string | null;
    }[];
}

interface Props {
    branch: {
        id: number;
        code: string;
        name: string;
        auto_print_labels: boolean;
        label_copies: number;
        label_size: '58mm' | '80mm';
    };
    staff: { name: string };
    orders: QueueOrder[];
    reverb: { channel: string; event: string };
}

const COLUMNS: {
    status: QueueOrder['status'];
    label: string;
    icon: React.ReactNode;
    color: string;
}[] = [
    {
        status: 'pending',
        label: 'Pending',
        icon: <Bell className="size-4" />,
        color: 'border-slate-600',
    },
    {
        status: 'preparing',
        label: 'Preparing',
        icon: <ChefHat className="size-4" />,
        color: 'border-amber-500',
    },
    {
        status: 'ready',
        label: 'Ready',
        icon: <Check className="size-4" />,
        color: 'border-emerald-500',
    },
];

export default function PosQueue({ branch, orders, reverb }: Props) {
    const [bumpKey, setBumpKey] = useState(0);
    const audioRef = useRef<HTMLAudioElement | null>(null);
    const flash = usePage<{ flash: { receipt: FlashReceipt | null } }>().props.flash;
    const printedRef = useRef<string | null>(null);

    useEffect(() => {
        const receipt = flash?.receipt;
        if (!receipt) return;
        if (printedRef.current === receipt.number) return;
        printedRef.current = receipt.number;
        printOrderReceipt(receipt, receipt.branch, { size: receipt.branch.label_size });
    }, [flash?.receipt]);

    useEffect(() => {
        const echo = getEcho();
        const channel = echo.channel(reverb.channel);
        const handler = (event: { status: string; previous_status: string }) => {
            // refresh page list
            router.reload({ only: ['orders'] });
            if (event.status === 'pending' || event.previous_status === 'pending') {
                audioRef.current?.play().catch(() => {});
                setBumpKey((k) => k + 1);
            }
        };
        channel.listen(`.${reverb.event}`, handler);
        return () => {
            channel.stopListening(`.${reverb.event}`, handler);
            echo.leaveChannel(reverb.channel);
        };
    }, [reverb.channel, reverb.event]);

    // Polling fallback in case websocket misses an event (network blip,
    // backend deploy, etc.) — partial reload, orders prop only, no scroll jump.
    useEffect(() => {
        const id = window.setInterval(() => {
            if (document.hidden) return;
            router.reload({ only: ['orders'] });
        }, 10_000);
        return () => window.clearInterval(id);
    }, []);

    // Auto-print kitchen labels the moment an order enters "preparing" —
    // i.e., payment is confirmed (mobile / PWA / web) or POS cashier has
    // accepted it. Pending orders are not paid yet, so they're skipped.
    const prevStatusById = useRef<Map<number, QueueOrder['status']>>(new Map());
    const printInitializedRef = useRef(false);
    useEffect(() => {
        if (!printInitializedRef.current) {
            for (const o of orders) prevStatusById.current.set(o.id, o.status);
            printInitializedRef.current = true;
            return;
        }
        for (const o of orders) {
            const prev = prevStatusById.current.get(o.id);
            prevStatusById.current.set(o.id, o.status);
            if (
                branch.auto_print_labels &&
                o.status === 'preparing' &&
                prev !== 'preparing'
            ) {
                printOrderLabels(o, {
                    copies: branch.label_copies,
                    size: branch.label_size,
                    branchName: branch.name,
                });
            }
        }
    }, [orders, branch.auto_print_labels, branch.label_copies, branch.label_size, branch.name]);

    function advance(orderId: number, current: QueueOrder['status']) {
        const next =
            current === 'pending' ? 'preparing' : current === 'preparing' ? 'ready' : 'completed';

        // Printing is handled centrally by the status-transition effect above —
        // it fires whenever any order (POS, mobile, PWA, web) enters preparing.
        router.post(
            `/pos/orders/${orderId}/transition`,
            { status: next },
            { preserveScroll: true },
        );
    }

    function reprintLabels(order: QueueOrder) {
        printOrderLabels(order, {
            copies: branch.label_copies,
            size: branch.label_size,
            branchName: branch.name,
        });
    }

    function cancel(orderId: number) {
        if (!window.confirm('Cancel this order?')) return;
        router.post(
            `/pos/orders/${orderId}/transition`,
            { status: 'cancelled' },
            { preserveScroll: true },
        );
    }

    const grouped = COLUMNS.map((col) => ({
        ...col,
        orders: orders.filter((o) => o.status === col.status),
    }));

    return (
        <PosLayout>
            <Head title={`POS · ${branch.name}`} />
            <audio ref={audioRef} preload="auto" src="/sounds/sc7.mp3" />

            <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                {grouped.map((col) => (
                    <section
                        key={col.status}
                        className={`rounded-lg border-2 ${col.color} bg-slate-900/60 p-3`}
                    >
                        <header className="mb-3 flex items-center justify-between">
                            <h2 className="flex items-center gap-2 text-sm font-bold tracking-wider uppercase">
                                {col.icon} {col.label}
                            </h2>
                            <span className="rounded-full bg-slate-800 px-2 py-0.5 text-xs">
                                {col.orders.length}
                            </span>
                        </header>

                        <div className="space-y-3">
                            {col.orders.length === 0 && (
                                <p className="rounded-md border border-dashed border-slate-700 p-6 text-center text-xs text-slate-500">
                                    No {col.label.toLowerCase()} orders
                                </p>
                            )}
                            {col.orders.map((order) => (
                                <article
                                    key={`${order.id}-${bumpKey}`}
                                    className="rounded-lg border border-slate-700 bg-slate-900 p-3 shadow"
                                >
                                    <header className="mb-2 flex items-start justify-between gap-2">
                                        <div>
                                            <p className="text-base font-bold">{order.number}</p>
                                            <p className="flex items-center gap-1 text-[10px] text-slate-400">
                                                {order.order_type === 'dine_in' ? (
                                                    <>
                                                        <MapPin className="size-3" /> Table{' '}
                                                        {order.dine_in_table}
                                                    </>
                                                ) : (
                                                    <>
                                                        <ShoppingBag className="size-3" /> Pickup
                                                    </>
                                                )}
                                            </p>
                                        </div>
                                        <span className="flex items-center gap-1 text-[10px] text-slate-400">
                                            <Clock className="size-3" />
                                            {order.created_at ? timeAgo(order.created_at) : '—'}
                                        </span>
                                    </header>

                                    <ul className="mb-3 space-y-1 text-xs">
                                        {order.items.map((item) => (
                                            <li key={item.id}>
                                                <span className="font-semibold">
                                                    {item.quantity}× {item.name}
                                                </span>
                                                {item.modifiers.length > 0 && (
                                                    <p className="text-[10px] text-slate-400">
                                                        {item.modifiers
                                                            .map((m) => m.option_name)
                                                            .join(' · ')}
                                                    </p>
                                                )}
                                                {item.notes && (
                                                    <p className="text-[10px] text-amber-400">
                                                        ⚠ {item.notes}
                                                    </p>
                                                )}
                                            </li>
                                        ))}
                                    </ul>

                                    {order.notes && (
                                        <p className="mb-2 rounded bg-amber-900/40 p-2 text-[10px] text-amber-200">
                                            {order.notes}
                                        </p>
                                    )}

                                    <div className="flex gap-2">
                                        <button
                                            type="button"
                                            onClick={() => advance(order.id, order.status)}
                                            className="flex-1 rounded-md bg-amber-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-amber-500"
                                        >
                                            {order.status === 'pending' && 'Accept → Preparing'}
                                            {order.status === 'preparing' && 'Mark Ready'}
                                            {order.status === 'ready' && 'Complete'}
                                        </button>
                                        <button
                                            type="button"
                                            onClick={() => reprintLabels(order)}
                                            title="Reprint labels"
                                            className="rounded-md bg-slate-800 px-2 py-1.5 text-slate-300 hover:bg-slate-700"
                                        >
                                            <Printer className="size-3.5" />
                                        </button>
                                        <button
                                            type="button"
                                            onClick={() => cancel(order.id)}
                                            className="rounded-md bg-slate-800 px-3 py-1.5 text-xs text-slate-300 hover:bg-red-900 hover:text-red-200"
                                        >
                                            Cancel
                                        </button>
                                    </div>
                                </article>
                            ))}
                        </div>
                    </section>
                ))}
            </div>
        </PosLayout>
    );
}

function timeAgo(iso: string): string {
    const seconds = Math.floor((Date.now() - new Date(iso).getTime()) / 1000);
    if (seconds < 60) return `${seconds}s`;
    if (seconds < 3600) return `${Math.floor(seconds / 60)}m`;
    return `${Math.floor(seconds / 3600)}h`;
}
