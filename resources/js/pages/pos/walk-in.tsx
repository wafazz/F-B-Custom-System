import { Head, router, usePage } from '@inertiajs/react';
import {
    Banknote,
    CreditCard,
    Eye,
    EyeOff,
    Hash,
    Monitor,
    Plus,
    Search,
    ShoppingBag,
    Sparkles,
    Store,
    Trash2,
    UserRound,
    X,
} from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Sheet, SheetContent, SheetTitle } from '@/components/ui/sheet';
import PosLayout from '@/layouts/pos-layout';
import { cn } from '@/lib/utils';

interface ModOption {
    id: number;
    name: string;
    price_delta: number;
    is_default: boolean;
}

interface ModGroup {
    id: number;
    name: string;
    selection_type: 'single' | 'multiple';
    is_required: boolean;
    min_select: number;
    max_select: number;
    allow_quantity: boolean;
    options: ModOption[];
}

interface PosProduct {
    id: number;
    name: string;
    sku: string;
    price: number;
    modifier_groups: ModGroup[];
}

interface Category {
    name: string;
    products: PosProduct[];
}

interface ParentCategory {
    name: string;
    children: Category[];
}

interface Line {
    key: string;
    product_id: number;
    name: string;
    unit_price: number;
    quantity: number;
    modifier_option_ids: number[];
    modifier_labels: string[];
}

interface Props {
    branch: {
        id: number;
        code: string;
        name: string;
        sst_rate: number;
        sst_enabled: boolean;
        service_charge_rate: number;
        service_charge_enabled: boolean;
    };
    staff: { name: string };
    parents: ParentCategory[];
}

interface CustomerHit {
    id: number;
    name: string;
    phone: string | null;
    email: string | null;
    referral_code: string | null;
    points: number;
    tier: string | null;
}

