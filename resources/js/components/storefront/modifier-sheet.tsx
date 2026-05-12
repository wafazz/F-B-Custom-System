import { Coffee, Minus, Plus } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Sheet, SheetContent, SheetTitle } from '@/components/ui/sheet';
import type { MenuProduct, SelectedModifier } from '@/types/menu';
import { cn } from '@/lib/utils';

interface Props {
    product: MenuProduct | null;
    open: boolean;
    onOpenChange: (open: boolean) => void;
    onAdd: (product: MenuProduct, modifiers: SelectedModifier[], quantity: number) => void;
}

type Selection = Record<number, number[]>;

function defaultsFor(product: MenuProduct): Selection {
    const defaults: Selection = {};
    for (const group of product.modifier_groups) {
        const defaultIds = group.options.filter((o) => o.is_default).map((o) => o.id);
        if (defaultIds.length > 0 || group.is_required) {
            defaults[group.id] = defaultIds.slice(0, group.max_select);
        }
    }
    return defaults;
}

export function ModifierSheet({ product, open, onOpenChange, onAdd }: Props) {
    return (
        <Sheet open={open} onOpenChange={onOpenChange}>
            <SheetContent
                side="bottom"
                className="sm:mx-auto sm:max-w-3xl sm:rounded-xl"
            >
                {product && (
                    <SheetBody
                        key={product.id}
                        product={product}
                        onAdd={onAdd}
                        onClose={() => onOpenChange(false)}
                    />
                )}
            </SheetContent>
        </Sheet>
    );
}

function SheetBody({
    product,
    onAdd,
    onClose,
}: {
    product: MenuProduct;
    onAdd: (product: MenuProduct, modifiers: SelectedModifier[], quantity: number) => void;
    onClose: () => void;
}) {
    const [selection, setSelection] = useState<Selection>(() => defaultsFor(product));
    const [quantity, setQuantity] = useState(1);

    let validationMessage = '';
    let valid = true;
    for (const group of product.modifier_groups) {
        const picked = selection[group.id] ?? [];
        if (group.is_required && picked.length < group.min_select) {
            validationMessage = `${group.name}: pick at least ${group.min_select}`;
            valid = false;
            break;
        }
        if (picked.length > group.max_select) {
            validationMessage = `${group.name}: max ${group.max_select}`;
            valid = false;
            break;
        }
    }

    const modifierTotal = product.modifier_groups.reduce((sum, group) => {
        const picked = selection[group.id] ?? [];
        const groupSum = group.options
            .filter((o) => picked.includes(o.id))
            .reduce((acc, o) => acc + Number(o.price_delta), 0);
        return sum + groupSum;
    }, 0);
    const totalPrice = (Number(product.price) + modifierTotal) * quantity;

    function toggle(
        groupId: number,
        optionId: number,
        type: 'single' | 'multiple',
        maxSelect: number,
    ) {
        setSelection((prev) => {
            const current = prev[groupId] ?? [];
            if (type === 'single') {
                return { ...prev, [groupId]: [optionId] };
            }
            if (current.includes(optionId)) {
                return { ...prev, [groupId]: current.filter((id) => id !== optionId) };
            }
            if (current.length >= maxSelect) return prev;
            return { ...prev, [groupId]: [...current, optionId] };
        });
    }

    function handleAdd() {
        if (!valid) return;
        const modifiers: SelectedModifier[] = [];
        for (const group of product.modifier_groups) {
            const picked = selection[group.id] ?? [];
            for (const option of group.options.filter((o) => picked.includes(o.id))) {
                modifiers.push({
                    group_id: group.id,
                    group_name: group.name,
                    option_id: option.id,
                    option_name: option.name,
                    price_delta: Number(option.price_delta),
                });
            }
        }
        onAdd(product, modifiers, quantity);
        onClose();
    }

    const imageSrc =
        product.image ?? product.gallery?.[0] ?? null;

    return (
        <>
            <SheetTitle className="sr-only">{product.name}</SheetTitle>

            <div className="flex min-h-0 flex-1 flex-col gap-4 sm:flex-row sm:gap-6">
                <aside className="flex-shrink-0 sm:w-2/5">
                    <div className="bg-secondary/50 flex aspect-[4/3] w-full items-center justify-center overflow-hidden rounded-xl">
                        {imageSrc ? (
                            <img
                                src={imageSrc.startsWith('http') ? imageSrc : `/storage/${imageSrc}`}
                                alt={product.name}
                                className="size-full object-cover"
                            />
                        ) : (
                            <Coffee className="text-muted-foreground size-16" />
                        )}
                    </div>
                    <h3 className="mt-3 text-lg leading-tight font-bold">{product.name}</h3>
                    {product.description && (
                        <p className="text-muted-foreground mt-1 text-xs leading-snug">
                            {product.description}
                        </p>
                    )}
                    <p className="text-primary mt-2 text-xl font-bold">
                        RM{Number(product.price).toFixed(2)}
                    </p>
                </aside>

                <div className="flex min-h-0 flex-1 flex-col">
                    <div className="flex-1 space-y-5 overflow-y-auto pr-1">
                        {product.modifier_groups.map((group) => (
                            <div key={group.id}>
                                <div className="mb-2 flex items-center justify-between">
                                    <h4 className="text-sm font-semibold">
                                        {group.name}
                                        {group.is_required && (
                                            <span className="ml-1 text-red-500">*</span>
                                        )}
                                    </h4>
                                    <span className="text-muted-foreground text-[10px]">
                                        {group.selection_type === 'single'
                                            ? 'Pick one'
                                            : `${group.min_select}–${group.max_select} options`}
                                    </span>
                                </div>
                                <div className="grid grid-cols-2 gap-1.5">
                                    {group.options.map((option) => {
                                        const checked = (selection[group.id] ?? []).includes(
                                            option.id,
                                        );
                                        return (
                                            <button
                                                key={option.id}
                                                type="button"
                                                onClick={() =>
                                                    toggle(
                                                        group.id,
                                                        option.id,
                                                        group.selection_type,
                                                        group.max_select,
                                                    )
                                                }
                                                className={cn(
                                                    'flex w-full items-center justify-between rounded-lg border px-3 py-2 text-sm transition-colors',
                                                    checked
                                                        ? 'border-primary bg-primary/5 text-primary'
                                                        : 'border-border hover:bg-secondary/50',
                                                )}
                                            >
                                                <span>{option.name}</span>
                                                <span className="text-muted-foreground text-xs">
                                                    {Number(option.price_delta) > 0
                                                        ? `+RM${Number(option.price_delta).toFixed(2)}`
                                                        : '—'}
                                                </span>
                                            </button>
                                        );
                                    })}
                                </div>
                            </div>
                        ))}
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
                        {!valid && validationMessage && (
                            <p className="mb-2 text-xs text-red-600">{validationMessage}</p>
                        )}
                        <Button onClick={handleAdd} disabled={!valid} className="w-full">
                            Add to cart — RM{totalPrice.toFixed(2)}
                        </Button>
                    </div>
                </div>
            </div>
        </>
    );
}
