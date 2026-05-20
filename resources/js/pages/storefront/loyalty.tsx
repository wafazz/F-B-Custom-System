import { Head, Link } from '@inertiajs/react';
import { Award, Check, Gift, Heart, Sparkles, Ticket, Trophy } from 'lucide-react';
import { PushToggle } from '@/components/storefront/push-toggle';
import StorefrontLayout from '@/layouts/storefront-layout';
import { cn } from '@/lib/utils';

interface PointRow {
    id: number;
    type: 'earn' | 'redeem' | 'adjustment' | 'expire' | 'refund';
    points: number;
    balance_after: number;
    reason: string | null;
    created_at: string | null;
}

interface MembershipTierCard {
    id: number;
    name: string;
    min_lifetime_spend: number;
    earn_multiplier: number;
    color: string | null;
    badge_image: string | null;
    perks: string[];
}

interface Props {
    balance: number;
    redeem_value: number;
    lifetime_spend: number;
    current_tier: { name: string; multiplier: number; color: string | null } | null;
    next_tier: { name: string; min_spend: number; multiplier: number } | null;
    history: PointRow[];
    membership_tiers: MembershipTierCard[];
}

export default function Loyalty({
    balance,
    redeem_value,
    lifetime_spend,
    current_tier,
    next_tier,
    history,
    membership_tiers,
}: Props) {
    const progress = next_tier ? Math.min(100, (lifetime_spend / next_tier.min_spend) * 100) : 100;
    const currentTierName = current_tier?.name ?? null;

    return (
        <StorefrontLayout>
            <Head title="Your Rewards" />

            {membership_tiers.length > 0 && (
                <section className="mb-5">
                    <div className="mb-3 flex items-center justify-between">
                        <h2 className="text-card-foreground flex items-center gap-1.5 text-lg font-bold">
                            <Award className="size-4 text-amber-600" /> Membership Tiers
                        </h2>
                    </div>

                    <div className="-mx-1 flex snap-x snap-mandatory gap-3 overflow-x-auto px-1 pb-2">
                        {membership_tiers.map((tier) => {
                            const isCurrent = currentTierName === tier.name;
                            const accent = tier.color ?? '#7c4a1e';
                            const upperBound = (() => {
                                const next = membership_tiers.find(
                                    (t) => t.min_lifetime_spend > tier.min_lifetime_spend,
                                );
                                return next ? next.min_lifetime_spend - 1 : null;
                            })();
                            const rangeLabel = upperBound !== null
                                ? `${Math.round(tier.min_lifetime_spend)} - ${Math.round(upperBound)}`
                                : `${Math.round(tier.min_lifetime_spend)}+`;

                            return (
                                <div
                                    key={tier.id}
                                    className={cn(
                                        'bg-card relative flex w-44 shrink-0 snap-start flex-col items-center overflow-hidden rounded-2xl border p-4 shadow-sm',
                                        isCurrent
                                            ? 'border-amber-400 ring-2 ring-amber-300'
                                            : 'border-border',
                                    )}
                                    style={{
                                        background: isCurrent
                                            ? `linear-gradient(180deg, ${accent}1a 0%, transparent 60%)`
                                            : undefined,
                                    }}
                                >
                                    <div
                                        className="relative flex size-16 items-center justify-center rounded-full shadow-inner"
                                        style={{
                                            background: `radial-gradient(circle at 30% 30%, #ffffffaa, ${accent} 70%)`,
                                        }}
                                    >
                                        {tier.badge_image ? (
                                            <img
                                                src={`/storage/${tier.badge_image}`}
                                                alt={tier.name}
                                                className="size-12 rounded-full object-cover"
                                            />
                                        ) : (
                                            <Award className="size-7 text-white drop-shadow" />
                                        )}
                                    </div>

                                    <p
                                        className="mt-2 text-sm font-extrabold tracking-wider uppercase"
                                        style={{ color: accent }}
                                    >
                                        {tier.name}
                                    </p>
                                    <p className="text-muted-foreground text-[10px]">
                                        RM {rangeLabel}
                                    </p>

                                    <ul className="mt-3 w-full space-y-1.5 text-[10.5px] leading-snug">
                                        <li className="flex items-start gap-1.5">
                                            <Check
                                                className="mt-0.5 size-3 shrink-0"
                                                style={{ color: accent }}
                                            />
                                            <span>
                                                Earn{' '}
                                                <strong>
                                                    {tier.earn_multiplier % 1 === 0
                                                        ? tier.earn_multiplier.toFixed(0)
                                                        : tier.earn_multiplier
                                                              .toFixed(2)
                                                              .replace(/0$/, '')}
                                                </strong>{' '}
                                                {tier.earn_multiplier === 1 ? 'point' : 'points'} for every RM1 spent
                                            </span>
                                        </li>
                                        {tier.perks.map((perk, i) => (
                                            <li key={i} className="flex items-start gap-1.5">
                                                <Check
                                                    className="mt-0.5 size-3 shrink-0"
                                                    style={{ color: accent }}
                                                />
                                                <span>{perk}</span>
                                            </li>
                                        ))}
                                    </ul>

                                    {isCurrent && (
                                        <span className="mt-3 inline-flex items-center gap-1 rounded-full bg-amber-500 px-2 py-0.5 text-[9px] font-bold tracking-wider text-white uppercase shadow">
                                            Current Tier
                                        </span>
                                    )}
                                </div>
                            );
                        })}
                    </div>
                </section>
            )}

            <div className="mb-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between sm:gap-2">
                <div className="flex items-center justify-between gap-2 sm:justify-start">
                    <h1 className="text-xl font-bold">Your Rewards</h1>
                    <div className="sm:hidden">
                        <PushToggle />
                    </div>
                </div>
                <div className="-mx-1 flex items-center gap-2 overflow-x-auto px-1 pb-1 sm:flex-wrap sm:overflow-visible sm:pb-0">
                    <div className="hidden sm:inline-flex">
                        <PushToggle />
                    </div>
                    <Link
                        href="/spin"
                        className="flex shrink-0 items-center gap-1.5 rounded-full bg-fuchsia-500/10 px-3 py-1.5 text-xs font-semibold text-fuchsia-600 hover:bg-fuchsia-500/20"
                    >
                        <Sparkles className="size-3.5" /> Spin
                    </Link>
                    <Link
                        href="/rewards"
                        className="flex shrink-0 items-center gap-1.5 rounded-full bg-amber-500/10 px-3 py-1.5 text-xs font-semibold text-amber-700 hover:bg-amber-500/20"
                    >
                        <Gift className="size-3.5" /> Claim pts
                    </Link>
                    <Link
                        href="/favourites"
                        className="flex shrink-0 items-center gap-1.5 rounded-full bg-rose-500/10 px-3 py-1.5 text-xs font-semibold text-rose-600 hover:bg-rose-500/20"
                    >
                        <Heart className="size-3.5" /> Favourites
                    </Link>
                    <Link
                        href="/vouchers"
                        className="bg-primary/10 text-primary hover:bg-primary/20 flex shrink-0 items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-semibold"
                    >
                        <Ticket className="size-3.5" /> Vouchers
                    </Link>
                </div>
            </div>

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
