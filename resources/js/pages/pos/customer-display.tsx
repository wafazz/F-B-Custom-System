import { Head } from '@inertiajs/react';
import { Coffee, ShoppingBag, Store } from 'lucide-react';
import { useEffect, useState } from 'react';

interface CartLine {
    key: string;
    name: string;
    quantity: number;
    unit_price: number;
    modifier_labels: string[];
}

interface CartState {
    lines: CartLine[];
    order_type: 'pickup' | 'dine_in';
    table_number: string;
}

interface Props {
    branch: { id: number; code: string; name: string; sst_rate: number; sst_enabled: boolean };
}

const EMPTY: CartState = { lines: [], order_type: 'pickup', table_number: '' };

export default function CustomerDisplay({ branch }: Props) {
    const [cart, setCart] = useState<CartState>(EMPTY);

    useEffect(() => {
        if (typeof BroadcastChannel === 'undefined') return;
        const channel = new BroadcastChannel(`pos-cart-${branch.id}`);
        channel.onmessage = (e: MessageEvent<{ type: string; cart?: CartState }>) => {
            if (e.data?.type === 'cart:update' && e.data.cart) {
                setCart(e.data.cart);
            }
            if (e.data?.type === 'cart:clear') {
                setCart(EMPTY);
            }
        };
        channel.postMessage({ type: 'cart:request' });
        return () => channel.close();
    }, [branch.id]);

    const subtotal = cart.lines.reduce((sum, l) => sum + l.unit_price * l.quantity, 0);
    const sst = branch.sst_enabled ? subtotal * (branch.sst_rate / 100) : 0;
    const total = subtotal + sst;
    const itemCount = cart.lines.reduce((sum, l) => sum + l.quantity, 0);

    return (
        <div className="min-h-screen bg-gradient-to-br from-amber-50 via-orange-50 to-amber-100">
            <Head title="Customer Display" />

            <div className="mx-auto flex min-h-screen max-w-4xl flex-col px-6 py-8">
                <header className="mb-6 flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <img
                            src="/images/logo.jpg"
                            alt="Star Coffee"
                            className="size-12 rounded-full border-2 border-amber-700 object-cover"
                            onError={(e) => {
                                e.currentTarget.style.display = 'none';
                            }}
                        />
                        <div>
                            <h1 className="text-2xl font-bold text-amber-900">Star Coffee</h1>
                            <p className="text-sm text-amber-700">{branch.name}</p>
                        </div>
                    </div>
                    {cart.lines.length > 0 && (
                        <div className="flex items-center gap-2 rounded-full bg-amber-900 px-4 py-1.5 text-sm font-semibold text-amber-50">
                            {cart.order_type === 'dine_in' ? (
                                <>
                                    <Store className="size-4" />
                                    {cart.table_number
                                        ? `Table ${cart.table_number}`
                                        : 'Dine-in'}
                                </>
                            ) : (
                                <>
                                    <ShoppingBag className="size-4" /> Pickup
                                </>
                            )}
                        </div>
                    )}
                </header>

                <main className="flex flex-1 flex-col rounded-3xl border-2 border-amber-200 bg-white/70 p-6 shadow-xl backdrop-blur">
                    {cart.lines.length === 0 ? (
                        <div className="flex flex-1 flex-col items-center justify-center gap-4 text-center">
                            <div className="rounded-full bg-amber-100 p-8">
                                <Coffee className="size-20 text-amber-700" />
                            </div>
                            <h2 className="text-3xl font-bold text-amber-900">
                                Welcome to Star Coffee
                            </h2>
                            <p className="max-w-md text-base text-amber-700">
                                Your order will appear here as the cashier rings it up.
                            </p>
                        </div>
                    ) : (
                        <>
                            <div className="mb-4 flex items-center justify-between border-b-2 border-dashed border-amber-200 pb-3">
                                <h2 className="text-xl font-bold text-amber-900">Your Order</h2>
                                <span className="text-sm font-semibold text-amber-700">
                                    {itemCount} {itemCount === 1 ? 'item' : 'items'}
                                </span>
                            </div>

                            <ul className="flex-1 space-y-3 overflow-y-auto">
                                {cart.lines.map((l) => (
                                    <li
                                        key={l.key}
                                        className="flex items-start justify-between gap-4 rounded-xl border border-amber-100 bg-white px-5 py-3 shadow-sm"
                                    >
                                        <div className="flex flex-1 items-start gap-4">
                                            <span className="flex size-10 flex-shrink-0 items-center justify-center rounded-lg bg-amber-100 text-base font-bold text-amber-900">
                                                {l.quantity}×
                                            </span>
                                            <div className="flex-1">
                                                <p className="text-base font-semibold text-amber-950">
                                                    {l.name}
                                                </p>
                                                {l.modifier_labels.length > 0 && (
                                                    <p className="mt-0.5 text-xs text-amber-600">
                                                        {l.modifier_labels.join(' · ')}
                                                    </p>
                                                )}
                                            </div>
                                        </div>
                                        <span className="text-lg font-bold text-amber-900">
                                            RM{(l.unit_price * l.quantity).toFixed(2)}
                                        </span>
                                    </li>
                                ))}
                            </ul>

                            <div className="mt-4 space-y-1 border-t-2 border-dashed border-amber-200 pt-4">
                                <div className="flex justify-between text-sm text-amber-700">
                                    <span>Subtotal</span>
                                    <span>RM{subtotal.toFixed(2)}</span>
                                </div>
                                {branch.sst_enabled && (
                                    <div className="flex justify-between text-sm text-amber-700">
                                        <span>SST {branch.sst_rate.toFixed(0)}%</span>
                                        <span>RM{sst.toFixed(2)}</span>
                                    </div>
                                )}
                                <div className="mt-2 flex items-baseline justify-between border-t border-amber-300 pt-2">
                                    <span className="text-lg font-bold text-amber-900">Total</span>
                                    <span className="text-3xl font-bold text-amber-900">
                                        RM{total.toFixed(2)}
                                    </span>
                                </div>
                            </div>
                        </>
                    )}
                </main>

                <footer className="mt-4 text-center text-xs text-amber-700">
                    Thank you for visiting Star Coffee — please confirm the order with our cashier
                </footer>
            </div>
        </div>
    );
}
