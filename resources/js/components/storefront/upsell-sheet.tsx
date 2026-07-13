import { Check, Coffee, Plus } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Sheet, SheetContent, SheetTitle } from '@/components/ui/sheet';
import { useCartStore } from '@/stores/cart-store';
import type { MenuProduct } from '@/types/menu';

export interface UpsellProduct {
    id: number;
    name: string;
    image: string | null;
    price: number;
    tumbler_discount: number;
}

interface Props {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    title: string;
    products: UpsellProduct[];
    branchId: number;
    onContinue: () => void;
}

export function UpsellSheet({ open, onOpenChange, title, products, branchId, onContinue }: Props) {
    const add = useCartStore((s) => s.add);
    const [added, setAdded] = useState<number[]>([]);

    const handleAdd = (p: UpsellProduct) => {
        add(
            {
                id: p.id,
                name: p.name,
                image: p.image,
                price: p.price,
                tumbler_discount: p.tumbler_discount,
            } as unknown as MenuProduct,
            [],
            1,
            branchId,
        );
        setAdded((prev) => (prev.includes(p.id) ? prev : [...prev, p.id]));
    };

    return (
        <Sheet open={open} onOpenChange={onOpenChange}>
            <SheetContent
                side="bottom"
                className="bg-white text-neutral-900 sm:mx-auto sm:max-w-3xl sm:rounded-xl dark:bg-neutral-950 dark:text-neutral-50"
            >
                <div className="flex flex-col gap-4 p-1">
                    <div>
                        <SheetTitle className="text-lg font-bold">{title}</SheetTitle>
                        <p className="text-muted-foreground text-sm">
                            Add a little extra before you check out.
                        </p>
                    </div>

                    <ul className="max-h-[50vh] space-y-2 overflow-y-auto">
                        {products.map((p) => {
                            const isAdded = added.includes(p.id);
                            return (
                                <li
                                    key={p.id}
                                    className="border-border bg-card flex items-center gap-3 rounded-xl border p-2.5 shadow-sm"
                                >
                                    <div className="bg-secondary flex size-14 flex-shrink-0 items-center justify-center overflow-hidden rounded-lg">
                                        {p.image ? (
                                            <img
                                                src={`/storage/${p.image}`}
                                                alt={p.name}
                                                className="size-full object-cover"
                                            />
                                        ) : (
                                            <Coffee className="text-muted-foreground size-6" />
                                        )}
                                    </div>
                                    <div className="flex min-w-0 flex-1 flex-col">
                                        <h3 className="truncate text-sm font-semibold">{p.name}</h3>
                                        <span className="text-primary text-sm font-bold">
                                            RM{p.price.toFixed(2)}
                                        </span>
                                    </div>
                                    <Button
                                        type="button"
                                        size="sm"
                                        variant={isAdded ? 'secondary' : 'default'}
                                        onClick={() => handleAdd(p)}
                                        className="flex-shrink-0"
                                    >
                                        {isAdded ? (
                                            <>
                                                <Check className="mr-1 size-4" /> Added
                                            </>
                                        ) : (
                                            <>
                                                <Plus className="mr-1 size-4" /> Add
                                            </>
                                        )}
                                    </Button>
                                </li>
                            );
                        })}
                    </ul>

                    <div className="flex flex-col gap-2">
                        <Button className="w-full" onClick={onContinue}>
                            Continue to checkout
                        </Button>
                        <button
                            type="button"
                            onClick={onContinue}
                            className="text-muted-foreground hover:text-foreground text-xs"
                        >
                            No thanks, checkout
                        </button>
                    </div>
                </div>
            </SheetContent>
        </Sheet>
    );
}
