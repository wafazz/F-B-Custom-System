import { Coffee, Minus, Plus, Sparkles } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Sheet, SheetContent, SheetTitle } from '@/components/ui/sheet';
import type { MenuCombo } from '@/types/menu';

interface Props {
    combo: MenuCombo | null;
    open: boolean;
    onOpenChange: (open: boolean) => void;
    onAdd: (combo: MenuCombo, quantity: number) => void;
    onBuyNow?: (combo: MenuCombo, quantity: number) => void;
}

export function ComboSheet({ combo, open, onOpenChange, onAdd, onBuyNow }: Props) {
    return (
        <Sheet open={open} onOpenChange={onOpenChange}>
            <SheetContent
                side="bottom"
                className="bg-white text-neutral-900 dark:bg-neutral-950 dark:text-neutral-50 sm:mx-auto sm:max-w-3xl sm:rounded-xl"
            >
                {combo && (
                    <ComboBody
                        key={combo.id}
                        combo={combo}
                        onAdd={onAdd}
                        onBuyNow={onBuyNow}
                        onClose={() => onOpenChange(false)}
                    />
                )}
            </SheetContent>
        </Sheet>
    );
}

function ComboBody({
    combo,
    onAdd,
    onBuyNow,
    onClose,
}: {
    combo: MenuCombo;
    onAdd: (combo: MenuCombo, quantity: number) => void;
    onBuyNow?: (combo: MenuCombo, quantity: number) => void;
    onClose: () => void;
}) {
    const [quantity, setQuantity] = useState(1);
    const total = Number(combo.price) * quantity;

    function handleAdd() {
        onAdd(combo, quantity);
        onClose();
    }
    function handleBuyNow() {
        if (!onBuyNow) return;
        onBuyNow(combo, quantity);
        onClose();
    }

    return (
        <>
            <SheetTitle className="sr-only">{combo.name}</SheetTitle>
            <div className="flex min-h-0 flex-1 flex-col gap-4 sm:flex-row sm:gap-6">
                <aside className="flex-shrink-0 sm:w-2/5">
                    <div className="bg-secondary/50 flex aspect-[4/3] w-full items-center justify-center overflow-hidden rounded-xl">
                        {combo.image ? (
                            <img
                                src={`/storage/${combo.image}`}
                                alt={combo.name}
                                className="size-full object-cover"
                            />
                        ) : (
                            <Coffee className="text-muted-foreground size-16" />
                        )}
                    </div>
                    <h3 className="mt-3 flex items-center gap-1.5 text-lg font-bold leading-tight">
                        <Sparkles className="size-4 text-amber-500" />
                        {combo.name}
                    </h3>
                    {combo.description && (
                        <p className="text-muted-foreground mt-1 text-xs leading-snug">
                            {combo.description}
                        </p>
                    )}
                    <p className="text-primary mt-2 text-xl font-bold">
                        RM{Number(combo.price).toFixed(2)}
                    </p>
                </aside>

                <div className="flex min-h-0 flex-1 flex-col">
                    <div className="flex-1 overflow-y-auto pr-1">
                        <h4 className="mb-2 text-sm font-semibold">What's inside</h4>
                        <ul className="space-y-2">
                            {combo.items.map((item) => (
                                <li
                                    key={item.product_id}
                                    className="border-border bg-secondary/30 flex items-center gap-3 rounded-lg border p-2"
                                >
                                    <div className="bg-secondary/60 flex size-12 shrink-0 items-center justify-center overflow-hidden rounded-md">
                                        {item.image ? (
                                            <img
                                                src={`/storage/${item.image}`}
                                                alt={item.name}
                                                className="size-full object-cover"
                                            />
                                        ) : (
                                            <Coffee className="text-muted-foreground size-5" />
                                        )}
                                    </div>
                                    <div className="flex-1">
                                        <p className="text-sm font-medium leading-tight">
                                            {item.name}
                                        </p>
                                        <p className="text-muted-foreground text-[11px]">
                                            ×{item.quantity}
                                        </p>
                                    </div>
                                </li>
                            ))}
                        </ul>
                    </div>

                    <div className="mt-3 border-t pt-3">
                        <div className="mb-3 flex items-center justify-between">
                            <span className="text-sm font-medium">Quantity</span>
                            <div className="flex items-center gap-3">
                                <button
                                    type="button"
                                    onClick={() => setQuantity((q) => Math.max(1, q - 1))}
                                    className="flex size-8 items-center justify-center rounded-full bg-black text-white hover:bg-black/80"
                                    aria-label="Decrease"
                                >
                                    <Minus className="size-3.5" />
                                </button>
                                <span className="w-6 text-center text-sm font-semibold">
                                    {quantity}
                                </span>
                                <button
                                    type="button"
                                    onClick={() => setQuantity((q) => q + 1)}
                                    className="flex size-8 items-center justify-center rounded-full bg-black text-white hover:bg-black/80"
                                    aria-label="Increase"
                                >
                                    <Plus className="size-3.5" />
                                </button>
                            </div>
                        </div>
                        <div className="flex gap-2">
                            {onBuyNow && (
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={handleBuyNow}
                                    className="flex-1"
                                >
                                    Buy now
                                </Button>
                            )}
                            <Button
                                onClick={handleAdd}
                                className={onBuyNow ? 'flex-1' : 'w-full'}
                            >
                                Add combo — RM{total.toFixed(2)}
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
