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
                'group border-border bg-card flex w-full items-stretch gap-3 rounded-xl border p-3 text-left shadow-sm transition-all',
                isAvailable
                    ? 'hover:-translate-y-0.5 hover:shadow-md'
                    : 'cursor-not-allowed opacity-50',
            )}
        >
            <div className="bg-secondary relative size-20 flex-shrink-0 overflow-hidden rounded-lg">
                {product.image ? (
                    <img
                        src={`/storage/${product.image}`}
                        alt={product.name}
                        className="size-full object-cover"
                    />
                ) : (
                    <div className="text-muted-foreground flex size-full items-center justify-center">
                        <Coffee className="size-7" />
                    </div>
                )}
                {!isAvailable && (
                    <div className="bg-background/80 text-muted-foreground absolute inset-0 flex items-center justify-center text-xs font-semibold">
                        Out of stock
                    </div>
                )}
            </div>
            <div className="flex flex-1 flex-col justify-between">
                <div>
                    <div className="flex items-center gap-2">
                        <h3 className="text-sm font-semibold">{product.name}</h3>
                        {product.is_featured && (
                            <Badge variant="warning" className="text-[10px]">
                                Featured
                            </Badge>
                        )}
                    </div>
                    {product.description && (
                        <p className="text-muted-foreground mt-1 line-clamp-2 text-xs">
                            {product.description}
                        </p>
                    )}
                </div>
                <div className="mt-2 flex items-center justify-between">
                    <span className="text-primary text-sm font-semibold">
                        RM{product.price.toFixed(2)}
                    </span>
                    <span className="text-muted-foreground text-[10px]">
                        {product.prep_time_minutes}min prep
                    </span>
                </div>
            </div>
        </button>
    );
}
