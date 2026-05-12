import { Head } from '@inertiajs/react';
import {
    Cake,
    Coffee,
    Croissant,
    Flame,
    Leaf,
    Snowflake,
    Sparkles,
    Sun,
    type LucideIcon,
} from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';
import { ModifierSheet } from '@/components/storefront/modifier-sheet';
import { ProductCard } from '@/components/storefront/product-card';
import { Badge } from '@/components/ui/badge';
import { useBranchMenu, useStockSubscription } from '@/hooks/use-branch-menu';
import StorefrontLayout from '@/layouts/storefront-layout';
import { useBranchStore } from '@/stores/branch-store';
import { useCartStore } from '@/stores/cart-store';
import type {
    BranchContext,
    MenuCategory,
    MenuProduct,
    SelectedModifier,
    StockChangedEvent,
} from '@/types/menu';
import { cn } from '@/lib/utils';

const CATEGORY_ICONS: Record<string, LucideIcon> = {
    'hot-coffee': Flame,
    'cold-coffee': Snowflake,
    'specialty-drinks': Sparkles,
    'tea-others': Leaf,
    pastries: Croissant,
    cakes: Cake,
    breakfast: Sun,
};

function iconFor(slug: string): LucideIcon {
    return CATEGORY_ICONS[slug] ?? Coffee;
}

function categoryThumb(cat: MenuCategory): string | null {
    return cat.products.find((p) => p.image)?.image ?? null;
}

interface Props {
    branch: BranchContext;
    reverb: { channel: string; event: string };
}

