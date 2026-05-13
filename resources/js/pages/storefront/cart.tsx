import { Head, Link, usePage } from '@inertiajs/react';
import { Coffee, LogIn, Minus, Plus, Trash2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import StorefrontLayout from '@/layouts/storefront-layout';
import { cartTotals, useCartStore } from '@/stores/cart-store';
import type { BranchContext } from '@/types/menu';

interface Props {
    branch: BranchContext;
}

export default function Cart({ branch }: Props) {
    const { auth } = usePage().props as unknown as { auth: { user: { id: number } | null } };
    const isAuthed = auth.user !== null;
    const lines = useCartStore((s) => s.lines);
    const notes = useCartStore((s) => s.notes);
    const setNotes = useCartStore((s) => s.setNotes);
    const increment = useCartStore((s) => s.increment);
    const decrement = useCartStore((s) => s.decrement);
    const remove = useCartStore((s) => s.remove);
    const cartBranchId = useCartStore((s) => s.branchId);

    const { itemCount, subtotal } = cartTotals(lines);
    const sst = branch.sst_enabled ? subtotal * (branch.sst_rate / 100) : 0;
    const serviceCharge = branch.service_charge_enabled
        ? subtotal * (branch.service_charge_rate / 100)
        : 0;
    const total = subtotal + sst + serviceCharge;
    const isMatch = cartBranchId === null || cartBranchId === branch.id;

    return (
        <StorefrontLayout>
            <Head title="Your Cart" />

            <div className="mb-4">
                <h1 className="text-xl font-bold">Your Cart</h1>
                <p className="text-muted-foreground text-sm">{branch.name}</p>
            </div>

            {!isMatch && (
                <div className="mb-3 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
                    Cart belongs to a different branch. Pick this branch to keep ordering or clear
                    cart first.
                </div>
            )}

            {lines.length === 0 ? (
                <div className="border-border bg-card text-muted-foreground flex flex-col items-center gap-3 rounded-xl border border-dashed p-8 text-center text-sm">
                    <Coffee className="size-10 opacity-40" />
                    <p>Your cart is empty.</p>
                    <Link
                        href={`/branches/${branch.id}/menu`}
                        className="text-primary text-xs underline"
                    >
                        Browse the menu
                    </Link>
                </div>
            ) : (
                <>
                    <ul className="space-y-2">
                        {lines.map((line) => (
                            <li
                                key={line.id}
                                className="border-border bg-card flex items-stretch gap-3 rounded-xl border p-3 shadow-sm"
                            >
                                <div className="bg-secondary flex size-16 flex-shrink-0 items-center justify-center overflow-hidden rounded-lg">
                                    {line.image ? (
                                        <img
                                            src={`/storage/${line.image}`}
                                            alt={line.name}
                                            className="size-full object-cover"
                                        />
                                    ) : (
                                        <Coffee className="text-muted-foreground size-6" />
                                    )}
                                </div>
                                <div className="flex flex-1 flex-col">
                                    <div className="flex items-start justify-between gap-2">
                                        <h3 className="text-sm font-semibold">{line.name}</h3>
                                        <button
                                            type="button"
                                            onClick={() => remove(line.id)}
                                            className="text-muted-foreground hover:text-red-600"
                                            aria-label="Remove"
                                        >
                                            <Trash2 className="size-4" />
                                        </button>
                                    </div>
                                    {line.modifiers.length > 0 && (
                                        <p className="text-muted-foreground mt-1 text-[10px]">
                                            {line.modifiers.map((m) => m.option_name).join(' · ')}
                                        </p>
                                    )}
                                    <div className="mt-auto flex items-center justify-between pt-2">
                                        <span className="text-primary text-sm font-semibold">
                                            RM{(line.unit_price * line.quantity).toFixed(2)}
                                        </span>
                                        <div className="flex items-center gap-2">
                                            <button
                                                type="button"
                                                onClick={() => decrement(line.id)}
                                                className="bg-secondary hover:bg-secondary/80 flex size-7 items-center justify-center rounded-full"
                                                aria-label="Decrease"
                                            >
                                                <Minus className="size-3" />
                                            </button>
                                            <span className="w-5 text-center text-xs font-semibold">
                                                {line.quantity}
                                            </span>
                                            <button
                                                type="button"
                                                onClick={() => increment(line.id)}
                                                className="bg-secondary hover:bg-secondary/80 flex size-7 items-center justify-center rounded-full"
                                                aria-label="Increase"
                                            >
                                                <Plus className="size-3" />
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        ))}
                    </ul>

                    <div className="border-border bg-card mt-4 rounded-xl border p-4 shadow-sm">
                        <label className="text-sm font-semibold">Order notes</label>
                        <textarea
                            value={notes}
                            onChange={(e) => setNotes(e.target.value)}
                            rows={2}
                            className="border-border bg-background mt-2 w-full rounded-md border px-3 py-2 text-sm"
                            placeholder="Allergies, special requests..."
                        />
                    </div>

                    <div className="border-border bg-card mt-4 space-y-2 rounded-xl border p-4 text-sm shadow-sm">
                        <div className="text-muted-foreground flex justify-between">
                            <span>Subtotal ({itemCount} items)</span>
                            <span>RM{subtotal.toFixed(2)}</span>
                        </div>
                        {branch.service_charge_enabled && (
                            <div className="text-muted-foreground flex justify-between">
                                <span>Service charge ({branch.service_charge_rate.toFixed(0)}%)</span>
                                <span>RM{serviceCharge.toFixed(2)}</span>
                            </div>
                        )}
                        {branch.sst_enabled && (
                            <div className="text-muted-foreground flex justify-between">
                                <span>SST ({branch.sst_rate.toFixed(0)}%)</span>
                                <span>RM{sst.toFixed(2)}</span>
                            </div>
                        )}
                        <div className="border-border flex justify-between border-t pt-2 font-semibold">
                            <span>Total</span>
                            <span className="text-primary">RM{total.toFixed(2)}</span>
                        </div>
                    </div>

                    {!isAuthed && (
                        <div className="mt-4 flex items-start gap-2 rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800">
                            <LogIn className="mt-0.5 size-3.5 flex-shrink-0" />
                            <span>
                                Sign in or create an account to checkout — we'll bring your cart
                                with you.
                            </span>
                        </div>
                    )}

                    {!branch.is_open_now && (
                        <div className="mt-4 flex items-start gap-2 rounded-lg border border-red-200 bg-red-50 p-3 text-xs text-red-800">
                            <span className="font-semibold">⏰ This branch is currently closed.</span>
                            <span>Online ordering will resume during operating hours.</span>
                        </div>
                    )}

                    <div className="mt-4">
                        {isAuthed ? (
                            <Link href={`/branches/${branch.id}/checkout`}>
                                <Button
                                    className="w-full"
                                    disabled={
                                        !isMatch ||
                                        !branch.accepts_orders ||
                                        !branch.is_open_now
                                    }
                                >
                                    {branch.is_open_now
                                        ? 'Continue to checkout'
                                        : 'Branch closed'}
                                </Button>
                            </Link>
                        ) : (
                            <Link href={`/login?redirect=/branches/${branch.id}/checkout`}>
                                <Button className="w-full" disabled={!branch.is_open_now}>
                                    <LogIn className="mr-1.5 size-4" />
                                    {branch.is_open_now ? 'Sign in to checkout' : 'Branch closed'}
                                </Button>
                            </Link>
                        )}
                    </div>
                </>
            )}
        </StorefrontLayout>
    );
}
