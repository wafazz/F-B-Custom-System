import { Head } from '@inertiajs/react';
import { Trophy } from 'lucide-react';
import StorefrontLayout from '@/layouts/storefront-layout';

interface PointRow {
    id: number;
    type: 'earn' | 'redeem' | 'adjustment' | 'expire' | 'refund';
    points: number;
    balance_after: number;
    reason: string | null;
    created_at: string | null;
}

interface Props {
    balance: number;
    redeem_value: number;
    lifetime_spend: number;
    current_tier: { name: string; multiplier: number; color: string | null } | null;
    next_tier: { name: string; min_spend: number; multiplier: number } | null;
    history: PointRow[];
}

export default function Loyalty({
    balance,
    redeem_value,
    lifetime_spend,
    current_tier,
    next_tier,
    history,
}: Props) {
    const progress = next_tier ? Math.min(100, (lifetime_spend / next_tier.min_spend) * 100) : 100;

    return (
        <StorefrontLayout>
            <Head title="Your Rewards" />

            <h1 className="mb-3 text-xl font-bold">Your Rewards</h1>

            <section className="border-border mb-4 rounded-2xl border bg-gradient-to-br from-amber-50 to-orange-100 p-5 shadow-sm">
                <div className="flex items-center justify-between gap-3">
                    <div>
                        <p className="text-muted-foreground text-xs">Balance</p>
                        <p className="text-3xl font-bold text-amber-700">
                            {balance.toLocaleString()} pts
                        </p>
                        <p className="text-muted-foreground text-xs">
                            ≈ RM{redeem_value.toFixed(2)} discount
                        </p>
                    </div>
                    {current_tier && (
                        <div
                            className="flex flex-col items-end rounded-xl bg-white/50 px-3 py-2 backdrop-blur"
                            style={{ borderLeft: `4px solid ${current_tier.color ?? '#7c4a1e'}` }}
                        >
                            <Trophy className="size-5 text-amber-600" />
                            <span className="text-xs font-semibold">{current_tier.name}</span>
                            <span className="text-muted-foreground text-[10px]">
                                {current_tier.multiplier}× earn
                            </span>
                        </div>
                    )}
                </div>

                {next_tier && (
                    <div className="mt-4">
                        <p className="text-muted-foreground text-xs">
                            RM{(next_tier.min_spend - lifetime_spend).toFixed(2)} to{' '}
                            <strong>{next_tier.name}</strong>
                        </p>
                        <div className="mt-2 h-2 overflow-hidden rounded-full bg-amber-200">
                            <div
                                className="h-full bg-amber-600"
                                style={{ width: `${progress}%` }}
                            />
                        </div>
                    </div>
                )}
            </section>

            <h2 className="mb-2 text-sm font-semibold">Recent activity</h2>
            {history.length === 0 ? (
                <div className="border-border bg-card text-muted-foreground rounded-xl border border-dashed p-6 text-center text-sm">
                    No activity yet. Earn 1 point per RM spent.
                </div>
            ) : (
                <ul className="space-y-1.5 text-xs">
                    {history.map((row) => (
                        <li
                            key={row.id}
                            className="border-border bg-card flex items-center justify-between gap-2 rounded-lg border p-3"
                        >
                            <div>
                                <p className="font-semibold capitalize">{row.type}</p>
                                <p className="text-muted-foreground text-[10px]">
                                    {row.reason ?? '—'}
                                </p>
                            </div>
                            <div className="text-right">
                                <p
                                    className={`font-bold ${row.points >= 0 ? 'text-emerald-600' : 'text-red-600'}`}
                                >
                                    {row.points >= 0 ? '+' : ''}
                                    {row.points}
                                </p>
                                <p className="text-muted-foreground text-[10px]">
                                    balance {row.balance_after}
                                </p>
                            </div>
                        </li>
                    ))}
                </ul>
            )}
        </StorefrontLayout>
    );
}
