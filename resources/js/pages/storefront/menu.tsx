import { Head } from '@inertiajs/react';
import { Coffee } from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';
import { ModifierSheet } from '@/components/storefront/modifier-sheet';
import { ProductCard } from '@/components/storefront/product-card';
import { Badge } from '@/components/ui/badge';
import { useBranchMenu, useStockSubscription } from '@/hooks/use-branch-menu';
import StorefrontLayout from '@/layouts/storefront-layout';
import { useBranchStore } from '@/stores/branch-store';
import { useCartStore } from '@/stores/cart-store';
import type { BranchContext, MenuProduct, SelectedModifier, StockChangedEvent } from '@/types/menu';
import { cn } from '@/lib/utils';

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

    const [activeCategory, setActiveCategory] = useState<number | null>(null);
    const [selectedProduct, setSelectedProduct] = useState<MenuProduct | null>(null);

    const allCategories = data?.categories ?? [];
    const visibleCategories =
        activeCategory === null
            ? allCategories
            : allCategories.filter((c) => c.id === activeCategory);

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
                <>
                    <div className="-mx-4 mb-4 overflow-x-auto px-4">
                        <div className="flex gap-2 pb-1">
                            <CategoryPill
                                label="All"
                                active={activeCategory === null}
                                onClick={() => setActiveCategory(null)}
                            />
                            {data.categories.map((cat) => (
                                <CategoryPill
                                    key={cat.id}
                                    label={cat.name}
                                    active={activeCategory === cat.id}
                                    onClick={() => setActiveCategory(cat.id)}
                                />
                            ))}
                        </div>
                    </div>

                    <div className="space-y-6">
                        {visibleCategories.map((cat) => (
                            <section key={cat.id}>
                                <div className="mb-2 flex items-center gap-2">
                                    <Coffee className="text-primary size-4" />
                                    <h2 className="text-muted-foreground text-sm font-semibold tracking-wider uppercase">
                                        {cat.name}
                                    </h2>
                                </div>
                                <div className="grid gap-2">
                                    {cat.products.map((product) => (
                                        <ProductCard
                                            key={product.id}
                                            product={product}
                                            isAvailable={!unavailable.has(product.id)}
                                            onSelect={setSelectedProduct}
                                        />
                                    ))}
                                </div>
                            </section>
                        ))}
                    </div>
                </>
            )}

            <ModifierSheet
                product={selectedProduct}
                open={selectedProduct !== null}
                onOpenChange={(open) => !open && setSelectedProduct(null)}
                onAdd={handleAdd}
            />
        </StorefrontLayout>
    );
}

function CategoryPill({
    label,
    active,
    onClick,
}: {
    label: string;
    active: boolean;
    onClick: () => void;
}) {
    return (
        <button
            type="button"
            onClick={onClick}
            className={cn(
                'flex-shrink-0 rounded-full px-4 py-1.5 text-xs font-medium transition-colors',
                active
                    ? 'bg-primary text-primary-foreground'
                    : 'bg-secondary text-secondary-foreground hover:bg-secondary/80',
            )}
        >
            {label}
        </button>
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
