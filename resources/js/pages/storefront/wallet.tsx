import { Head, useForm } from '@inertiajs/react';
import { ArrowDownCircle, ArrowUpCircle, RefreshCcw, Wallet as WalletIcon } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import StorefrontLayout from '@/layouts/storefront-layout';
import { cn } from '@/lib/utils';

interface Row {
    id: number;
    type: 'topup' | 'spend' | 'refund' | 'adjustment';
    amount: number;
    balance_after: number;
    description: string | null;
    created_at: string | null;
}

interface Props {
    balance: number;
    history: Row[];
    topup_amounts: number[];
    pending_topups: number;
}

export default function Wallet({ balance, history, topup_amounts, pending_topups }: Props) {
    const [picked, setPicked] = useState<number>(topup_amounts[1] ?? 20);
    const [custom, setCustom] = useState('');
    const form = useForm({ amount: picked });

    function selectPreset(value: number) {
        setPicked(value);
        setCustom('');
        form.setData('amount', value);
    }

    function setCustomAmount(value: string) {
        setCustom(value);
        const n = Number(value);
        if (!Number.isNaN(n) && n > 0) form.setData('amount', n);
    }

    function submit(e: React.FormEvent) {
        e.preventDefault();
        form.post('/wallet/topup', { preserveScroll: true });
    }

    return (
        <StorefrontLayout>
            <Head title="Wallet" />

            <h1 className="mb-3 text-xl font-bold">Wallet</h1>

            <section className="mb-4 rounded-2xl border border-amber-200 bg-gradient-to-br from-amber-100 to-orange-200 p-5 shadow-sm">
                <div className="flex items-center gap-3">
                    <div className="flex size-12 items-center justify-center rounded-full bg-amber-700 text-white">
                        <WalletIcon className="size-6" />
                    </div>
                    <div>
                        <p className="text-xs text-amber-900/70">Available balance</p>
                        <p className="text-3xl font-bold tabular-nums text-amber-900">RM{balance.toFixed(2)}</p>
                    </div>
                </div>
                {pending_topups > 0 && (
                    <p className="mt-3 flex items-center gap-1.5 text-xs text-amber-900/80">
                        <RefreshCcw className="size-3" /> {pending_topups} top-up{pending_topups > 1 ? 's' : ''} pending payment
                    </p>
                )}
            </section>

            <form onSubmit={submit} className="border-border bg-card mb-4 rounded-xl border p-4 shadow-sm">
                <h2 className="mb-3 text-sm font-semibold">Top up</h2>
                <div className="mb-3 grid grid-cols-5 gap-2">
                    {topup_amounts.map((v) => (
                        <button
                            key={v}
                            type="button"
                            onClick={() => selectPreset(v)}
                            className={cn(
                                'rounded-lg border px-2 py-2 text-xs font-semibold transition-colors',
                                picked === v && !custom
                                    ? 'border-primary bg-primary/10 text-primary'
                                    : 'border-border hover:bg-secondary/50',
                            )}
                        >
                            RM{v}
                        </button>
                    ))}
                </div>
                <div className="mb-3 space-y-1">
                    <label className="text-xs text-muted-foreground">Or enter custom amount (RM 5 – 1000)</label>
                    <input
                        type="number"
                        step="0.01"
                        min={5}
                        max={1000}
                        value={custom}
                        onChange={(e) => setCustomAmount(e.target.value)}
                        placeholder="0.00"
                        className="border-border bg-background w-full rounded-md border px-3 py-2 text-sm"
                    />
                    {form.errors.amount && <p className="text-xs text-red-600">{form.errors.amount}</p>}
                </div>
                <Button type="submit" disabled={form.processing} className="w-full">
                    {form.processing ? 'Redirecting…' : `Top up RM${(custom || picked).toString()} via Billplz`}
                </Button>
            </form>

            <h2 className="mb-2 text-sm font-semibold">Recent activity</h2>
            {history.length === 0 ? (
                <div className="border-border bg-card text-muted-foreground rounded-xl border border-dashed p-6 text-center text-sm">
                    No wallet activity yet.
                </div>
            ) : (
                <ul className="space-y-1.5 text-xs">
                    {history.map((row) => {
                        const credit = row.amount > 0;
                        return (
                            <li
                                key={row.id}
                                className="border-border bg-card flex items-center justify-between gap-2 rounded-lg border p-3"
                            >
                                <div className="flex items-center gap-2">
                                    {credit ? (
                                        <ArrowDownCircle className="size-4 text-emerald-600" />
                                    ) : (
                                        <ArrowUpCircle className="size-4 text-red-500" />
                                    )}
                                    <div>
                                        <p className="font-semibold capitalize">{row.type}</p>
                                        <p className="text-muted-foreground text-[10px]">
                                            {row.description ?? '—'}
                                        </p>
                                    </div>
                                </div>
                                <div className="text-right">
                                    <p className={`font-bold ${credit ? 'text-emerald-600' : 'text-red-600'}`}>
                                        {credit ? '+' : ''}
                                        RM{Math.abs(row.amount).toFixed(2)}
                                    </p>
                                    <p className="text-muted-foreground text-[10px] tabular-nums">
                                        RM{row.balance_after.toFixed(2)}
                                    </p>
                                </div>
                            </li>
                        );
                    })}
                </ul>
            )}
        </StorefrontLayout>
    );
}
