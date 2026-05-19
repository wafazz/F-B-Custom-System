import { Head, router, usePage } from '@inertiajs/react';
import { Coffee, Gift, Package, Sparkles, TimerReset } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import StorefrontLayout from '@/layouts/storefront-layout';
import type { Flash } from '@/types';

interface CatalogReward {
    id: number;
    name: string;
    description: string | null;
    banner_image: string | null;
    points_cost: number;
    kind: 'product' | 'merchandise';
    product_name: string | null;
    max_claims_per_user: number;
    user_claims: number;
    stock: number | null;
    claimed_count: number;
    valid_until: string | null;
}

interface PendingClaim {
    id: number;
    pickup_code: string | null;
    points_spent: number;
    claimed_at: string;
    reward: {
        name: string;
        banner_image: string | null;
        kind: 'product' | 'merchandise';
    } | null;
}

interface Props {
    rewards: CatalogReward[];
    pending: PendingClaim[];
    points_balance: number;
}

export default function PointRewards({ rewards, pending, points_balance }: Props) {
    const flash = usePage<{ flash: Flash }>().props.flash;
    const [redeeming, setRedeeming] = useState<number | null>(null);

    function handleRedeem(reward: CatalogReward) {
        const confirmMsg = `Redeem "${reward.name}" for ${reward.points_cost.toLocaleString()} pts?`;
        if (!window.confirm(confirmMsg)) return;
        setRedeeming(reward.id);
        router.post(
            `/rewards/${reward.id}/redeem`,
            {},
            { preserveScroll: true, onFinish: () => setRedeeming(null) },
        );
    }

    return (
        <StorefrontLayout hideStats>
            <Head title="Reward Catalogue" />

            <div className="mb-4 flex items-start justify-between gap-3">
                <div>
                    <h1 className="flex items-center gap-2 text-xl font-bold">
                        <Gift className="size-5 text-amber-600" /> Reward catalogue
                    </h1>
                    <p className="text-muted-foreground text-xs">
                        Spend your loyalty points on free drinks, food &amp; merch.
                    </p>
                </div>
                <div className="flex shrink-0 flex-col items-end rounded-xl bg-amber-100 px-3 py-2 text-right text-amber-900">
                    <span className="text-[10px] font-semibold tracking-wider uppercase opacity-70">
                        Balance
                    </span>
                    <span className="text-lg leading-none font-extrabold">
                        {points_balance.toLocaleString()}
                    </span>
                    <span className="text-[10px] opacity-70">pts</span>
                </div>
            </div>

            {flash?.success && (
                <div className="mb-3 rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700">
                    {flash.success}
                </div>
            )}
            {flash?.error && (
                <div className="mb-3 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700">
                    {flash.error}
                </div>
            )}

            {pending.length > 0 && (
                <section className="mb-5">
                    <h2 className="mb-2 text-sm font-semibold">Show this at the counter</h2>
                    <ul className="space-y-2">
                        {pending.map((p) => (
                            <li
                                key={p.id}
                                className="flex items-center gap-3 rounded-xl border border-amber-300 bg-amber-50 p-3"
                            >
                                {p.reward?.banner_image ? (
                                    <img
                                        src={`/storage/${p.reward.banner_image}`}
                                        alt=""
                                        className="size-14 shrink-0 rounded-lg object-cover"
                                    />
                                ) : (
                                    <div className="flex size-14 shrink-0 items-center justify-center rounded-lg bg-amber-200 text-amber-700">
                                        {p.reward?.kind === 'product' ? (
                                            <Coffee className="size-6" />
                                        ) : (
                                            <Package className="size-6" />
                                        )}
                                    </div>
                                )}
                                <div className="min-w-0 flex-1">
                                    <p className="text-card-foreground text-sm font-bold">
                                        {p.reward?.name ?? 'Reward'}
                                    </p>
                                    <p className="mt-0.5 font-mono text-lg font-extrabold tracking-wider text-amber-700">
                                        {p.pickup_code}
                                    </p>
                                    <p className="text-muted-foreground text-[10px]">
                                        Redeemed {new Date(p.claimed_at).toLocaleDateString()} ·{' '}
                                        {p.points_spent.toLocaleString()} pts
                                    </p>
                                </div>
                            </li>
                        ))}
                    </ul>
                </section>
            )}

            {rewards.length === 0 ? (
                <div className="border-border bg-card rounded-2xl border border-dashed py-12 text-center">
                    <Sparkles className="text-muted-foreground mx-auto mb-3 size-10" />
                    <p className="text-card-foreground text-sm font-semibold">No rewards yet</p>
                    <p className="text-muted-foreground mt-1 text-xs">
                        Catalogue is empty — check back later.
                    </p>
                </div>
            ) : (
                <ul className="space-y-3">
                    {rewards.map((r) => {
                        const remaining = r.max_claims_per_user - r.user_claims;
                        const userExhausted = remaining <= 0;
                        const outOfStock = r.stock !== null && r.claimed_count >= r.stock;
                        const canAfford = points_balance >= r.points_cost;
                        const disabled =
                            redeeming === r.id || userExhausted || outOfStock || !canAfford;
                        let label = `Redeem · ${r.points_cost.toLocaleString()} pts`;
                        if (redeeming === r.id) label = 'Redeeming…';
                        else if (userExhausted) label = 'Already redeemed';
                        else if (outOfStock) label = 'Out of stock';
                        else if (!canAfford) {
                            const need = r.points_cost - points_balance;
                            label = `Need ${need.toLocaleString()} more pts`;
                        }
                        return (
                            <li
                                key={r.id}
                                className="border-border bg-card overflow-hidden rounded-2xl border shadow-sm"
                            >
                                {r.banner_image && (
                                    <div className="aspect-[16/9] w-full overflow-hidden bg-amber-100">
                                        <img
                                            src={`/storage/${r.banner_image}`}
                                            alt=""
                                            aria-hidden
                                            className="size-full object-cover"
                                        />
                                    </div>
                                )}
                                <div className="p-4">
                                    <div className="flex items-start justify-between gap-3">
                                        <div className="min-w-0 flex-1">
                                            <p className="text-sm leading-tight font-bold">
                                                {r.name}
                                            </p>
                                            <p className="text-muted-foreground mt-0.5 flex items-center gap-1 text-[11px]">
                                                {r.kind === 'product' ? (
                                                    <Coffee className="size-3" />
                                                ) : (
                                                    <Package className="size-3" />
                                                )}
                                                {r.kind === 'product'
                                                    ? (r.product_name ?? 'Menu item')
                                                    : 'Merchandise'}
                                            </p>
                                            {r.description && (
                                                <p className="text-muted-foreground mt-1.5 text-xs leading-snug whitespace-pre-line">
                                                    {r.description}
                                                </p>
                                            )}
                                        </div>
                                        <span className="flex shrink-0 items-center gap-1 rounded-full bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-700">
                                            {r.points_cost.toLocaleString()} pts
                                        </span>
                                    </div>
                                    <div className="text-muted-foreground mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px]">
                                        {r.max_claims_per_user > 1 && (
                                            <span>
                                                {r.user_claims}/{r.max_claims_per_user} redeemed
                                            </span>
                                        )}
                                        {r.stock !== null && (
                                            <span>
                                                {Math.max(0, r.stock - r.claimed_count)} left
                                            </span>
                                        )}
                                        {r.valid_until && (
                                            <span className="flex items-center gap-1">
                                                <TimerReset className="size-3" />
                                                Until {new Date(r.valid_until).toLocaleDateString()}
                                            </span>
                                        )}
                                    </div>
                                    <Button
                                        onClick={() => handleRedeem(r)}
                                        disabled={disabled}
                                        className="mt-3 w-full"
                                    >
                                        {label}
                                    </Button>
                                </div>
                            </li>
                        );
                    })}
                </ul>
            )}
        </StorefrontLayout>
    );
}