export default function PosWalkIn({ branch, parents }: Props) {
    const [lines, setLines] = useState<Line[]>([]);
    const [orderType, setOrderType] = useState<'pickup' | 'dine_in'>('pickup');
    const [tableNumber, setTableNumber] = useState('');
    const [paymentMethod, setPaymentMethod] = useState<'cash' | 'card' | 'duitnow'>('cash');
    const [activeParent, setActiveParent] = useState<string | null>(parents[0]?.name ?? null);
    const [activeChild, setActiveChild] = useState<string | null>(parents[0]?.children[0]?.name ?? null);
    const [picker, setPicker] = useState<PosProduct | null>(null);
    const [customer, setCustomer] = useState<CustomerHit | null>(null);
    const [search, setSearch] = useState('');
    const [hits, setHits] = useState<CustomerHit[]>([]);
    const [searching, setSearching] = useState(false);
    const [pointsRevealed, setPointsRevealed] = useState(false);
    const [cashOpen, setCashOpen] = useState(false);

    const pageErrors = usePage<{ errors: Record<string, string> }>().props.errors;
    const submitError = pageErrors?.order ?? null;

    useEffect(() => {
        if (customer || search.trim().length < 2) return;
        const ctrl = new AbortController();
        const timer = window.setTimeout(() => {
            setSearching(true);
            fetch(`/pos/customers/search?q=${encodeURIComponent(search.trim())}`, {
                signal: ctrl.signal,
                headers: { Accept: 'application/json' },
            })
                .then((r) => r.json())
                .then((data: { results: CustomerHit[] }) => setHits(data.results ?? []))
                .catch(() => {})
                .finally(() => setSearching(false));
        }, 250);
        return () => {
            window.clearTimeout(timer);
            ctrl.abort();
        };
    }, [search, customer]);

    const visibleHits = customer || search.trim().length < 2 ? [] : hits;

    function tap(product: PosProduct) {
        if (product.modifier_groups.length === 0) {
            commitLine(product, [], []);
            return;
        }
        setPicker(product);
    }

    function commitLine(product: PosProduct, optionIds: number[], labels: string[]) {
        const extra = product.modifier_groups
            .flatMap((g) => g.options)
            .filter((o) => optionIds.includes(o.id))
            .reduce((sum, o) => sum + Number(o.price_delta), 0);
        const key = `${product.id}#${[...optionIds].sort((a, b) => a - b).join('|')}`;
        setLines((prev) => {
            const existing = prev.find((l) => l.key === key);
            if (existing) {
                return prev.map((l) => (l.key === key ? { ...l, quantity: l.quantity + 1 } : l));
            }
            return [
                ...prev,
                {
                    key,
                    product_id: product.id,
                    name: product.name,
                    unit_price: Number(product.price) + extra,
                    quantity: 1,
                    modifier_option_ids: optionIds,
                    modifier_labels: labels,
                },
            ];
        });
    }

    function adjust(key: string, delta: number) {
        setLines((prev) =>
            prev
                .map((l) => (l.key === key ? { ...l, quantity: l.quantity + delta } : l))
                .filter((l) => l.quantity > 0),
        );
    }

    function remove(key: string) {
        setLines((prev) => prev.filter((l) => l.key !== key));
    }

    const subtotal = lines.reduce((sum, l) => sum + l.unit_price * l.quantity, 0);
    const sst = branch.sst_enabled ? subtotal * (branch.sst_rate / 100) : 0;
    const serviceCharge = branch.service_charge_enabled
        ? subtotal * (branch.service_charge_rate / 100)
        : 0;
    const total = subtotal + sst + serviceCharge;
    const activeParentObj = parents.find((p) => p.name === activeParent) ?? parents[0] ?? null;
    const childCats = activeParentObj?.children ?? [];
    const showChildBar = childCats.length > 1 || childCats[0]?.name !== activeParentObj?.name;
    const visibleProducts =
        childCats.find((c) => c.name === activeChild)?.products ??
        childCats[0]?.products ??
        [];

    function selectParent(name: string) {
        const found = parents.find((p) => p.name === name);
        setActiveParent(name);
        setActiveChild(found?.children[0]?.name ?? null);
    }

    const canSubmit = lines.length > 0 && (orderType === 'pickup' || tableNumber.trim().length > 0);

    const channelRef = useRef<BroadcastChannel | null>(null);
    const effectivePointsRevealed = !!customer && pointsRevealed;
    const cartSnapshot = useMemo(
        () => ({
            order_type: orderType,
            table_number: orderType === 'dine_in' ? tableNumber : '',
            customer: customer
                ? {
                      name: customer.name,
                      points: customer.points,
                      tier: customer.tier,
                  }
                : null,
            points_revealed: effectivePointsRevealed,
            lines: lines.map((l) => ({
                key: l.key,
                name: l.name,
                quantity: l.quantity,
                unit_price: l.unit_price,
                modifier_labels: l.modifier_labels,
            })),
        }),
        [lines, orderType, tableNumber, customer, effectivePointsRevealed],
    );

    useEffect(() => {
        if (typeof BroadcastChannel === 'undefined') return;
        const channel = new BroadcastChannel(`pos-cart-${branch.id}`);
        channelRef.current = channel;
        channel.onmessage = (e: MessageEvent<{ type: string }>) => {
            if (e.data?.type === 'cart:request') {
                channel.postMessage({ type: 'cart:update', cart: cartSnapshot });
            }
        };
        return () => {
            channel.close();
            channelRef.current = null;
        };
    }, [branch.id, cartSnapshot]);

    useEffect(() => {
        channelRef.current?.postMessage({ type: 'cart:update', cart: cartSnapshot });
    }, [cartSnapshot]);

    function openCustomerDisplay() {
        window.open('/pos/customer-display', 'star-coffee-customer-display', 'noopener,noreferrer');
    }

    function submit() {
        if (!canSubmit) return;
        router.post(
            '/pos/walk-in',
            {
                order_type: orderType,
                dine_in_table: orderType === 'dine_in' ? tableNumber : null,
                payment_method: paymentMethod,
                customer_user_id: customer?.id ?? null,
                lines: lines.map((l) => ({
                    product_id: l.product_id,
                    quantity: l.quantity,
                    modifier_option_ids: l.modifier_option_ids,
                })),
            },
            {
                onSuccess: () => {
                    channelRef.current?.postMessage({ type: 'cart:clear' });
                    setCustomer(null);
                    setSearch('');
                    setPointsRevealed(false);
                },
            },
        );
    }

    return (
        <PosLayout>
            <Head title="POS · Walk-in" />

            <div className="grid h-full grid-cols-1 gap-4 lg:grid-cols-[1fr_360px]">
                <section className="flex min-h-0 flex-col">
                    <div className="-mx-1 mb-2 flex gap-2 overflow-x-auto px-1 pb-1">
                        {parents.map((p) => (
                            <button
                                key={p.name}
                                type="button"
                                onClick={() => selectParent(p.name)}
                                className={cn(
                                    'flex-shrink-0 rounded-full px-4 py-1.5 text-sm font-semibold transition-colors',
                                    activeParent === p.name
                                        ? 'bg-amber-600 text-white shadow'
                                        : 'bg-slate-800 text-slate-200 hover:bg-slate-700',
                                )}
                            >
                                {p.name}
                            </button>
                        ))}
                    </div>
                    {showChildBar && (
                        <div className="-mx-1 mb-3 flex gap-1.5 overflow-x-auto px-1 pb-1">
                            {childCats.map((c) => (
                                <button
                                    key={c.name}
                                    type="button"
                                    onClick={() => setActiveChild(c.name)}
                                    className={cn(
                                        'flex-shrink-0 rounded-full border px-3 py-1 text-[11px] font-medium transition-colors',
                                        activeChild === c.name
                                            ? 'border-amber-500 bg-amber-900/30 text-amber-200'
                                            : 'border-slate-700 text-slate-400 hover:border-slate-600 hover:text-slate-200',
                                    )}
                                >
                                    {c.name}
                                </button>
                            ))}
                        </div>
                    )}

                    <div className="-mr-1 grid grid-cols-2 gap-2 overflow-y-auto pr-1 md:grid-cols-3">
                        {visibleProducts.map((p) => (
                            <button
                                key={p.id}
                                type="button"
                                onClick={() => tap(p)}
                                className="flex flex-col gap-1 rounded-lg border border-slate-700 bg-slate-900 p-3 text-left hover:border-amber-500"
                            >
                                <span className="text-sm font-semibold">{p.name}</span>
                                <span className="text-xs text-slate-400">{p.sku}</span>
                                <span className="mt-auto flex items-center justify-between pt-2 text-xs">
                                    <span className="font-semibold text-amber-400">
                                        RM{p.price.toFixed(2)}
                                    </span>
                                    <Plus className="size-3 text-slate-500" />
                                </span>
                            </button>
                        ))}
                    </div>
                </section>

                <aside className="flex min-h-0 flex-col overflow-y-auto rounded-xl border border-slate-700 bg-slate-900 p-4">
                    <div className="mb-3 flex items-center justify-between">
                        <h2 className="text-sm font-bold">Cart</h2>
                        <button
                            type="button"
                            onClick={openCustomerDisplay}
                            title="Open customer display in new window"
                            className="flex items-center gap-1 rounded-md border border-slate-700 px-2 py-1 text-[10px] text-slate-300 hover:border-amber-500 hover:text-amber-300"
                        >
                            <Monitor className="size-3" /> Customer view
                        </button>
                    </div>

                    <div className="mb-3">
                        {customer ? (
                            <div className="flex items-center justify-between rounded-md border border-amber-700/60 bg-amber-900/30 px-2.5 py-2 text-xs">
                                <div className="flex min-w-0 items-center gap-2">
                                    <UserRound className="size-3.5 flex-shrink-0 text-amber-400" />
                                    <div className="min-w-0">
                                        <div className="flex items-center gap-1.5">
                                            <p className="truncate font-semibold text-amber-100">
                                                {customer.name}
                                            </p>
                                            {customer.tier && (
                                                <span
                                                    className={cn(
                                                        'rounded-sm border px-1 py-px text-[9px] font-bold tracking-wide uppercase',
                                                        tierClasses(customer.tier),
                                                    )}
                                                >
                                                    {customer.tier}
                                                </span>
                                            )}
                                        </div>
                                        <p className="flex items-center gap-1.5 text-[10px] text-amber-300/80">
                                            <Sparkles className="size-2.5" />
                                            <span className="tabular-nums">
                                                {pointsRevealed ? customer.points : '••••'}
                                            </span>
                                            pts
                                        </p>
                                    </div>
                                </div>
                                <div className="ml-2 flex items-center gap-1">
                                    <button
                                        type="button"
                                        onClick={() => setPointsRevealed((v) => !v)}
                                        title={
                                            pointsRevealed
                                                ? 'Hide points on customer view'
                                                : 'Reveal points on customer view'
                                        }
                                        aria-label={
                                            pointsRevealed ? 'Hide points' : 'Reveal points'
                                        }
                                        className={cn(
                                            'rounded-md p-1 transition-colors',
                                            pointsRevealed
                                                ? 'bg-amber-700/40 text-amber-100'
                                                : 'text-amber-300 hover:bg-amber-800/40 hover:text-amber-100',
                                        )}
                                    >
                                        {pointsRevealed ? (
                                            <EyeOff className="size-3.5" />
                                        ) : (
                                            <Eye className="size-3.5" />
                                        )}
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => {
                                            setCustomer(null);
                                            setSearch('');
                                            setPointsRevealed(false);
                                        }}
                                        className="rounded-md p-1 text-amber-300 hover:bg-amber-800/40 hover:text-amber-100"
                                        aria-label="Detach"
                                    >
                                        <X className="size-3.5" />
                                    </button>
                                </div>
                            </div>
                        ) : (
                            <div className="relative">
                                <Search className="absolute top-1/2 left-2 size-3 -translate-y-1/2 text-slate-500" />
                                <input
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    placeholder="Attach member (phone / email / code)"
                                    className="w-full rounded-md border border-slate-700 bg-slate-950 py-2 pr-2 pl-7 text-xs text-slate-100 placeholder:text-slate-500 outline-none focus:border-amber-500"
                                />
                                {visibleHits.length > 0 && (
                                    <ul className="absolute z-10 mt-1 max-h-56 w-full overflow-y-auto rounded-md border border-slate-700 bg-slate-900 shadow-xl">
                                        {visibleHits.map((h) => (
                                            <li key={h.id}>
                                                <button
                                                    type="button"
                                                    onClick={() => {
                                                        setCustomer(h);
                                                        setHits([]);
                                                        setSearch('');
                                                    }}
                                                    className="block w-full px-2.5 py-1.5 text-left text-xs hover:bg-slate-800"
                                                >
                                                    <p className="font-semibold text-slate-100">
                                                        {h.name}
                                                    </p>
                                                    <p className="text-[10px] text-slate-400">
                                                        {h.phone ?? h.email ?? h.referral_code}
                                                        {' · '}
                                                        {h.points} pts
                                                        {h.tier && ` · ${h.tier}`}
                                                    </p>
                                                </button>
                                            </li>
                                        ))}
                                    </ul>
                                )}
                                {search.trim().length >= 2 && !searching && visibleHits.length === 0 && (
                                    <p className="mt-1 text-[10px] text-slate-500">No match</p>
                                )}
                            </div>
                        )}
                    </div>

                    <div className="mb-3 grid grid-cols-2 gap-1">
                        <button
                            type="button"
                            onClick={() => setOrderType('pickup')}
                            className={cn(
                                'flex flex-col items-center gap-1 rounded-md border p-2 text-xs',
                                orderType === 'pickup'
                                    ? 'border-amber-500 bg-amber-900/30'
                                    : 'border-slate-700',
                            )}
                        >
                            <ShoppingBag className="size-4" /> Pickup
                        </button>
                        <button
                            type="button"
                            onClick={() => setOrderType('dine_in')}
                            className={cn(
                                'flex flex-col items-center gap-1 rounded-md border p-2 text-xs',
                                orderType === 'dine_in'
                                    ? 'border-amber-500 bg-amber-900/30'
                                    : 'border-slate-700',
                            )}
                        >
                            <Store className="size-4" /> Dine-in
                        </button>
                    </div>

                    {orderType === 'dine_in' && (
                        <div className="mb-3 flex items-center gap-2 rounded-md border border-slate-700 bg-slate-950 px-3 py-2">
                            <Hash className="size-3 text-slate-500" />
                            <input
                                value={tableNumber}
                                onChange={(e) => setTableNumber(e.target.value)}
                                placeholder="Table"
                                className="w-full bg-transparent text-sm outline-none"
                            />
                        </div>
                    )}

                    <ul className="mb-3 max-h-64 space-y-2 overflow-y-auto">
                        {lines.length === 0 && (
                            <li className="rounded-md border border-dashed border-slate-700 p-4 text-center text-xs text-slate-500">
                                Tap items to add
                            </li>
                        )}
                        {lines.map((l) => (
                            <li
                                key={l.key}
                                className="rounded-md border border-slate-800 bg-slate-950 p-2 text-xs"
                            >
                                <div className="flex items-start justify-between gap-2">
                                    <span className="flex-1 font-semibold">{l.name}</span>
                                    <button
                                        type="button"
                                        onClick={() => remove(l.key)}
                                        className="text-slate-500 hover:text-red-400"
                                    >
                                        <Trash2 className="size-3" />
                                    </button>
                                </div>
                                {l.modifier_labels.length > 0 && (
                                    <p className="text-[10px] text-slate-500">
                                        {groupLabels(l.modifier_labels).join(' · ')}
                                    </p>
                                )}
                                <div className="mt-1 flex items-center justify-between">
                                    <span className="font-semibold text-amber-400">
                                        RM{(l.unit_price * l.quantity).toFixed(2)}
                                    </span>
                                    <div className="flex items-center gap-1">
                                        <button
                                            type="button"
                                            onClick={() => adjust(l.key, -1)}
                                            className="size-6 rounded bg-slate-800 hover:bg-slate-700"
                                        >
                                            −
                                        </button>
                                        <span className="w-5 text-center">{l.quantity}</span>
                                        <button
                                            type="button"
                                            onClick={() => adjust(l.key, 1)}
                                            className="size-6 rounded bg-slate-800 hover:bg-slate-700"
                                        >
                                            +
                                        </button>
                                    </div>
                                </div>
                            </li>
                        ))}
                    </ul>

                    <div className="mb-3 grid grid-cols-3 gap-1">
                        <PayBtn
                            label="Cash"
                            icon={<Banknote className="size-3" />}
                            active={paymentMethod === 'cash'}
                            onClick={() => setPaymentMethod('cash')}
                        />
                        <PayBtn
                            label="Card"
                            icon={<CreditCard className="size-3" />}
                            active={paymentMethod === 'card'}
                            onClick={() => setPaymentMethod('card')}
                        />
                        <PayBtn
                            label="DuitNow"
                            icon={<Hash className="size-3" />}
                            active={paymentMethod === 'duitnow'}
                            onClick={() => setPaymentMethod('duitnow')}
                        />
                    </div>

                    <div className="space-y-1 border-t border-slate-700 pt-3 text-xs">
                        <div className="flex justify-between text-slate-400">
                            <span>Subtotal</span>
                            <span>RM{subtotal.toFixed(2)}</span>
                        </div>
                        {branch.service_charge_enabled && (
                            <div className="flex justify-between text-slate-400">
                                <span>Service charge {branch.service_charge_rate.toFixed(0)}%</span>
                                <span>RM{serviceCharge.toFixed(2)}</span>
                            </div>
                        )}
                        {branch.sst_enabled && (
                            <div className="flex justify-between text-slate-400">
                                <span>SST {branch.sst_rate.toFixed(0)}%</span>
                                <span>RM{sst.toFixed(2)}</span>
                            </div>
                        )}
                        <div className="flex justify-between text-base font-bold">
                            <span>Total</span>
                            <span className="text-amber-400">RM{total.toFixed(2)}</span>
                        </div>
                    </div>

                    {submitError && (
                        <div className="mt-3 rounded-md border border-red-500/60 bg-red-900/30 px-3 py-2 text-xs text-red-200">
                            {submitError}
                            {submitError.toLowerCase().includes('shift') && (
                                <a
                                    href="/pos/shift"
                                    className="ml-1 font-semibold underline hover:text-red-100"
                                >
                                    Open one now →
                                </a>
                            )}
                        </div>
                    )}

                    <Button
                        onClick={() => (paymentMethod === 'cash' ? setCashOpen(true) : submit())}
                        disabled={!canSubmit}
                        className="mt-3 w-full bg-amber-600 hover:bg-amber-500"
                    >
                        {paymentMethod === 'cash' ? 'Tender cash' : 'Charge & place order'}
                    </Button>
                </aside>
            </div>

            <Sheet open={cashOpen} onOpenChange={setCashOpen}>
                <SheetContent
                    side="bottom"
                    className="border-slate-700 bg-slate-900 text-slate-100 sm:mx-auto sm:max-w-md sm:rounded-xl"
                >
                    {cashOpen && (
                        <CashTender
                            total={total}
                            onCancel={() => setCashOpen(false)}
                            onConfirm={() => {
                                setCashOpen(false);
                                submit();
                            }}
                        />
                    )}
                </SheetContent>
            </Sheet>

            <Sheet open={picker !== null} onOpenChange={(o) => !o && setPicker(null)}>
                <SheetContent
                    side="bottom"
                    className="border-slate-700 bg-slate-900 text-slate-100 sm:mx-auto sm:max-w-2xl sm:rounded-xl"
                >
                    {picker && (
                        <ModifierPicker
                            key={picker.id}
                            product={picker}
                            onAdd={(ids, labels) => {
                                commitLine(picker, ids, labels);
                                setPicker(null);
                            }}
                            onClose={() => setPicker(null)}
                        />
                    )}
                </SheetContent>
            </Sheet>
        </PosLayout>
    );
}

