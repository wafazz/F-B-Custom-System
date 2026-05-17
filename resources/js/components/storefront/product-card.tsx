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
            className={cn(
                'group border-border bg-card flex h-full w-full flex-col rounded-xl border p-2.5 text-left shadow-sm transition-all',
                isAvailable
                    ? 'hover:-translate-y-0.5 hover:shadow-md'
                    : 'cursor-not-allowed opacity-50',
            )}
        >
            <div className="bg-secondary relative aspect-square w-full flex-shrink-0 overflow-hidden rounded-lg">
                {product.image ? (
                    <img
                        src={`/storage/${product.image}`}
                        alt={product.name}
                        className="size-full object-cover"
                    />
                ) : (
                    <div className="text-muted-foreground flex size-full items-center justify-center">
                        <Coffee className="size-8" />
                    </div>
                )}
                {product.is_featured && (
                    <Badge
                        variant="warning"
                        className="absolute top-1.5 left-1.5 text-[9px]"
                    >
                        Featured
                    </Badge>
                )}
                {!isAvailable && (
                    <div className="bg-background/80 text-muted-foreground absolute inset-0 flex items-center justify-center text-xs font-semibold">
                        Out of stock
                    </div>
                )}
            </div>
            <div className="mt-2 flex flex-1 flex-col">
                <h3 className="line-clamp-2 text-sm font-semibold leading-tight">
                    {product.name}
                </h3>
                {product.description && (
                    <p className="text-muted-foreground mt-1 line-clamp-2 text-[11px] leading-snug">
                        {product.description}
                    </p>
                )}
                <div className="mt-auto flex items-center justify-between pt-2">
                    <span className="text-primary text-sm font-bold">
                        RM{product.price.toFixed(2)}
                    </span>
                    <span className="text-muted-foreground text-[10px]">
                        {product.prep_time_minutes} min
                    </span>
                </div>
            </div>
        </button>
    );
}
