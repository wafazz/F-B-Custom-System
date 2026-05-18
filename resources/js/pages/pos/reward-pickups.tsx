import { Head } from '@inertiajs/react';
import { Check, CheckCircle2, Coffee, Gift, Package, Search } from 'lucide-react';
import { type FormEvent, useState } from 'react';
import { Button } from '@/components/ui/button';
import PosLayout from '@/layouts/pos-layout';
import { cn } from '@/lib/utils';

interface Pickup {
    id: number;
    pickup_code: string | null;
    points_spent: number;
    claimed_at: string;
    fulfilled_at: string | null;
    reward: {
        name: string;
        banner_image: string | null;
        kind: 'product' | 'merchandise';
        product_name: string | null;
    } | null;
    customer: { name: string; phone: string | null } | null;
}

interface Props {
    pending: Pickup[];
    fulfilled_today: Pickup[];
}

export default function PosRewardPickups({ pending, fulfilled_today }: Props) {
    const [pendingRows, setPendingRows] = useState<Pickup[]>(pending);
    const [fulfilledRows, setFulfilledRows] = useState<Pickup[]>(fulfilled_today);
    const [code, setCode] = useState('');
    const [lookupResult, setLookupResult] = useState<Pickup | null>(null);
    const [error, setError] = useState<string | null>(null);
    const [busy, setBusy] = useState(false);

    function getCsrf(): string {
        const v = document.cookie
            .split('; ')
            .find((c) => c.startsWith('XSRF-TOKEN='))
            ?.substring('XSRF-TOKEN='.length);
        return v ? decodeURIComponent(v) : '';
    }

    async function handleLookup(e: FormEvent) {
        e.preventDefault();
        setError(null);
        setLookupResult(null);
        const trimmed = code.trim();
        if (!trimmed) return;
        setBusy(true);
        try {
            const res = await fetch(
                `/pos/reward-pickups/lookup?code=${encodeURIComponent(trimmed)}`,
                { credentials: 'same-origin' },
            );
            if (!res.ok) {
                const data = await res.json().catch(() => ({}));
                setError((data as { message?: string }).message ?? `HTTP ${res.status}`);
                return;
            }
            setLookupResult((await res.json()) as Pickup);
        } finally {
            setBusy(false);
        }
    }

    async function fulfilById(id: number): Promise<Pickup | null> {
        const res = await fetch(`/pos/reward-pickups/${id}/fulfil`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-XSRF-TOKEN': getCsrf(),
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        if (!res.ok) {
            const data = await res.json().catch(() => ({}));
            setError((data as { message?: string }).message ?? `HTTP ${res.status}`);
            return null;
        }
        return (await res.json()) as Pickup;
    }

    async function handleFulfil(p: Pickup) {
        setError(null);
        setBusy(true);
        const updated = await fulfilById(p.id);
        setBusy(false);
        if (updated === null) return;
        setPendingRows((rows) => rows.filter((r) => r.id !== p.id));
        setFulfilledRows((rows) => [updated, ...rows]);
        if (lookupResult?.id === p.id) setLookupResult(updated);
    }

    return (
        <PosLayout>
            <Head title="Reward pickups" />

            <div className="grid gap-4 lg:grid-cols-[1fr_400px]">
                {/* Pending list */}
                <div>
                    <div className="mb-3 flex items-baseline justify-between">
                        <h1 className="text-xl font-bold flex items-center gap-2">
                            <Gift className="text-amber-400 size-5" /> Reward pickups
                        </h1>
                        <span className="text-neutral-400 text-xs">
                            {pendingRows.length} pending
                        </span>
                    </div>

                    {pendingRows.length === 0 ? (
                        <div className="rounded-2xl border border-neutral-800 bg-neutral-900/40 p-10 text-center text-sm text-neutral-400">
                            No pickups waiting. ☕
                        </div>
                    ) : (
                        <ul className="space-y-2">
                            {pendingRows.map((p) => (
                                <PickupRow key={p.id} pickup={p} onFulfil={() => handleFulfil(p)} busy={busy} />
                            ))}
                        </ul>
                    )}

                    {fulfilledRows.length > 0 && (
                        <section className="mt-6">
                            <h2 className="text-neutral-400 mb-2 text-xs font-semibold uppercase tracking-wider">
                                Fulfilled today
                            </h2>
                            <ul className="space-y-1.5">
                                {fulfilledRows.map((p) => (
                                    <li
                                        key={p.id}
                                        className="rounded-lg border border-neutral-800 bg-neutral-900/40 px-3 py-2 text-xs text-neutral-300 flex items-center gap-2"
                                    >
                                        <CheckCircle2 className="text-emerald-400 size-3.5 shrink-0" />
                                        <span className="font-mono font-bold text-amber-300">
                                            {p.pickup_code}
                                        </span>
                                        <span className="text-neutral-400">·</span>
                                        <span className="truncate">{p.reward?.name}</span>
                                        <span className="text-neutral-500 ml-auto text-[10px]">
                                            {p.customer?.name}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        </section>
                    )}
                </div>

                {/* Lookup panel */}
                <aside className="rounded-2xl border border-neutral-800 bg-neutral-900/60 p-4">
                    <h2 className="mb-2 text-sm font-bold text-amber-200">Look up code</h2>
                    <p className="text-neutral-400 mb-3 text-xs">
                        Customer reads out their pickup code (starts with R-). Search to
                        confirm + fulfil instantly.
                    </p>
                    <form onSubmit={handleLookup} className="flex gap-2">
                        <div className="flex flex-1 items-center gap-2 rounded-lg border border-neutral-700 bg-neutral-950 px-3 py-2">
                            <Search className="text-neutral-500 size-4" />
                            <input
                                type="text"
                                value={code}
                                onChange={(e) => setCode(e.target.value.toUpperCase())}
                                placeholder="R-ABC123"
                                autoFocus
                                className="flex-1 bg-transparent text-sm font-mono uppercase tracking-wider text-neutral-100 outline-none placeholder:text-neutral-600"
                            />
                        </div>
                        <Button type="submit" disabled={busy || !code.trim()}>
                            {busy ? '…' : 'Find'}
                        </Button>
                    </form>

                    {error && (
                        <p className="mt-3 rounded-md border border-red-900/60 bg-red-950/40 px-3 py-2 text-xs text-red-300">
                            {error}
                        </p>
                    )}

                    {lookupResult && (
                        <div className="mt-4">
                            <PickupRow
                                pickup={lookupResult}
                                onFulfil={() => handleFulfil(lookupResult)}
                                busy={busy}
                                emphasis
                            />
                        </div>
                    )}
                </aside>
            </div>
        </PosLayout>
    );
}

function PickupRow({
    pickup,
    onFulfil,
    busy,
    emphasis = false,
}: {
    pickup: Pickup;
    onFulfil: () => void;
    busy: boolean;
    emphasis?: boolean;
}) {
    const fulfilled = pickup.fulfilled_at !== null;
    return (
        <li
            className={cn(
                'rounded-2xl border p-3',
                fulfilled
                    ? 'border-emerald-800/60 bg-emerald-950/30'
                    : emphasis
                      ? 'border-amber-500 bg-amber-950/30 shadow-lg shadow-amber-900/30'
                      : 'border-neutral-800 bg-neutral-900/60',
            )}
        >
            <div className="flex items-center gap-3">
                {pickup.reward?.banner_image ? (
                    <img
                        src={`/storage/${pickup.reward.banner_image}`}
                        alt=""
                        className="size-14 shrink-0 rounded-lg object-cover"
                    />
                ) : (
                    <div className="bg-neutral-800 text-amber-300 flex size-14 shrink-0 items-center justify-center rounded-lg">
                        {pickup.reward?.kind === 'product' ? (
                            <Coffee className="size-6" />
                        ) : (
                            <Package className="size-6" />
                        )}
                    </div>
                )}
                <div className="min-w-0 flex-1">
                    <p className="text-amber-300 font-mono text-lg font-extrabold tracking-wider leading-none">
                        {pickup.pickup_code}
                    </p>
                    <p className="mt-1 truncate text-sm font-semibold text-neutral-100">
                        {pickup.reward?.name}
                        {pickup.reward?.kind === 'product' && pickup.reward.product_name && (
                            <span className="text-neutral-400 font-normal">
                                {' '}
                                · {pickup.reward.product_name}
                            </span>
                        )}
                    </p>
                    <p className="text-neutral-400 text-[11px]">
                        {pickup.customer?.name}
                        {pickup.customer?.phone && ` · ${pickup.customer.phone}`}
                        {' · '}
                        {pickup.points_spent.toLocaleString()} pts
                    </p>
                </div>
                {fulfilled ? (
                    <span className="flex shrink-0 items-center gap-1 rounded-full bg-emerald-500/15 px-3 py-1.5 text-xs font-bold text-emerald-300">
                        <Check className="size-3.5" /> Fulfilled
                    </span>
                ) : (
                    <Button
                        onClick={onFulfil}
                        disabled={busy}
                        className="shrink-0 bg-amber-500 text-black hover:bg-amber-400"
                    >
                        Fulfil
                    </Button>
                )}
            </div>
        </li>
    );
}
