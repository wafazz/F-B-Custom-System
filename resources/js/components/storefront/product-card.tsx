import { router, usePage } from '@inertiajs/react';
import { Coffee, Heart } from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import type { MenuProduct } from '@/types/menu';
import { cn } from '@/lib/utils';

interface Props {
    product: MenuProduct;
    onSelect: (product: MenuProduct) => void;
    isAvailable?: boolean;
}

export function ProductCard({ product, onSelect, isAvailable = true }: Props) {
    const { auth } = usePage().props as unknown as {
        auth: { user: { name: string } | null; favourite_product_ids: number[] };
    };
    const initial = auth.user !== null && (auth.favourite_product_ids ?? []).includes(product.id);
    const [favourited, setFavourited] = useState(initial);
    const [pending, setPending] = useState(false);

    async function handleFavouriteToggle(e: React.MouseEvent) {
        e.stopPropagation();
        if (!auth.user) {
            router.visit(`/login?redirect=${encodeURIComponent(window.location.pathname)}`);
            return;
        }
        if (pending) return;
        setPending(true);
        // Optimistic flip
        setFavourited((f) => !f);
        try {
            const csrf =
                document.cookie
                    .split('; ')
                    .find((c) => c.startsWith('XSRF-TOKEN='))
                    ?.substring('XSRF-TOKEN='.length) ?? '';
            const res = await fetch(`/favourites/${product.id}/toggle`, {
                method: 'POST',
                credentials: 'same-origin',
                redirect: 'error',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-XSRF-TOKEN': decodeURIComponent(csrf),
                },
            });
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            const data = (await res.json()) as { favourited: boolean };
            setFavourited(data.favourited);
        } catch {
            setFavourited((f) => !f); // revert
        } finally {
            setPending(false);
        }
    }
    return (
        <button
            type="button"
            onClick={() => isAvailable && onSelect(product)}
            disabled={!isAvailable}
            aria-label={isAvailable ? product.name : `${product.name} — out of stock`}
            className={cn(
                'group border-border bg-card relative flex h-full w-full flex-col rounded-xl border p-2.5 text-left shadow-sm transition-all',
                isAvailable ? 'hover:-translate-y-0.5 hover:shadow-md' : 'cursor-not-allowed',
            )}
        >
            <div
                className={cn(
                    'bg-secondary relative aspect-square w-full flex-shrink-0 overflow-hidden rounded-lg',
                    !isAvailable && 'grayscale',
                )}
            >
                {product.image ? (
                    <img
                        src={`/storage/${product.image}`}
                        alt={product.name}
                        className={cn('size-full object-cover', !isAvailable && 'opacity-60')}
                    />
                ) : (
                    <div className="text-muted-foreground flex size-full items-center justify-center">
                        <Coffee className="size-8" />
                    </div>
                )}
                {isAvailable && (product.badge_label || product.is_featured) && (
                    <Badge variant="warning" className="absolute top-1.5 left-1.5 text-[9px]">
                        {product.badge_label ?? 'Featured'}
                    </Badge>
                )}
                <span
                    role="button"
                    tabIndex={isAvailable ? 0 : -1}
                    aria-label={favourited ? 'Remove from favourites' : 'Add to favourites'}
                    onClick={handleFavouriteToggle}
                    onKeyDown={(e) => {
                        if (e.key === 'Enter' || e.key === ' ') {
                            e.preventDefault();
                            handleFavouriteToggle(e as unknown as React.MouseEvent);
                        }
                    }}
                    className={cn(
                        'absolute top-1.5 right-1.5 flex size-7 cursor-pointer items-center justify-center rounded-full bg-white/85 shadow backdrop-blur-sm transition-all',
                        favourited
                            ? 'text-rose-500 hover:bg-white'
                            : 'text-neutral-500 hover:bg-white hover:text-rose-500',
                        pending && 'opacity-60',
                    )}
                >
                    <Heart className={cn('size-3.5', favourited && 'fill-rose-500')} />
                </span>
                {!isAvailable && (
                    <>
                        <div
                            aria-hidden
                            className="absolute inset-0 bg-[repeating-linear-gradient(135deg,rgba(0,0,0,0.18)_0_8px,transparent_8px_18px)]"
                        />
                        <div className="absolute inset-0 flex items-center justify-center">
                            <span className="-rotate-12 rounded-md border-2 border-red-600 bg-white/90 px-3 py-1 text-[11px] font-extrabold tracking-widest text-red-600 uppercase shadow-lg select-none">
                                Out of stock
                            </span>
                        </div>
                    </>
                )}
            </div>
            <div className={cn('mt-2 flex flex-1 flex-col', !isAvailable && 'opacity-60')}>
                <h3 className="line-clamp-2 text-sm leading-tight font-semibold">{product.name}</h3>
                {product.description && (
                    <p className="text-muted-foreground mt-1 line-clamp-2 text-[11px] leading-snug">
                        {product.description}
                    </p>
                )}
                <div className="mt-auto flex items-center justify-between pt-2">
                    <span
                        className={cn(
                            'text-sm font-bold',
                            isAvailable ? 'text-primary' : 'text-muted-foreground line-through',
                        )}
                    >
                        RM{product.price.toFixed(2)}
                    </span>
                    <span className="text-muted-foreground text-[10px]">
                        {isAvailable ? `${product.prep_time_minutes} min` : 'Unavailable'}
                    </span>
                </div>
            </div>
        </button>
    );
}
