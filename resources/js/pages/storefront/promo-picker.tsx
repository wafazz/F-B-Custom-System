import { Head, router } from '@inertiajs/react';
import { ArrowRight, Check, Coffee, Gift, Minus, Plus, ShoppingBag } from 'lucide-react';
import { useMemo, useState } from 'react';
import { Button } from '@/components/ui/button';
import StorefrontLayout from '@/layouts/storefront-layout';
import { useCartStore } from '@/stores/cart-store';
import { cn } from '@/lib/utils';

interface PickableProduct {
    id: number;
    name: string;
    image: string | null;
    price: number;
    sku: string;
}

interface Props {
    branch: { id: number; code: string; name: string };
    voucher: {
        code: string;
        name: string;
        description: string | null;
        banner_image: string | null;
        bxgy_buy_qty: number;
        bxgy_free_qty: number;
        free_scope_mode: 'same' | 'cross' | 'any';
    };
    paid_products: PickableProduct[];
    free_products: PickableProduct[];
}

type Picks = Record<number, number>; // productId -> qty

export default function PromoPicker({ branch, voucher, paid_products, free_products }: Props) {
    const addBundle = useCartStore((s) => s.addBundle);
    const [paidPicks, setPaidPicks] = useState<Picks>({});
    const [freePicks, setFreePicks] = useState<Picks>({});

    const paidCount = useMemo(
        () => Object.values(paidPicks).reduce((a, b) => a + b, 0),
        [paidPicks],
    );
    const freeCount = useMemo(
        () => Object.values(freePicks).reduce((a, b) => a + b, 0),
        [freePicks],
    );

    const paidGoal = voucher.bxgy_buy_qty;
    const freeGoal = voucher.bxgy_free_qty;
    const paidComplete = paidCount === paidGoal;
    const freeComplete = freeCount === freeGoal;
    const ready = paidComplete && freeComplete;

    const paidSubtotal = useMemo(
        () => paid_products.reduce((sum, p) => sum + (paidPicks[p.id] ?? 0) * p.price, 0),
        [paidPicks, paid_products],
    );
    const freeSavings = useMemo(
        () => free_products.reduce((sum, p) => sum + (freePicks[p.id] ?? 0) * p.price, 0),
        [freePicks, free_products],
    );

    function tweakPicks(
        set: (p: Picks) => void,
        current: Picks,
        max: number,
        id: number,
        delta: number,
    ) {
        const currentQty = current[id] ?? 0;
        const next = Math.max(0, currentQty + delta);
        const totalOthers = Object.entries(current)
            .filter(([k]) => Number(k) !== id)
            .reduce((sum, [, v]) => sum + v, 0);
        if (totalOthers + next > max) return;
        const updated = { ...current };
        if (next === 0) delete updated[id];
        else updated[id] = next;
        set(updated);
    }

    function confirm() {
        if (!ready) return;
        const picks = [
            ...paid_products.flatMap((p) => {
                const qty = paidPicks[p.id] ?? 0;
                return qty > 0
                    ? [
                          {
                              product: toMenuProduct(p),
                              modifiers: [],
                              quantity: qty,
                              role: 'paid' as const,
                          },
                      ]
                    : [];
            }),
            ...free_products.flatMap((p) => {
                const qty = freePicks[p.id] ?? 0;
                return qty > 0
                    ? [
                          {
                              product: toMenuProduct(p),
                              modifiers: [],
                              quantity: qty,
                              role: 'free' as const,
                          },
                      ]
                    : [];
            }),
        ];
        addBundle(voucher.code, picks, branch.id);
        router.visit(`/branches/${branch.id}/cart`);
    }

    const heading: Record<'same' | 'cross' | 'any', string> = {
        same: 'Pick your free item — same products you can pay for',
        cross: 'Pick your free item — from the bonus list',
        any: 'Pick your free item — any item in the menu',
    };

    return (
        <StorefrontLayout>
            <Head title={`Promo · ${voucher.code}`} />

            <header className="mb-4 overflow-hidden rounded-2xl bg-gradient-to-br from-amber-700 to-amber-900 p-5 text-white shadow-md">
                <p className="text-[10px] font-bold tracking-widest uppercase opacity-80">
                    Promo · {voucher.code}
                </p>
                <h1 className="mt-1 text-xl font-bold">{voucher.name}</h1>
                <p className="mt-1 text-xs text-amber-100/90">
                    Pick {paidGoal} item{paidGoal > 1 ? 's' : ''} to pay for, then {freeGoal} free
                    item{freeGoal > 1 ? 's' : ''} on us.
                </p>
                {voucher.description && (
                    <p className="mt-2 text-[11px] leading-snug text-amber-100/80">
                        {voucher.description}
                    </p>
                )}
            </header>

            <PickerSection
                title={`Step 1 · Pick ${paidGoal} paying item${paidGoal > 1 ? 's' : ''}`}
                icon={<ShoppingBag className="size-4" />}
                count={paidCount}
                goal={paidGoal}
                done={paidComplete}
                products={paid_products}
                picks={paidPicks}
                onTweak={(id, delta) => tweakPicks(setPaidPicks, paidPicks, paidGoal, id, delta)}
                emptyHint="No paying-eligible items at this branch yet."
            />

            <PickerSection
                title={`Step 2 · ${heading[voucher.free_scope_mode]}`}
                icon={<Gift className="size-4" />}
                count={freeCount}
                goal={freeGoal}
                done={freeComplete}
                products={free_products}
                picks={freePicks}
                onTweak={(id, delta) => tweakPicks(setFreePicks, freePicks, freeGoal, id, delta)}
                accent="emerald"
                emptyHint="No eligible free items at this branch."
            />

            <section className="border-border bg-card mb-4 rounded-xl border p-4 shadow-sm">
                <div className="flex items-baseline justify-between text-sm">
                    <span className="text-muted-foreground">You pay</span>
                    <span className="font-semibold">RM{paidSubtotal.toFixed(2)}</span>
                </div>
                <div className="mt-1 flex items-baseline justify-between text-sm">
                    <span className="text-muted-foreground">You save</span>
                    <span className="font-semibold text-emerald-600">
                        −RM{freeSavings.toFixed(2)}
                    </span>
                </div>
            </section>

            <Button onClick={confirm} disabled={!ready} className="w-full">
                {ready ? (
                    <>
                        Add to cart <ArrowRight className="ml-1.5 size-4" />
                    </>
                ) : (
                    `Pick ${paidGoal - paidCount + (freeGoal - freeCount)} more item${
                        paidGoal - paidCount + (freeGoal - freeCount) > 1 ? 's' : ''
                    }`
                )}
            </Button>
        </StorefrontLayout>
    );
}