export default function Menu({ branch }: Props) {
    const setBranch = useBranchStore((s) => s.setBranch);
    const addToCart = useCartStore((s) => s.add);
    const rebindBranch = useCartStore((s) => s.rebindBranch);

    useEffect(() => {
        rebindBranch(branch.id);
        setBranch({
            id: branch.id,
            code: branch.code,
            name: branch.name,
            address: '',
            city: null,
            state: null,
            phone: null,
            latitude: null,
            longitude: null,
            operating_hours: null,
            logo: branch.logo,
            is_open_now: branch.is_open_now,
        });
    }, [branch, rebindBranch, setBranch]);

    const { data, isLoading, isError } = useBranchMenu(branch.id);
    const [unavailable, setUnavailable] = useState<Set<number>>(new Set());

    const onStockChange = useCallback((event: StockChangedEvent) => {
        setUnavailable((prev) => {
            const next = new Set(prev);
            if (event.is_available) next.delete(event.product_id);
            else next.add(event.product_id);
            return next;
        });
    }, []);

    useStockSubscription(branch.id, onStockChange);

    const [initialSlug] = useState<string | null>(() =>
        typeof window !== 'undefined'
            ? new URLSearchParams(window.location.search).get('category')
            : null,
    );
    const [initialProductId] = useState<number | null>(() => {
        if (typeof window === 'undefined') return null;
        const raw = new URLSearchParams(window.location.search).get('product');
        const id = raw ? Number(raw) : NaN;
        return Number.isFinite(id) ? id : null;
    });
    const [initialDismissed, setInitialDismissed] = useState(false);
    const [userPicked, setUserPicked] = useState<number | null>(null);
    const [userPickedProduct, setUserPickedProduct] = useState<MenuProduct | null>(null);

    const initialProductMatch: MenuProduct | null =
        data && initialProductId !== null && !initialDismissed
            ? (data.categories.flatMap((c) => c.products).find((p) => p.id === initialProductId) ??
              null)
            : null;

    const selectedProduct = userPickedProduct ?? initialProductMatch;

    const allCategories = data?.categories ?? [];

    const activeCategory: number | null =
        typeof userPicked === 'number'
            ? userPicked
            : initialSlug && allCategories.length > 0
              ? (allCategories.find((c) => c.slug === initialSlug)?.id ?? allCategories[0].id)
              : (allCategories[0]?.id ?? null);

    const visibleCategory = allCategories.find((c) => c.id === activeCategory) ?? null;

    function handleAdd(product: MenuProduct, modifiers: SelectedModifier[], qty: number) {
        addToCart(product, modifiers, qty, branch.id);
    }

    return (
        <StorefrontLayout>
            <Head title={`${branch.name} — Menu`} />

            <div className="mb-4 flex items-start justify-between gap-3">
                <div>
                    <h1 className="text-xl font-bold">{branch.name}</h1>
                    <p className="text-muted-foreground text-xs">{branch.code}</p>
                </div>
                <div className="flex flex-col items-end gap-1 text-xs">
                    <Badge variant={branch.is_open_now ? 'success' : 'danger'}>
                        {branch.is_open_now ? 'Open now' : 'Closed'}
                    </Badge>
                    {branch.sst_enabled && (
                        <span className="text-muted-foreground">
                            +{branch.sst_rate.toFixed(0)}% SST
                        </span>
                    )}
                </div>
            </div>

            {isLoading && <MenuSkeleton />}
            {isError && (
                <div className="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                    Failed to load menu. Please refresh.
                </div>
            )}
            {data && data.categories.length === 0 && (
                <div className="border-border bg-card text-muted-foreground rounded-lg border border-dashed p-6 text-center text-sm">
                    {data.message ?? 'No items available right now.'}
                </div>
            )}

            {data && data.categories.length > 0 && (
                <div className="-mx-4 flex gap-0">
                    <aside className="bg-muted/40 border-border w-24 shrink-0 border-r">
                        <ul className="flex flex-col">
                            {data.categories.map((cat) => {
                                const Icon = iconFor(cat.slug);
                                const thumb = categoryThumb(cat);
                                const active = cat.id === activeCategory;
                                return (
                                    <li key={cat.id}>
                                        <button
                                            type="button"
                                            onClick={() => setUserPicked(cat.id)}
                                            className={cn(
                                                'flex w-full flex-col items-center gap-1.5 px-2 py-3 text-center transition-colors',
                                                active
                                                    ? 'bg-card border-primary border-l-4 pl-1'
                                                    : 'hover:bg-card/60',
                                            )}
                                        >
                                            <span
                                                className={cn(
                                                    'flex size-14 items-center justify-center overflow-hidden rounded-full transition-all',
                                                    active
                                                        ? 'ring-primary ring-2 ring-offset-2'
                                                        : 'bg-secondary',
                                                )}
                                            >
                                                {thumb ? (
                                                    <img
                                                        src={
                                                            thumb.startsWith('http')
                                                                ? thumb
                                                                : `/storage/${thumb}`
                                                        }
                                                        alt={cat.name}
                                                        className="size-full object-cover"
                                                    />
                                                ) : (
                                                    <Icon
                                                        className={cn(
                                                            'size-6',
                                                            active
                                                                ? 'text-primary'
                                                                : 'text-secondary-foreground/70',
                                                        )}
                                                    />
                                                )}
                                            </span>
                                            <span
                                                className={cn(
                                                    'text-[10px] leading-tight font-medium',
                                                    active
                                                        ? 'text-foreground'
                                                        : 'text-muted-foreground',
                                                )}
                                            >
                                                {cat.name}
                                            </span>
                                        </button>
                                    </li>
                                );
                            })}
                        </ul>
                    </aside>

                    <main className="min-w-0 flex-1 px-3 py-2">
                        {visibleCategory && (
                            <>
                                <div className="mb-3 flex items-center gap-2">
                                    <Coffee className="text-primary size-4" />
                                    <h2 className="text-sm font-bold tracking-wider uppercase">
                                        {visibleCategory.name}
                                    </h2>
                                    <span className="text-muted-foreground text-xs">
                                        · {visibleCategory.products.length} items
                                    </span>
                                </div>
                                <div className="grid gap-2">
                                    {visibleCategory.products.map((product) => (
                                        <ProductCard
                                            key={product.id}
                                            product={product}
                                            isAvailable={!unavailable.has(product.id)}
                                            onSelect={setUserPickedProduct}
                                        />
                                    ))}
                                    {visibleCategory.products.length === 0 && (
                                        <p className="text-muted-foreground py-12 text-center text-sm">
                                            No items in this category yet.
                                        </p>
                                    )}
                                </div>
                            </>
                        )}
                    </main>
                </div>
            )}

            <ModifierSheet
                product={selectedProduct}
                open={selectedProduct !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setUserPickedProduct(null);
                        setInitialDismissed(true);
                    }
                }}
                onAdd={handleAdd}
            />
        </StorefrontLayout>
    );
}

function MenuSkeleton() {
    return (
        <div className="space-y-3">
            {[1, 2, 3, 4, 5].map((i) => (
                <div key={i} className="border-border bg-card flex gap-3 rounded-xl border p-3">
                    <div className="bg-secondary size-20 animate-pulse rounded-lg" />
                    <div className="flex-1 space-y-2">
                        <div className="bg-secondary h-3 w-3/4 animate-pulse rounded" />
                        <div className="bg-secondary h-2.5 w-full animate-pulse rounded" />
                        <div className="bg-secondary h-3 w-1/4 animate-pulse rounded" />
                    </div>
                </div>
            ))}
        </div>
    );
}
