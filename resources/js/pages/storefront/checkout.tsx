import { Head, Link, router } from '@inertiajs/react';
import { Coffee, CreditCard, Hash, MessageSquare, Package, ShoppingBag, Sparkles, Store, Tag, Wallet as WalletIcon, X } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import StorefrontLayout from '@/layouts/storefront-layout';
import { cartTotals, useCartStore } from '@/stores/cart-store';
import type { BranchContext } from '@/types/menu';
import { cn } from '@/lib/utils';

interface VoucherChip {
    code: string;
    name: string;
    discount_type: 'percentage' | 'fixed';
    discount_value: number;
    min_subtotal: number;
    max_discount: number | null;
}

interface SuggestionProduct {
    id: number;
    name: string;
    image: string | null;
    price: number;
}

interface Props {
    branch: BranchContext;
    wallet_balance: number;
    is_authenticated: boolean;
    vouchers: VoucherChip[];
    suggestions: SuggestionProduct[];
}

type OrderType = 'pickup' | 'dine_in';
type PaymentMethod = 'gateway' | 'wallet';

export default function Checkout({
    branch,
    wallet_balance,
    is_authenticated,
    vouchers,
    suggestions,
}: Props) {
    const lines = useCartStore((s) => s.lines);
    const notes = useCartStore((s) => s.notes);
    const setNotes = useCartStore((s) => s.setNotes);
    const clear = useCartStore((s) => s.clear);
    const cartBranchId = useCartStore((s) => s.branchId);
    const NOTES_LIMIT = 200;

    const [orderType, setOrderType] = useState<OrderType>('pickup');
    const [tableNumber, setTableNumber] = useState('');
    const [paymentMethod, setPaymentMethod] = useState<PaymentMethod>('gateway');
    const [voucherCode, setVoucherCode] = useState<string | null>(null);
    const [packaging, setPackaging] = useState<string[]>([]);
    const [useOwnTumbler, setUseOwnTumbler] = useState(false);
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const { subtotal } = cartTotals(lines);
    const tumblerSaving = useOwnTumbler
        ? lines.reduce((sum, l) => sum + (l.tumbler_discount ?? 0) * l.quantity, 0)
        : 0;
    const activeVoucher = voucherCode ? vouchers.find((v) => v.code === voucherCode) : null;
    const voucherDiscount = activeVoucher
        ? computeVoucherDiscount(activeVoucher, subtotal)
        : 0;
    const discountedSubtotal = Math.max(0, subtotal - voucherDiscount - tumblerSaving);
    const sst = branch.sst_enabled ? discountedSubtotal * (branch.sst_rate / 100) : 0;
    const serviceCharge = branch.service_charge_enabled
        ? discountedSubtotal * (branch.service_charge_rate / 100)
        : 0;
    const total = discountedSubtotal + sst + serviceCharge;
    const hasTumblerEligibleItem = lines.some((l) => (l.tumbler_discount ?? 0) > 0);

    function togglePackaging(key: string) {
        setPackaging((current) =>
            current.includes(key) ? current.filter((k) => k !== key) : [...current, key],
        );
    }

    const walletAffordable = is_authenticated && wallet_balance >= total;

    const canSubmit =
        lines.length > 0 &&
        (cartBranchId === null || cartBranchId === branch.id) &&
        branch.accepts_orders &&
        branch.is_open_now &&
        (orderType === 'pickup' || tableNumber.trim().length > 0) &&
        (paymentMethod !== 'wallet' || walletAffordable);

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
                    payment_method: paymentMethod,
                    voucher_code: voucherCode,
                    packaging,
                    use_own_tumbler: useOwnTumbler,
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

            <section className="border-border bg-card mb-4 rounded-xl border p-4 shadow-sm">
                <h2 className="text-sm font-semibold">Payment</h2>
                <div className="mt-3 grid grid-cols-2 gap-2">
                    <button
                        type="button"
                        onClick={() => walletAffordable && setPaymentMethod('wallet')}
                        disabled={!walletAffordable}
                        className={cn(
                            'flex flex-col items-start gap-1 rounded-lg border p-3 text-left transition-colors',
                            paymentMethod === 'wallet' && walletAffordable
                                ? 'border-primary bg-primary/5 text-primary'
                                : 'border-border hover:bg-secondary/50',
                            !walletAffordable && 'cursor-not-allowed opacity-50',
                        )}
                    >
                        <div className="flex items-center gap-2">
                            <WalletIcon className="size-4" />
                            <span className="text-sm font-medium">Wallet</span>
                        </div>
                        <span className="text-muted-foreground text-[10px]">
                            {is_authenticated
                                ? `Balance RM${wallet_balance.toFixed(2)}`
                                : 'Login required'}
                        </span>
                        {is_authenticated && !walletAffordable && (
                            <span className="text-[10px] text-red-500">Insufficient</span>
                        )}
                    </button>
                    <button
                        type="button"
                        onClick={() => setPaymentMethod('gateway')}
                        className={cn(
                            'flex flex-col items-start gap-1 rounded-lg border p-3 text-left transition-colors',
                            paymentMethod === 'gateway'
                                ? 'border-primary bg-primary/5 text-primary'
                                : 'border-border hover:bg-secondary/50',
                        )}
                    >
                        <div className="flex items-center gap-2">
                            <CreditCard className="size-4" />
                            <span className="text-sm font-medium">Billplz</span>
                        </div>
                        <span className="text-muted-foreground text-[10px]">FPX / e-wallet</span>
                    </button>
                </div>
            </section>

            <section className="border-border bg-card mb-4 rounded-xl border p-4 shadow-sm">
                <h2 className="flex items-center gap-1.5 text-sm font-semibold">
                    <MessageSquare className="size-3.5" /> Special remarks
                </h2>
                <textarea
                    value={notes}
                    onChange={(e) => setNotes(e.target.value.slice(0, NOTES_LIMIT))}
                    rows={2}
                    placeholder="Let us know if you have any special requests. E.g. less ice, extra napkins."
                    className="border-border bg-background mt-2 w-full rounded-md border px-3 py-2 text-sm"
                />
                <p className="text-muted-foreground mt-1 text-right text-[10px]">
                    {notes.length}/{NOTES_LIMIT}
                </p>
            </section>

            {hasTumblerEligibleItem && (
                <section className="border-border bg-card mb-4 rounded-xl border p-4 shadow-sm">
                    <label className="flex cursor-pointer items-start gap-3">
                        <input
                            type="checkbox"
                            checked={useOwnTumbler}
                            onChange={(e) => setUseOwnTumbler(e.target.checked)}
                            className="border-border accent-primary mt-0.5 size-4 rounded"
                        />
                        <span className="flex-1">
                            <span className="flex items-center gap-1.5 text-sm font-semibold">
                                <Coffee className="size-3.5" /> Bring your own tumbler
                            </span>
                            <span className="text-muted-foreground mt-0.5 block text-xs">
                                We'll skip the disposable cup. Discount applies to eligible drinks.
                            </span>
                        </span>
                        {tumblerSaving > 0 && (
                            <span className="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-700">
                                −RM{tumblerSaving.toFixed(2)}
                            </span>
                        )}
                    </label>
                </section>
            )}

            <section className="border-border bg-card mb-4 rounded-xl border p-4 shadow-sm">
                <h2 className="flex items-center gap-1.5 text-sm font-semibold">
                    <Package className="size-3.5" /> Packaging{' '}
                    <span className="text-muted-foreground text-[10px] font-normal">
                        (Only if you really need it)
                    </span>
                </h2>
                <div className="mt-3 space-y-2">
                    {[
                        { key: 'straws', label: 'I need straws' },
                        { key: 'paper_bag', label: 'I need a paper bag for my order' },
                    ].map((opt) => {
                        const active = packaging.includes(opt.key);
                        return (
                            <label
                                key={opt.key}
                                className={cn(
                                    'flex cursor-pointer items-center gap-3 rounded-lg border px-3 py-2 transition-colors',
                                    active
                                        ? 'border-primary bg-primary/5'
                                        : 'border-border hover:bg-secondary/50',
                                )}
                            >
                                <input
                                    type="checkbox"
                                    checked={active}
                                    onChange={() => togglePackaging(opt.key)}
                                    className="border-border accent-primary size-4 rounded"
                                />
                                <span className="text-sm">{opt.label}</span>
                            </label>
                        );
                    })}
                </div>
            </section>

            {vouchers.length > 0 && (
                <section className="border-border bg-card mb-4 rounded-xl border p-4 shadow-sm">
                    <h2 className="text-sm font-semibold">Voucher</h2>
                    <div className="mt-3 flex flex-wrap gap-2">
                        {vouchers.map((v) => {
                            const active = voucherCode === v.code;
                            const eligible = subtotal >= v.min_subtotal;
                            return (
                                <button
                                    key={v.code}
                                    type="button"
                                    onClick={() =>
                                        setVoucherCode(active ? null : eligible ? v.code : null)
                                    }
                                    disabled={!eligible && !active}
                                    className={cn(
                                        'flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-xs font-semibold transition-colors',
                                        active
                                            ? 'border-amber-400 bg-amber-100 text-amber-800'
                                            : 'border-border hover:bg-secondary/50',
                                        !eligible &&
                                            !active &&
                                            'cursor-not-allowed opacity-50',
                                    )}
                                    title={
                                        !eligible
                                            ? `Minimum RM${v.min_subtotal.toFixed(2)}`
                                            : undefined
                                    }
                                >
                                    {active ? (
                                        <X className="size-3" />
                                    ) : (
                                        <Tag className="size-3" />
                                    )}
                                    <span className="font-mono">{v.code}</span>
                                    <span className="text-muted-foreground font-normal">
                                        {v.discount_type === 'percentage'
                                            ? `${v.discount_value.toFixed(0)}%`
                                            : `RM${v.discount_value.toFixed(2)}`}
                                    </span>
                                </button>
                            );
                        })}
                    </div>
                </section>
            )}

            {suggestions.length > 0 && (
                <section className="mb-4">
                    <h2 className="mb-2 flex items-center gap-1.5 text-sm font-semibold">
                        <Sparkles className="size-3.5 text-amber-500" /> You might also want
                    </h2>
                    <div className="-mx-1 flex snap-x snap-mandatory gap-2 overflow-x-auto px-1 pb-2">
                        {suggestions.map((s) => (
                            <Link
                                key={s.id}
                                href={`/branches/${branch.id}/menu?product=${s.id}`}
                                className="border-border bg-card hover:bg-secondary/30 flex w-32 shrink-0 snap-start flex-col gap-1.5 rounded-xl border p-2 shadow-sm transition-colors"
                            >
                                <div className="bg-secondary aspect-square overflow-hidden rounded-lg">
                                    {s.image ? (
                                        <img
                                            src={`/storage/${s.image}`}
                                            alt={s.name}
                                            className="size-full object-cover"
                                        />
                                    ) : (
                                        <div className="flex size-full items-center justify-center">
                                            <Coffee className="text-muted-foreground size-6" />
                                        </div>
                                    )}
                                </div>
                                <p className="line-clamp-2 text-xs font-medium leading-tight">
                                    {s.name}
                                </p>
                                <p className="text-primary text-xs font-bold">
                                    RM{s.price.toFixed(2)}
                                </p>
                            </Link>
                        ))}
                    </div>
                </section>
            )}

            <section className="border-border bg-card mb-4 space-y-2 rounded-xl border p-4 text-sm shadow-sm">
                <div className="text-muted-foreground flex justify-between">
                    <span>Subtotal</span>
                    <span>RM{subtotal.toFixed(2)}</span>
                </div>
                {voucherDiscount > 0 && activeVoucher && (
                    <div className="flex justify-between text-emerald-600">
                        <span>Voucher ({activeVoucher.code})</span>
                        <span>−RM{voucherDiscount.toFixed(2)}</span>
                    </div>
                )}
                {tumblerSaving > 0 && (
                    <div className="flex justify-between text-emerald-600">
                        <span>BYO tumbler</span>
                        <span>−RM{tumblerSaving.toFixed(2)}</span>
                    </div>
                )}
                {branch.service_charge_enabled && (
                    <div className="text-muted-foreground flex justify-between">
                        <span>Service charge ({branch.service_charge_rate.toFixed(0)}%)</span>
                        <span>RM{serviceCharge.toFixed(2)}</span>
                    </div>
                )}
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

            {!branch.is_open_now && (
                <div className="mb-3 rounded-lg border border-red-200 bg-red-50 p-3 text-xs text-red-800">
                    ⏰ <strong>This branch is currently closed.</strong> Online ordering will resume
                    during operating hours.
                </div>
            )}
            {error && <p className="mb-3 rounded-md bg-red-50 p-3 text-xs text-red-700">{error}</p>}

            <Button onClick={handlePlace} disabled={!canSubmit || submitting} className="w-full">
                {submitting
                    ? 'Placing order…'
                    : branch.is_open_now
                      ? `Place order — RM${total.toFixed(2)}`
                      : 'Branch closed'}
            </Button>
        </StorefrontLayout>
    );
}

function computeVoucherDiscount(voucher: VoucherChip, subtotal: number): number {
    if (subtotal < voucher.min_subtotal) return 0;
    const raw =
        voucher.discount_type === 'percentage'
            ? subtotal * (voucher.discount_value / 100)
            : voucher.discount_value;
    const capped = voucher.max_discount !== null ? Math.min(raw, voucher.max_discount) : raw;
    return Math.min(Math.round(capped * 100) / 100, subtotal);
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
