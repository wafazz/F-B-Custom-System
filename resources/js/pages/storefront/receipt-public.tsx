import { Head } from '@inertiajs/react';
import { Check, Clock, MapPin, Package, Receipt as ReceiptIcon, Star, Tag } from 'lucide-react';

interface OrderItem {
    name: string;
    quantity: number;
    unit_price: number;
    line_total: number;
    voucher_code: string | null;
    voucher_role: 'paid' | 'free' | null;
    modifiers: { option_name: string }[];
}

interface Props {
    order: {
        number: string;
        order_type: 'pickup' | 'dine_in';
        dine_in_table: string | null;
        created_at: string | null;
        paid_at: string | null;
        payment_status: 'unpaid' | 'paid' | 'failed' | 'refunded';
        payment_method: string | null;
        payment_reference: string | null;
        subtotal: number;
        sst_amount: number;
        service_charge_amount: number;
        discount_amount: number;
        tumbler_discount_amount: number;
        total: number;
        customer_name: string | null;
        points_earned: number;
        vouchers: { code: string | null; name: string | null; discount_amount: number }[];
        items: OrderItem[];
    };
    branch: {
        name: string;
        code: string | null;
        address: string | null;
        receipt_header: string | null;
        receipt_footer: string | null;
        sst_rate: number;
        service_charge_rate: number;
    };
}