function ModifierPicker({
    product,
    onAdd,
    onClose,
}: {
    product: PosProduct;
    onAdd: (optionIds: number[], labels: string[]) => void;
    onClose: () => void;
}) {
    // selection: groupId → optionId → count
    const [selection, setSelection] = useState<Record<number, Record<number, number>>>(() => {
        const init: Record<number, Record<number, number>> = {};
        for (const group of product.modifier_groups) {
            const defaults = group.options.filter((o) => o.is_default);
            if (defaults.length > 0 || group.is_required) {
                init[group.id] = {};
                for (const d of defaults) {
                    init[group.id][d.id] = 1;
                }
            }
        }
        return init;
    });

    const countOf = (groupId: number, optionId: number) =>
        selection[groupId]?.[optionId] ?? 0;
    const totalInGroup = (groupId: number) =>
        Object.values(selection[groupId] ?? {}).reduce((a, b) => a + b, 0);

    let validationMessage = '';
    let valid = true;
    for (const group of product.modifier_groups) {
        if (group.is_required && totalInGroup(group.id) < group.min_select) {
            validationMessage = `${group.name}: pick at least ${group.min_select}`;
            valid = false;
            break;
        }
    }

    const extra = product.modifier_groups.reduce((sum, group) => {
        return (
            sum +
            group.options.reduce(
                (acc, o) => acc + Number(o.price_delta) * countOf(group.id, o.id),
                0,
            )
        );
    }, 0);
    const lineTotal = Number(product.price) + extra;

    function bump(group: ModGroup, optionId: number, delta: number) {
        setSelection((prev) => {
            const groupSel = { ...(prev[group.id] ?? {}) };
            const next = Math.max(0, (groupSel[optionId] ?? 0) + delta);
            if (next === 0) {
                delete groupSel[optionId];
            } else {
                groupSel[optionId] = next;
            }
            return { ...prev, [group.id]: groupSel };
        });
    }

    function submit() {
        if (!valid) return;
        const ids: number[] = [];
        const labels: string[] = [];
        for (const group of product.modifier_groups) {
            for (const opt of group.options) {
                const count = countOf(group.id, opt.id);
                for (let i = 0; i < count; i++) {
                    ids.push(opt.id);
                    labels.push(opt.name);
                }
            }
        }
        onAdd(ids, labels);
    }

    return (
        <>
            <SheetTitle className="text-slate-100">{product.name}</SheetTitle>
            <p className="text-xs text-slate-400">RM{Number(product.price).toFixed(2)}</p>

            <div className="mt-3 max-h-[60vh] space-y-4 overflow-y-auto pr-1">
                {product.modifier_groups.map((group) => (
                    <div key={group.id}>
                        <div className="mb-1.5 flex items-center justify-between">
                            <h4 className="text-sm font-semibold">
                                {group.name}
                                {group.is_required && <span className="ml-1 text-red-400">*</span>}
                            </h4>
                            <span className="text-[10px] text-slate-500">
                                {group.is_required
                                    ? `Pick ${group.min_select}+`
                                    : 'Optional'}
                            </span>
                        </div>
                        {group.allow_quantity ? (
                            <div className="grid grid-cols-1 gap-1.5 sm:grid-cols-2">
                                {group.options.map((option) => {
                                    const count = countOf(group.id, option.id);
                                    return (
                                        <div
                                            key={option.id}
                                            className={cn(
                                                'flex items-center justify-between gap-2 rounded-lg border px-2.5 py-2 text-sm transition-colors',
                                                count > 0
                                                    ? 'border-amber-500 bg-amber-900/30 text-amber-200'
                                                    : 'border-slate-700',
                                            )}
                                        >
                                            <div className="min-w-0 flex-1">
                                                <p className="truncate text-sm">{option.name}</p>
                                                <p className="text-[10px] text-slate-400">
                                                    {Number(option.price_delta) > 0
                                                        ? `+RM${Number(option.price_delta).toFixed(2)} each`
                                                        : 'No extra charge'}
                                                </p>
                                            </div>
                                            <div className="flex flex-shrink-0 items-center gap-1.5">
                                                <button
                                                    type="button"
                                                    onClick={() => bump(group, option.id, -1)}
                                                    disabled={count === 0}
                                                    className="flex size-7 items-center justify-center rounded-md bg-slate-800 text-slate-200 hover:bg-slate-700 disabled:opacity-30"
                                                    aria-label={`Decrease ${option.name}`}
                                                >
                                                    −
                                                </button>
                                                <span className="w-6 text-center text-sm font-bold tabular-nums">
                                                    {count}
                                                </span>
                                                <button
                                                    type="button"
                                                    onClick={() => bump(group, option.id, 1)}
                                                    className="flex size-7 items-center justify-center rounded-md bg-amber-600 font-bold text-white hover:bg-amber-500"
                                                    aria-label={`Increase ${option.name}`}
                                                >
                                                    +
                                                </button>
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        ) : (
                            <div className="grid grid-cols-2 gap-1.5 md:grid-cols-3">
                                {group.options.map((option) => {
                                    const active = countOf(group.id, option.id) > 0;
                                    return (
                                        <button
                                            key={option.id}
                                            type="button"
                                            onClick={() =>
                                                bump(group, option.id, active ? -1 : 1)
                                            }
                                            className={cn(
                                                'flex items-center justify-between rounded-lg border px-3 py-2 text-sm transition-colors',
                                                active
                                                    ? 'border-amber-500 bg-amber-900/30 text-amber-200'
                                                    : 'border-slate-700 hover:bg-slate-800',
                                            )}
                                        >
                                            <span>{option.name}</span>
                                            <span className="text-[10px] text-slate-400">
                                                {Number(option.price_delta) > 0
                                                    ? `+RM${Number(option.price_delta).toFixed(2)}`
                                                    : '—'}
                                            </span>
                                        </button>
                                    );
                                })}
                            </div>
                        )}
                    </div>
                ))}
            </div>

            {!valid && validationMessage && (
                <p className="mt-3 text-xs text-red-400">{validationMessage}</p>
            )}

            <div className="mt-3 flex gap-2 border-t border-slate-700 pt-3">
                <Button
                    onClick={onClose}
                    variant="outline"
                    className="flex-1 border-slate-700 bg-transparent text-slate-200 hover:bg-slate-800"
                >
                    Cancel
                </Button>
                <Button
                    onClick={submit}
                    disabled={!valid}
                    className="flex-1 bg-amber-600 hover:bg-amber-500"
                >
                    Add — RM{lineTotal.toFixed(2)}
                </Button>
            </div>
        </>
    );
}

function PayBtn({
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
                'flex flex-col items-center gap-1 rounded-md border p-2 text-[10px]',
                active ? 'border-amber-500 bg-amber-900/30' : 'border-slate-700',
            )}
        >
            {icon}
            <span>{label}</span>
        </button>
    );
}

function CashTender({
    total,
    onCancel,
    onConfirm,
}: {
    total: number;
    onCancel: () => void;
    onConfirm: () => void;
}) {
    const [tendered, setTendered] = useState('');
    const received = Number(tendered) || 0;
    const change = received - total;
    const enough = received >= total && total > 0;

    const denominations = [1, 5, 10, 20, 50, 100];
    const quickAmounts = denominations.filter((d) => d >= Math.ceil(total));
    const padded = [Math.ceil(total), ...quickAmounts.filter((q) => q !== Math.ceil(total))].slice(
        0,
        4,
    );

    function press(key: string) {
        if (key === 'C') {
            setTendered('');
            return;
        }
        if (key === '⌫') {
            setTendered((v) => v.slice(0, -1));
            return;
        }
        if (key === '.') {
            if (tendered.includes('.')) return;
            setTendered((v) => (v === '' ? '0.' : v + '.'));
            return;
        }
        setTendered((v) => {
            const next = v + key;
            // disallow leading zero like "007"
            if (next.length > 1 && next[0] === '0' && next[1] !== '.') return next.replace(/^0+/, '');
            // limit to 2 decimal places
            if (next.includes('.') && next.split('.')[1].length > 2) return v;
            return next;
        });
    }

    return (
        <>
            <SheetTitle className="text-slate-100">Cash payment</SheetTitle>

            <div className="mt-3 rounded-xl border border-slate-700 bg-slate-950 p-4">
                <div className="flex items-baseline justify-between">
                    <span className="text-xs text-slate-400">Total due</span>
                    <span className="text-2xl font-bold text-amber-400 tabular-nums">
                        RM{total.toFixed(2)}
                    </span>
                </div>
                <div className="mt-2 flex items-baseline justify-between">
                    <span className="text-xs text-slate-400">Cash received</span>
                    <span className="text-xl font-semibold tabular-nums">
                        {received > 0 ? `RM${received.toFixed(2)}` : '—'}
                    </span>
                </div>
                <div
                    className={cn(
                        'mt-3 flex items-baseline justify-between border-t border-slate-800 pt-3',
                        enough ? 'text-emerald-400' : 'text-slate-500',
                    )}
                >
                    <span className="text-xs font-semibold uppercase">Change</span>
                    <span className="text-3xl font-bold tabular-nums">
                        RM{change > 0 ? change.toFixed(2) : '0.00'}
                    </span>
                </div>
            </div>

            <div className="mt-3 grid grid-cols-4 gap-1.5">
                {padded.map((amt) => (
                    <button
                        key={amt}
                        type="button"
                        onClick={() => setTendered(amt.toFixed(2))}
                        className="rounded-md border border-slate-700 bg-slate-800 px-2 py-2 text-xs font-semibold text-slate-200 hover:border-amber-500 hover:bg-slate-700"
                    >
                        RM{amt}
                    </button>
                ))}
            </div>

            <div className="mt-2 grid grid-cols-3 gap-1.5">
                {['7', '8', '9', '4', '5', '6', '1', '2', '3', '.', '0', '⌫'].map((k) => (
                    <button
                        key={k}
                        type="button"
                        onClick={() => press(k)}
                        className="rounded-md border border-slate-700 bg-slate-800 px-3 py-3 text-base font-bold text-slate-100 hover:bg-slate-700 active:bg-slate-600"
                    >
                        {k}
                    </button>
                ))}
            </div>

            <div className="mt-3 flex gap-2">
                <Button
                    variant="outline"
                    onClick={onCancel}
                    className="flex-1 border-slate-700 bg-transparent text-slate-200 hover:bg-slate-800"
                >
                    Cancel
                </Button>
                <button
                    type="button"
                    onClick={() => press('C')}
                    className="rounded-md border border-slate-700 px-3 py-2 text-xs text-slate-300 hover:bg-slate-800"
                >
                    Clear
                </button>
                <Button
                    onClick={onConfirm}
                    disabled={!enough}
                    className="flex-1 bg-amber-600 hover:bg-amber-500"
                >
                    Confirm & place
                </Button>
            </div>
        </>
    );
}

function groupLabels(labels: string[]): string[] {
    const counts = new Map<string, number>();
    for (const l of labels) counts.set(l, (counts.get(l) ?? 0) + 1);
    return Array.from(counts.entries()).map(([name, n]) => (n > 1 ? `${name} × ${n}` : name));
}

function tierClasses(tier: string | null): string {
    switch (tier?.toLowerCase()) {
        case 'platinum':
            return 'border-indigo-400/60 bg-indigo-500/20 text-indigo-200';
        case 'gold':
            return 'border-yellow-400/60 bg-yellow-500/20 text-yellow-200';
        case 'silver':
            return 'border-slate-400/60 bg-slate-500/20 text-slate-100';
        case 'bronze':
            return 'border-orange-400/60 bg-orange-500/20 text-orange-200';
        default:
            return 'border-amber-400/60 bg-amber-500/20 text-amber-200';
    }
}
