import { router } from '@inertiajs/react';
import { Check, Coffee, Plus, Ticket } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Sheet, SheetContent, SheetTitle } from '@/components/ui/sheet';
import { useCartStore } from '@/stores/cart-store';
import type { Flash } from '@/types';
import type { MenuProduct } from '@/types/menu';

export interface UpsellProduct {
    id: number;
    name: string;
    image: string | null;
    price: number;
    normal_price: number;
    tumbler_discount: number;
}

export interface UpsellVoucher {
    id: number;
    code: string;
    name: string;
    discount_type: string;
    discount_value: number;
    min_subtotal: number;
    max_discount: number | null;
    points_cost: number | null;
    valid_until: string | null;
}

interface Props {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    title: string;
    products: UpsellProduct[];
    vouchers?: UpsellVoucher[];
    branchId: number;
    onContinue: () => void;
}

function discountLabel(v: UpsellVoucher): string {
    if (v.discount_type === 'percentage') return `${v.discount_value}% OFF`;
    if (v.discount_type === 'buy_x_get_y') return 'FREE ITEM';
    return `RM${v.discount_value.toFixed(2)} OFF`;
}

export function UpsellSheet({
    open,
    onOpenChange,
    title,
    products,
    vouchers = [],
    branchId,
    onContinue,
}: Props) {
    const add = useCartStore((s) => s.add);
    const [added, setAdded] = useState<number[]>([]);
    const [claimed, setClaimed] = useState<number[]>([]);
    const [claiming, setClaiming] = useState<number | null>(null);
    const [claimError, setClaimError] = useState<string | null>(null);

    const handleClaim = (v: UpsellVoucher) => {
        setClaiming(v.id);
        setClaimError(null);
        router.post(
            `/vouchers/${v.id}/claim`,
            {},
            {
                // Keep the sheet open and the upsell props untouched — only the
                // flash message needs to come back from the claim.
                preserveScroll: true,
                preserveState: true,
                only: ['flash'],
                onSuccess: (page) => {
                    const flash = page.props.flash as Flash | undefined;
                    if (flash?.error) {
                        setClaimError(flash.error);
                        return;
                    }
                    setClaimed((prev) => (prev.includes(v.id) ? prev : [...prev, v.id]));
                },
                onError: () => setClaimError('Could not claim that voucher. Please try again.'),
                onFinish: () => setClaiming(null),
            },
        );
    };

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
            true,
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

                    {vouchers.length > 0 && (
                        <div className="space-y-2">
                            <h4 className="text-muted-foreground text-xs font-semibold tracking-wide uppercase">
                                Your vouchers
                            </h4>
                            {vouchers.map((v) => {
                                const isClaimed = claimed.includes(v.id);
                                return (
                                    <div
                                        key={v.id}
                                        className="border-primary/30 bg-primary/5 flex items-center gap-3 rounded-xl border border-dashed p-2.5"
                                    >
                                        <div className="bg-primary/10 text-primary flex size-10 flex-shrink-0 items-center justify-center rounded-lg">
                                            <Ticket className="size-5" />
                                        </div>
                                        <div className="flex min-w-0 flex-1 flex-col">
                                            <span className="text-primary text-sm font-bold">
                                                {discountLabel(v)}
                                            </span>
                                            <span className="text-muted-foreground truncate text-xs">
                                                {v.name}
                                                {v.min_subtotal > 0 &&
                                                    ` · min RM${v.min_subtotal.toFixed(2)}`}
                                            </span>
                                        </div>
                                        <Button
                                            type="button"
                                            size="sm"
                                            variant={isClaimed ? 'secondary' : 'default'}
                                            disabled={isClaimed || claiming === v.id}
                                            onClick={() => handleClaim(v)}
                                            className="flex-shrink-0"
                                        >
                                            {isClaimed ? (
                                                <>
                                                    <Check className="mr-1 size-4" /> Claimed
                                                </>
                                            ) : v.points_cost ? (
                                                `Claim · ${v.points_cost} pts`
                                            ) : (
                                                'Claim'
                                            )}
                                        </Button>
                                    </div>
                                );
                            })}
                            {claimed.length > 0 && (
                                <p className="text-muted-foreground text-xs">
                                    Apply it on the checkout page.
                                </p>
                            )}
                            {claimError && (
                                <p className="text-destructive text-xs">{claimError}</p>
                            )}
                        </div>
                    )}

                    {products.length > 0 && vouchers.length > 0 && (
                        <h4 className="text-muted-foreground -mb-2 text-xs font-semibold tracking-wide uppercase">
                            Add-ons
                        </h4>
                    )}

                    <ul
                        className={`space-y-2 overflow-y-auto ${
                            vouchers.length > 0 ? 'max-h-[32vh]' : 'max-h-[50vh]'
                        }`}
                    >
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
                                        <span className="flex items-baseline gap-1.5">
                                            <span className="text-primary text-sm font-bold">
                                                RM{p.price.toFixed(2)}
                                            </span>
                                            {p.normal_price > p.price && (
                                                <span className="text-muted-foreground text-xs line-through">
                                                    RM{p.normal_price.toFixed(2)}
                                                </span>
                                            )}
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