export default function ReceiptPublic({ order, branch }: Props) {
    const when = order.paid_at ?? order.created_at;
    const isPaid = order.payment_status === 'paid';

    return (
        <div className="min-h-screen bg-gradient-to-br from-amber-50 via-orange-50 to-amber-100 px-4 py-6 sm:py-12">
            <Head title={`Receipt ${order.number}`} />

            <div className="mx-auto w-full max-w-md">
                <div className="mb-4 flex items-center justify-center gap-2 text-amber-800">
                    <ReceiptIcon className="size-5" />
                    <span className="text-xs font-semibold tracking-widest uppercase">
                        Digital Receipt
                    </span>
                </div>

                <article className="overflow-hidden rounded-2xl bg-white shadow-xl ring-1 ring-amber-200/60">
                    <header className="relative bg-gradient-to-b from-amber-100 to-white px-5 pt-6 pb-4 text-center">
                        <h1 className="text-lg font-black tracking-wider text-amber-900">
                            {branch.name}
                        </h1>
                        {branch.address && (
                            <p className="mt-1 flex items-center justify-center gap-1 text-[11px] text-amber-800/70">
                                <MapPin className="size-3" /> {branch.address}
                            </p>
                        )}
                        {branch.receipt_header && (
                            <p className="mt-2 text-[10px] whitespace-pre-line text-amber-900/60">
                                {branch.receipt_header}
                            </p>
                        )}

                        <div className="my-4 border-t border-dashed border-amber-300" />

                        <p className="text-2xl font-black tracking-widest text-slate-900">
                            {order.number}
                        </p>
                        <div className="mt-1 flex items-center justify-center gap-3 text-[11px] text-slate-600">
                            <span className="flex items-center gap-1">
                                <Package className="size-3" />
                                {order.order_type === 'dine_in'
                                    ? `Dine-in · Table ${order.dine_in_table ?? '—'}`
                                    : 'Pickup'}
                            </span>
                            {when && (
                                <span className="flex items-center gap-1">
                                    <Clock className="size-3" />
                                    {new Date(when).toLocaleString('en-MY', {
                                        day: '2-digit',
                                        month: 'short',
                                        year: 'numeric',
                                        hour: '2-digit',
                                        minute: '2-digit',
                                    })}
                                </span>
                            )}
                        </div>
                        {order.customer_name && (
                            <p className="mt-2 text-[11px] text-slate-500">
                                Member: <strong>{order.customer_name}</strong>
                            </p>
                        )}
                    </header>

                    <section className="px-5 py-4">
                        <h2 className="mb-3 text-[10px] font-bold tracking-widest text-slate-400 uppercase">
                            Items
                        </h2>
                        <ul className="space-y-3">
                            {order.items.map((item, idx) => {
                                const mods = groupCounts(
                                    item.modifiers.map((m) => m.option_name),
                                );
                                return (
                                    <li key={idx} className="flex items-start gap-3 text-sm">
                                        <span className="w-6 shrink-0 font-bold text-amber-700 tabular-nums">
                                            {item.quantity}×
                                        </span>
                                        <div className="min-w-0 flex-1">
                                            <p className="font-semibold text-slate-900">
                                                {item.name}
                                                {item.voucher_role === 'free' && (
                                                    <span className="ml-1.5 inline-block rounded-full bg-emerald-100 px-1.5 py-0.5 text-[9px] font-bold tracking-wider text-emerald-700 uppercase align-middle">
                                                        Free
                                                    </span>
                                                )}
                                            </p>
                                            {mods.length > 0 && (
                                                <p className="text-[11px] text-slate-500">
                                                    {mods.join(' · ')}
                                                </p>
                                            )}
                                            {item.voucher_code && (
                                                <p className="mt-0.5 flex items-center gap-1 font-mono text-[10px] text-amber-600">
                                                    <Tag className="size-2.5" /> {item.voucher_code}
                                                </p>
                                            )}
                                        </div>
                                        <span className="shrink-0 font-semibold tabular-nums text-slate-900">
                                            RM{item.line_total.toFixed(2)}
                                        </span>
                                    </li>
                                );
                            })}
                        </ul>

                        <div className="my-4 border-t border-dashed border-slate-200" />

                        <dl className="space-y-1.5 text-sm">
                            <Row label="Subtotal" value={`RM${order.subtotal.toFixed(2)}`} />
                            {order.vouchers.length > 0
                                ? order.vouchers.map((v, i) => (
                                      <Row
                                          key={i}
                                          label={`Voucher${v.code ? ` (${v.code})` : ''}`}
                                          value={`−RM${v.discount_amount.toFixed(2)}`}
                                          valueClass="text-emerald-600"
                                      />
                                  ))
                                : order.discount_amount > 0 && (
                                      <Row
                                          label="Discount"
                                          value={`−RM${order.discount_amount.toFixed(2)}`}
                                          valueClass="text-emerald-600"
                                      />
                                  )}
                            {order.tumbler_discount_amount > 0 && (
                                <Row
                                    label="BYO tumbler"
                                    value={`−RM${order.tumbler_discount_amount.toFixed(2)}`}
                                    valueClass="text-emerald-600"
                                />
                            )}
                            {order.service_charge_amount > 0 && (
                                <Row
                                    label={`Service charge${branch.service_charge_rate ? ` ${branch.service_charge_rate.toFixed(0)}%` : ''}`}
                                    value={`RM${order.service_charge_amount.toFixed(2)}`}
                                />
                            )}
                            {order.sst_amount > 0 && (
                                <Row
                                    label={`SST${branch.sst_rate ? ` ${branch.sst_rate.toFixed(0)}%` : ''}`}
                                    value={`RM${order.sst_amount.toFixed(2)}`}
                                />
                            )}
                        </dl>

                        <div className="my-4 border-t-2 border-double border-slate-300" />

                        <div className="flex items-center justify-between">
                            <span className="text-xs font-bold tracking-widest text-slate-500 uppercase">
                                Total
                            </span>
                            <span className="text-2xl font-black text-amber-700">
                                RM{order.total.toFixed(2)}
                            </span>
                        </div>

                        <div className="my-4 border-t border-dashed border-slate-200" />

                        <div className="rounded-lg bg-slate-50 p-3 text-[11px] text-slate-600">
                            <div className="flex items-center justify-between">
                                <span>Payment</span>
                                <span className="font-bold uppercase">
                                    {order.payment_method ?? '—'}
                                </span>
                            </div>
                            <div className="mt-1 flex items-center justify-between">
                                <span>Status</span>
                                <span
                                    className={`flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold ${
                                        isPaid
                                            ? 'bg-emerald-100 text-emerald-700'
                                            : 'bg-amber-100 text-amber-700'
                                    }`}
                                >
                                    {isPaid && <Check className="size-3" />}
                                    {order.payment_status.toUpperCase()}
                                </span>
                            </div>
                            {order.payment_reference && (
                                <div className="mt-1 flex items-center justify-between">
                                    <span>Ref</span>
                                    <span className="font-mono text-[10px]">
                                        {order.payment_reference}
                                    </span>
                                </div>
                            )}
                        </div>

                        {order.points_earned > 0 && (
                            <div className="mt-3 flex items-center justify-center gap-2 rounded-lg border border-dashed border-amber-300 bg-amber-50 p-3 text-amber-900">
                                <Star className="size-4 fill-amber-400 text-amber-500" />
                                <span className="text-xs">
                                    Earned <strong>{order.points_earned} pts</strong>
                                </span>
                            </div>
                        )}
                    </section>

                    <footer className="bg-slate-50 px-5 py-4 text-center">
                        <p className="text-xs font-semibold text-slate-700">
                            Thank you, see you again!
                        </p>
                        {branch.receipt_footer && (
                            <p className="mt-1 text-[10px] whitespace-pre-line text-slate-500">
                                {branch.receipt_footer}
                            </p>
                        )}
                    </footer>
                </article>

                <p className="mt-4 text-center text-[10px] text-slate-500">
                    Digital receipt · Keep this link to view anytime
                </p>
            </div>
        </div>
    );
}

function Row({
    label,
    value,
    valueClass,
}: {
    label: string;
    value: string;
    valueClass?: string;
}) {
    return (
        <div className="flex items-center justify-between">
            <span className="text-slate-500">{label}</span>
            <span className={`tabular-nums ${valueClass ?? 'text-slate-700'}`}>{value}</span>
        </div>
    );
}

function groupCounts(labels: string[]): string[] {
    const counts = new Map<string, number>();
    for (const l of labels) counts.set(l, (counts.get(l) ?? 0) + 1);
    return Array.from(counts.entries()).map(([name, n]) => (n > 1 ? `${name} × ${n}` : name));
}