function PickerSection({
    title,
    icon,
    count,
    goal,
    done,
    products,
    picks,
    onTweak,
    accent = 'amber',
    emptyHint,
}: {
    title: string;
    icon: React.ReactNode;
    count: number;
    goal: number;
    done: boolean;
    products: PickableProduct[];
    picks: Picks;
    onTweak: (productId: number, delta: number) => void;
    accent?: 'amber' | 'emerald';
    emptyHint: string;
}) {
    const accentRing =
        accent === 'amber' ? 'ring-amber-400 bg-amber-50' : 'ring-emerald-400 bg-emerald-50';
    return (
        <section className="border-border bg-card mb-4 rounded-xl border p-4 shadow-sm">
            <div className="mb-3 flex items-center justify-between">
                <h2 className="flex items-center gap-1.5 text-sm font-semibold">
                    {icon} {title}
                </h2>
                <span
                    className={cn(
                        'flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold tracking-wider uppercase',
                        done ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500',
                    )}
                >
                    {done && <Check className="size-3" />} {count} / {goal}
                </span>
            </div>

            {products.length === 0 ? (
                <p className="text-muted-foreground rounded-lg border border-dashed py-6 text-center text-xs">
                    {emptyHint}
                </p>
            ) : (
                <div className="grid grid-cols-2 gap-2 sm:grid-cols-3">
                    {products.map((p) => {
                        const qty = picks[p.id] ?? 0;
                        const chosen = qty > 0;
                        return (
                            <div
                                key={p.id}
                                className={cn(
                                    'border-border bg-card flex flex-col gap-2 rounded-lg border p-2',
                                    chosen && `ring-2 ${accentRing}`,
                                )}
                            >
                                <div className="bg-secondary aspect-square overflow-hidden rounded-md">
                                    {p.image ? (
                                        <img
                                            src={`/storage/${p.image}`}
                                            alt={p.name}
                                            className="size-full object-cover"
                                            loading="lazy"
                                        />
                                    ) : (
                                        <div className="flex size-full items-center justify-center">
                                            <Coffee className="text-muted-foreground size-6" />
                                        </div>
                                    )}
                                </div>
                                <p className="line-clamp-2 text-xs leading-tight font-semibold">
                                    {p.name}
                                </p>
                                <p className="text-primary text-[11px] font-bold">
                                    RM{p.price.toFixed(2)}
                                </p>
                                <div className="mt-auto flex items-center justify-between gap-1">
                                    <button
                                        type="button"
                                        onClick={() => onTweak(p.id, -1)}
                                        disabled={qty === 0}
                                        className="flex size-6 items-center justify-center rounded-md border bg-white text-xs hover:bg-slate-50 disabled:opacity-30"
                                    >
                                        <Minus className="size-3" />
                                    </button>
                                    <span className="text-xs font-bold tabular-nums">{qty}</span>
                                    <button
                                        type="button"
                                        onClick={() => onTweak(p.id, 1)}
                                        disabled={!chosen && count >= goal}
                                        className="flex size-6 items-center justify-center rounded-md border bg-white text-xs hover:bg-slate-50 disabled:opacity-30"
                                    >
                                        <Plus className="size-3" />
                                    </button>
                                </div>
                            </div>
                        );
                    })}
                </div>
            )}
        </section>
    );
}

/** Adapt the picker's pickable shape into the MenuProduct shape addBundle expects. */
function toMenuProduct(p: PickableProduct): import('@/types/menu').MenuProduct {
    return {
        id: p.id,
        name: p.name,
        sku: p.sku,
        slug: '',
        image: p.image,
        gallery: [],
        description: null,
        price: p.price,
        base_price: p.price,
        calories: null,
        prep_time_minutes: 0,
        is_featured: false,
        badge_label: null,
        sst_applicable: true,
        in_stock: true,
        modifier_groups: [],
        tumbler_discount: 0,
    };
}
