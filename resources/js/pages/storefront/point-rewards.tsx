import { Head, router, usePage } from '@inertiajs/react';
import { Gift, Sparkles, TimerReset } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import StorefrontLayout from '@/layouts/storefront-layout';
import type { Flash } from '@/types';

interface PointReward {
    id: number;
    name: string;
    description: string | null;
    banner_image: string | null;
    points: number;
    max_claims_per_user: number;
    user_claims: number;
    valid_until: string | null;
}

interface Props {
    rewards: PointReward[];
    points_balance: number;
}

export default function PointRewards({ rewards, points_balance }: Props) {
    const flash = usePage<{ flash: Flash }>().props.flash;
    const [claiming, setClaiming] = useState<number | null>(null);

    function handleClaim(reward: PointReward) {
        setClaiming(reward.id);
        router.post(
            `/rewards/${reward.id}/claim`,
            {},
            { preserveScroll: true, onFinish: () => setClaiming(null) },
        );
    }

    return (
        <StorefrontLayout hideStats>
            <Head title="Reward Points" />

            <div className="mb-4 flex items-start justify-between gap-3">
                <div>
                    <h1 className="text-xl font-bold flex items-center gap-2">
                        <Gift className="text-amber-600 size-5" /> Claim points
                    </h1>
                    <p className="text-muted-foreground text-xs">
                        Tap to grab bonus points and stack them toward rewards.
                    </p>
                </div>
                <div className="bg-amber-100 text-amber-900 flex shrink-0 flex-col items-end rounded-xl px-3 py-2 text-right">
                    <span className="text-[10px] uppercase tracking-wider font-semibold opacity-70">
                        Balance
                    </span>
                    <span className="text-lg font-extrabold leading-none">
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

            {rewards.length === 0 ? (
                <div className="border-border bg-card rounded-2xl border border-dashed py-12 text-center">
                    <Sparkles className="text-muted-foreground mx-auto mb-3 size-10" />
                    <p className="text-card-foreground text-sm font-semibold">
                        No point rewards right now
                    </p>
                    <p className="text-muted-foreground mt-1 text-xs">
                        Check back later — new rewards drop regularly.
                    </p>
                </div>
            ) : (
                <ul className="space-y-3">
                    {rewards.map((r) => {
                        const remaining = r.max_claims_per_user - r.user_claims;
                        const exhausted = remaining <= 0;
                        return (
                            <li
                                key={r.id}
                                className="border-border bg-card overflow-hidden rounded-2xl border shadow-sm"
                            >
                                {r.banner_image && (
                                    <div className="bg-amber-100 aspect-[16/9] w-full overflow-hidden">
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
                                            <p className="text-sm font-bold leading-tight">
                                                {r.name}
                                            </p>
                                            {r.description && (
                                                <p className="text-muted-foreground mt-1 whitespace-pre-line text-xs leading-snug">
                                                    {r.description}
                                                </p>
                                            )}
                                        </div>
                                        <span className="bg-amber-100 text-amber-700 flex shrink-0 items-center gap-1 rounded-full px-2.5 py-1 text-xs font-bold">
                                            +{r.points.toLocaleString()} pts
                                        </span>
                                    </div>
                                    <div className="text-muted-foreground mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px]">
                                        {r.max_claims_per_user > 1 && (
                                            <span>
                                                {r.user_claims}/{r.max_claims_per_user} claimed
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
                                        onClick={() => handleClaim(r)}
                                        disabled={claiming === r.id || exhausted}
                                        className="mt-3 w-full"
                                    >
                                        {claiming === r.id
                                            ? 'Claiming…'
                                            : exhausted
                                              ? 'Already claimed'
                                              : `Claim +${r.points.toLocaleString()} pts`}
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
