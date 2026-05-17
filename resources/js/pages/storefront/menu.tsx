import { Head, router, usePage } from '@inertiajs/react';
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
import { useCallback, useEffect, useRef, useState } from 'react';
import { ComboSheet } from '@/components/storefront/combo-sheet';
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
    MenuCombo,
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
    // Prefer the admin-uploaded category image; fall back to the first product
    // image only when the category has no image of its own.
    return cat.image ?? cat.products.find((p) => p.image)?.image ?? null;
}

type Section = {
    id: number;
    name: string;
    slug: string;
    image: string | null;
    products: MenuProduct[];
};

interface Props {
    branch: BranchContext;
    reverb: { channel: string; event: string };
}

export default function Menu({ branch }: Props) {
    const setBranch = useBranchStore((s) => s.setBranch);
    const addToCart = useCartStore((s) => s.add);
    const addComboToCart = useCartStore((s) => s.addCombo);
    const [activeCombo, setActiveCombo] = useState<MenuCombo | null>(null);
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

    // Parents are derived from any category that declares a parent_id. Each
    // parent carries its own slug so URL deep-links like ?category=pastry can
    // match either a real child slug or a parent slug.
    const parents: {
        id: number;
        name: string;
        slug: string;
        image: string | null;
        icon: string | null;
    }[] = (() => {
        const map = new Map<
            number,
            {
                id: number;
                name: string;
                slug: string;
                image: string | null;
                icon: string | null;
                sort: number;
            }
        >();
        for (const c of allCategories) {
            if (c.parent_id === null || c.parent_name === null) continue;
            if (!map.has(c.parent_id)) {
                map.set(c.parent_id, {
                    id: c.parent_id,
                    name: c.parent_name,
                    slug: c.parent_slug ?? '',
                    image: c.parent_image ?? null,
                    icon: c.parent_icon ?? null,
                    sort: c.parent_sort_order ?? c.sort_order,
                });
            }
        }
        return [...map.values()]
            .sort((a, b) => a.sort - b.sort)
            .map((p) => ({
                id: p.id,
                name: p.name,
                slug: p.slug,
                image: p.image,
                icon: p.icon,
            }));
    })();
    const hierarchical = parents.length > 0;

    // Sections drive both the sidebar and the scrollable content. In
    // hierarchical mode each parent collapses its own products plus every
    // child's products into a single section; flat menus map 1:1.
    const sections: Section[] = hierarchical
        ? parents.map((p) => {
              const self = allCategories.find((c) => c.id === p.id);
              const children = allCategories.filter((c) => c.parent_id === p.id);
              return {
                  id: p.id,
                  name: p.name,
                  slug: p.slug,
                  image:
                      p.image ??
                      self?.image ??
                      children.find((c) => c.image)?.image ??
                      null,
                  products: [
                      ...(self?.products ?? []),
                      ...children.flatMap((c) => c.products),
                  ],
              };
          })
        : allCategories.map((c) => ({
              id: c.id,
              name: c.name,
              slug: c.slug,
              image: categoryThumb(c),
              products: c.products,
          }));

    // ?category=foo can match a section directly, or a child whose parent
    // owns the section in hierarchical mode.
    const slugSection = initialSlug
        ? (sections.find((s) => s.slug === initialSlug) ??
              (() => {
                  const child = allCategories.find((c) => c.slug === initialSlug);
                  if (!child) return null;
                  return (
                      sections.find(
                          (s) => s.id === child.parent_id || s.id === child.id,
                      ) ?? null
                  );
              })())
        : null;

    const activeCategory: number | null =
        typeof userPicked === 'number'
            ? userPicked
            : (slugSection?.id ?? sections[0]?.id ?? null);

    const sectionRefs = useRef<Map<number, HTMLElement>>(new Map());
    const scrollingToRef = useRef<number | null>(null);

    const setSectionRef = (id: number) => (el: HTMLElement | null) => {
        if (el) sectionRefs.current.set(id, el);
        else sectionRefs.current.delete(id);
    };

    const scrollToCategory = (id: number) => {
        setUserPicked(id);
        const el = sectionRefs.current.get(id);
        if (!el) return;
        scrollingToRef.current = id;
        el.scrollIntoView({ behavior: 'smooth', block: 'start' });
        window.setTimeout(() => {
            scrollingToRef.current = null;
        }, 700);
    };

    const sidebarKey = sections.map((s) => s.id).join(',');
    useEffect(() => {
        if (!data || sections.length === 0) return;
        const observer = new IntersectionObserver(
            (entries) => {
                if (scrollingToRef.current !== null) return;
                const top = entries
                    .filter((e) => e.isIntersecting)
                    .sort(
                        (a, b) => a.boundingClientRect.top - b.boundingClientRect.top,
                    )[0];
                if (!top) return;
                const id = Number((top.target as HTMLElement).dataset.catId);
                if (Number.isFinite(id)) setUserPicked(id);
            },
            { rootMargin: '-30% 0px -60% 0px', threshold: 0 },
        );
        sectionRefs.current.forEach((el) => observer.observe(el));
        return () => observer.disconnect();
    }, [data, sidebarKey]);

    const { auth } = usePage().props as unknown as { auth: { user: { id: number } | null } };

    function handleAdd(product: MenuProduct, modifiers: SelectedModifier[], qty: number) {
        addToCart(product, modifiers, qty, branch.id);
    }

    function handleBuyNow(product: MenuProduct, modifiers: SelectedModifier[], qty: number) {
        addToCart(product, modifiers, qty, branch.id);
        if (auth.user) {
            router.visit(`/branches/${branch.id}/checkout`);
        } else {
            router.visit(`/login?redirect=/branches/${branch.id}/checkout`);
        }
    }

    function handleAddCombo(combo: MenuCombo, qty: number) {
        addComboToCart(combo, qty, branch.id);
    }

    function handleBuyNowCombo(combo: MenuCombo, qty: number) {
        addComboToCart(combo, qty, branch.id);
        router.visit(
            auth.user
                ? `/branches/${branch.id}/checkout`
                : `/login?redirect=/branches/${branch.id}/checkout`,
        );
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

            {!branch.is_open_now && (
                <div className="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-xs text-red-800">
                    ⏰ <strong>This branch is currently closed.</strong> Feel free to browse the
                    menu — online ordering will resume during operating hours.
                </div>
            )}

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

            {data && (data.combos?.length ?? 0) > 0 && (
                <section className="mb-4">
                    <h2 className="mb-2 flex items-center gap-1.5 text-sm font-semibold">
                        <Sparkles className="size-3.5 text-amber-500" /> Combos
                    </h2>
                    <div className="-mx-1 flex snap-x snap-mandatory gap-2 overflow-x-auto px-1 pb-2">
                        {data.combos!.map((combo) => (
                            <button
                                key={combo.id}
                                type="button"
                                onClick={() => setActiveCombo(combo)}
                                className="border-amber-200 bg-gradient-to-br from-amber-50 to-orange-100 hover:from-amber-100 hover:to-orange-200 dark:border-amber-900 dark:from-amber-950/50 dark:to-orange-950/50 flex w-44 shrink-0 snap-start flex-col gap-1.5 rounded-xl border p-2 text-left shadow-sm transition-colors"
                            >
                                <div className="bg-secondary aspect-[4/3] overflow-hidden rounded-lg">
                                    {combo.image ? (
                                        <img
                                            src={`/storage/${combo.image}`}
                                            alt={combo.name}
                                            className="size-full object-cover"
                                        />
                                    ) : (
                                        <div className="flex size-full items-center justify-center">
                                            <Coffee className="text-muted-foreground size-6" />
                                        </div>
                                    )}
                                </div>
                                <p className="line-clamp-1 text-sm font-semibold leading-tight">
                                    {combo.name}
                                </p>
                                <p className="text-muted-foreground line-clamp-1 text-[10px]">
                                    {combo.items.map((i) => `${i.quantity}× ${i.name}`).join(' + ')}
                                </p>
                                <p className="text-primary text-sm font-bold">
                                    RM{Number(combo.price).toFixed(2)}
                                </p>
                            </button>
                        ))}
                    </div>
                </section>
            )}

            {data && data.categories.length > 0 && (
                <div className="-mx-4 flex items-start gap-0">
                    <aside className="bg-muted/40 border-border sticky top-16 max-h-[calc(100vh-4rem)] w-24 shrink-0 self-start overflow-y-auto border-r">
                        <ul className="flex flex-col">
                            {sections.map((sec) => {
                                const Icon = iconFor(sec.slug);
                                const thumb =
                                    sec.image ??
                                    sec.products.find((p) => p.image)?.image ??
                                    null;
                                const active = sec.id === activeCategory;
                                return (
                                    <li key={sec.id}>
                                        <button
                                            type="button"
                                            onClick={() => scrollToCategory(sec.id)}
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
                                                        alt={sec.name}
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
                                                {sec.name}
                                            </span>
                                        </button>
                                    </li>
                                );
                            })}
                        </ul>
                    </aside>

                    <main className="min-w-0 flex-1 px-3 py-2">
                        {sections.map((sec) => (
                            <section
                                key={sec.id}
                                ref={setSectionRef(sec.id)}
                                data-cat-id={sec.id}
                                className="scroll-mt-20 mb-8 last:mb-4"
                            >
                                <div className="mb-3 flex items-center gap-2">
                                    <Coffee className="text-primary size-4" />
                                    <h2 className="text-sm font-bold tracking-wider uppercase">
                                        {sec.name}
                                    </h2>
                                    <span className="text-muted-foreground text-xs">
                                        · {sec.products.length} items
                                    </span>
                                </div>
                                <div className="grid grid-cols-2 gap-2.5 sm:gap-3">
                                    {sec.products.map((product) => (
                                        <ProductCard
                                            key={product.id}
                                            product={product}
                                            isAvailable={!unavailable.has(product.id)}
                                            onSelect={setUserPickedProduct}
                                        />
                                    ))}
                                    {sec.products.length === 0 && (
                                        <p className="text-muted-foreground col-span-2 py-12 text-center text-sm">
                                            No items in this category yet.
                                        </p>
                                    )}
                                </div>
                            </section>
                        ))}
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
                onBuyNow={handleBuyNow}
            />

            <ComboSheet
                combo={activeCombo}
                open={activeCombo !== null}
                onOpenChange={(open) => {
                    if (!open) setActiveCombo(null);
                }}
                onAdd={handleAddCombo}
                onBuyNow={handleBuyNowCombo}
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
