import { Head, router } from '@inertiajs/react';
import { Banknote, CreditCard, Hash, Plus, ShoppingBag, Store, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
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
    branch: { id: number; code: string; name: string; sst_rate: number; sst_enabled: boolean };
    staff: { name: string };
    categories: Category[];
}

export default function PosWalkIn({ branch, categories }: Props) {
    const [lines, setLines] = useState<Line[]>([]);
    const [orderType, setOrderType] = useState<'pickup' | 'dine_in'>('pickup');
    const [tableNumber, setTableNumber] = useState('');
    const [paymentMethod, setPaymentMethod] = useState<'cash' | 'card' | 'duitnow'>('cash');
    const [activeCategory, setActiveCategory] = useState(categories[0]?.name ?? null);

    function addProduct(product: PosProduct) {
        // Auto-pick required defaults; for required-without-defaults open a quick prompt fallback.
        const optionIds: number[] = [];
        const labels: string[] = [];
        let extra = 0;
        for (const group of product.modifier_groups) {
            const defaults = group.options.filter((o) => o.is_default);
            const pick =
                defaults.length > 0
                    ? defaults
                    : group.is_required
                      ? group.options.slice(0, group.min_select)
                      : [];
            for (const opt of pick.slice(0, group.max_select)) {
                optionIds.push(opt.id);
                labels.push(opt.name);
                extra += Number(opt.price_delta);
            }
        }
        const key = `${product.id}#${optionIds.sort().join('|')}`;
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
    const total = subtotal + sst;
    const visibleProducts = categories.find((c) => c.name === activeCategory)?.products ?? [];

    const canSubmit = lines.length > 0 && (orderType === 'pickup' || tableNumber.trim().length > 0);

    function submit() {
        if (!canSubmit) return;
        router.post('/pos/walk-in', {
            order_type: orderType,
            dine_in_table: orderType === 'dine_in' ? tableNumber : null,
            payment_method: paymentMethod,
            lines: lines.map((l) => ({
                product_id: l.product_id,
                quantity: l.quantity,
                modifier_option_ids: l.modifier_option_ids,
            })),
        });
    }

    return (
        <PosLayout>
            <Head title="POS · Walk-in" />

            <div className="grid grid-cols-1 gap-4 lg:grid-cols-[1fr_360px]">
                <section>
                    <div className="-mx-1 mb-3 flex gap-2 overflow-x-auto px-1 pb-1">
                        {categories.map((c) => (
                            <button
                                key={c.name}
                                type="button"
                                onClick={() => setActiveCategory(c.name)}
                                className={cn(
                                    'flex-shrink-0 rounded-full px-4 py-1.5 text-xs font-medium transition-colors',
                                    activeCategory === c.name
                                        ? 'bg-amber-600 text-white'
                                        : 'bg-slate-800 text-slate-300 hover:bg-slate-700',
                                )}
                            >
                                {c.name}
                            </button>
                        ))}
                    </div>

                    <div className="grid grid-cols-2 gap-2 md:grid-cols-3">
                        {visibleProducts.map((p) => (
                            <button
                                key={p.id}
                                type="button"
                                onClick={() => addProduct(p)}
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

                <aside className="rounded-xl border border-slate-700 bg-slate-900 p-4">
                    <h2 className="mb-3 text-sm font-bold">Cart</h2>

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
                                        {l.modifier_labels.join(' · ')}
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
                        {branch.sst_enabled && (
                            <div className="flex justify-between text-slate-400">
                                <span>SST</span>
                                <span>RM{sst.toFixed(2)}</span>
                            </div>
                        )}
                        <div className="flex justify-between text-base font-bold">
                            <span>Total</span>
                            <span className="text-amber-400">RM{total.toFixed(2)}</span>
                        </div>
                    </div>

                    <Button
                        onClick={submit}
                        disabled={!canSubmit}
                        className="mt-3 w-full bg-amber-600 hover:bg-amber-500"
                    >
                        Charge & place order
                    </Button>
                </aside>
            </div>
        </PosLayout>
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
