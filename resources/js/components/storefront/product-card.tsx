import { Coffee } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import type { MenuProduct } from '@/types/menu';
import { cn } from '@/lib/utils';

interface Props {
    product: MenuProduct;
    onSelect: (product: MenuProduct) => void;
    isAvailable?: boolean;
}

export function ProductCard({ product, onSelect, isAvailable = true }: Props) {
    return (
        <button
            type="button"
            onClick={() => isAvailable && onSelect(product)}
            disabled={!isAvailable}
            aria-label={isAvailable ? product.name : `${product.name} — out of stock`}
            className={cn(
                'group border-border bg-card relative flex h-full w-full flex-col rounded-xl border p-2.5 text-left shadow-sm transition-all',
                isAvailable
                    ? 'hover:-translate-y-0.5 hover:shadow-md'
                    : 'cursor-not-allowed',
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
                        className={cn(
                            'size-full object-cover',
                            !isAvailable && 'opacity-60',
                        )}
                    />
                ) : (
                    <div className="text-muted-foreground flex size-full items-center justify-center">
                        <Coffee className="size-8" />
                    </div>
                )}
                {product.is_featured && isAvailable && (
                    <Badge
                        variant="warning"
                        className="absolute top-1.5 left-1.5 text-[9px]"
                    >
                        Featured
                    </Badge>
                )}
                {!isAvailable && (
                    <>
                        <div
                            aria-hidden
                            className="absolute inset-0 bg-[repeating-linear-gradient(135deg,rgba(0,0,0,0.18)_0_8px,transparent_8px_18px)]"
                        />
                        <div className="absolute inset-0 flex items-center justify-center">
                            <span className="-rotate-12 select-none rounded-md border-2 border-red-600 bg-white/90 px-3 py-1 text-[11px] font-extrabold uppercase tracking-widest text-red-600 shadow-lg">
                                Out of stock
                            </span>
                        </div>
                    </>
                )}
            </div>
            <div className={cn('mt-2 flex flex-1 flex-col', !isAvailable && 'opacity-60')}>
                <h3 className="line-clamp-2 text-sm font-semibold leading-tight">
                    {product.name}
                </h3>
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
