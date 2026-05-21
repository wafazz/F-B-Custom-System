import { Head, Link } from '@inertiajs/react';
import { ArrowRight, Award, Check, Copy, Gift, Share2, Trophy } from 'lucide-react';
import { useEffect, useState } from 'react';
import { PushToggle } from '@/components/storefront/push-toggle';
import StorefrontLayout from '@/layouts/storefront-layout';
import { useBranchStore } from '@/stores/branch-store';
import { cn } from '@/lib/utils';

function resolveSlideUrl(url: string, branchId: number | null): string {
    if (/^(https?:)?\/\//i.test(url)) return url;
    if (url.startsWith('/')) return url;
    return branchId ? `/branches/${branchId}/${url}` : '/branches';
}

interface Slide {
    type: 'cover' | 'product' | 'managed';
    image: string | null;
    title: string;
    subtitle: string | null;
    cta_label: string | null;
    cta_url: string | null;
}

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
    slides: Slide[];
    referral: { code: string; share_url: string; referrer_bonus: number };
    balance: number;
    redeem_value: number;
    lifetime_spend: number;
    current_tier: { name: string; multiplier: number; color: string | null } | null;
    next_tier: { name: string; min_spend: number; multiplier: number } | null;
    history: PointRow[];
    membership_tiers: MembershipTierCard[];
}

export default function Loyalty({
    slides,
    referral,
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
    const selectedBranch = useBranchStore((s) => s.selected);
    const [active, setActive] = useState(0);
    const [copied, setCopied] = useState(false);

    function copyReferral() {
        navigator.clipboard.writeText(referral.share_url).then(() => {
            setCopied(true);
            window.setTimeout(() => setCopied(false), 1500);
        });
    }

    function shareReferral() {
        const text = `Join me on Star Coffee — sign up with my code ${referral.code} and we both get bonus points!`;
        if (typeof navigator !== 'undefined' && 'share' in navigator) {
            navigator
                .share({ title: 'Star Coffee', text, url: referral.share_url })
                .catch(() => copyReferral());
        } else {
            copyReferral();
        }
    }

    useEffect(() => {
        if (slides.length <= 1) return;
        const t = window.setInterval(() => setActive((i) => (i + 1) % slides.length), 4500);
        return () => window.clearInterval(t);
    }, [slides.length]);

    const slide = slides[active] ?? slides[0];

    return (
        <StorefrontLayout>
            <Head title="Your Rewards" />

            {slide && (
                <section className="mb-5 overflow-hidden rounded-2xl shadow-md">
                    <div className="relative h-52 sm:h-60">
                        {slides.map((s, i) => (
                            <div
                                key={i}
                                className={cn(
                                    'absolute inset-0 transition-opacity duration-700',
                                    active === i ? 'opacity-100' : 'opacity-0',
                                )}
                                style={{
                                    backgroundImage: s.image
                                        ? `linear-gradient(110deg, rgba(20,15,12,0.85) 0%, rgba(20,15,12,0.55) 45%, rgba(20,15,12,0.1) 70%), url(/storage/${s.image})`
                                        : 'linear-gradient(135deg, #2a1d14, #4a2c18)',
                                    backgroundSize: 'cover',
                                    backgroundPosition: 'center',
                                }}
                            >
                                <div className="flex h-full flex-col justify-center p-6 text-white">
                                    <p className="text-2xl leading-none font-bold drop-shadow-lg sm:text-3xl">
                                        {s.title.split(' ')[0]}
                                    </p>
                                    <p className="-mt-1 font-serif text-3xl text-amber-100 italic drop-shadow-lg sm:text-4xl">
                                        {s.title.split(' ').slice(1).join(' ') || ''}
                                    </p>
                                    {s.subtitle && (
                                        <p className="mt-3 max-w-[55%] text-xs leading-snug text-amber-50/90 sm:text-sm">
                                            {s.subtitle}
                                        </p>
                                    )}
                                    {s.cta_label && s.cta_url && (
                                        <a
                                            href={resolveSlideUrl(s.cta_url, selectedBranch?.id ?? null)}
                                            className="mt-4 inline-flex w-fit items-center gap-1.5 rounded-full bg-white px-4 py-2 text-[11px] font-bold tracking-wider text-neutral-900 uppercase shadow transition-transform hover:scale-105"
                                        >
                                            {s.cta_label} <ArrowRight className="size-3" />
                                        </a>
                                    )}
                                </div>
                            </div>
                        ))}

                        {slides.length > 1 && (
                            <div className="absolute bottom-2 left-1/2 z-10 flex -translate-x-1/2 gap-1.5">
                                {slides.map((_, i) => (
                                    <button
                                        key={i}
                                        type="button"
                                        onClick={() => setActive(i)}
                                        aria-label={`Slide ${i + 1}`}
                                        className={cn(
                                            'h-1.5 rounded-full transition-all',
                                            active === i ? 'w-5 bg-white' : 'w-1.5 bg-white/50',
                                        )}
                                    />
                                ))}
                            </div>
                        )}
                    </div>
                </section>
            )}

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
                                        'group relative flex w-48 shrink-0 snap-start flex-col items-center overflow-hidden rounded-2xl border bg-white p-4 pt-7 transition-all duration-300 hover:-translate-y-1 active:scale-[0.98]',
                                        isCurrent
                                            ? 'border-amber-300 shadow-[0_10px_30px_-8px_rgba(245,158,11,0.45)] ring-2 ring-amber-300'
                                            : 'border-neutral-200 shadow-[0_6px_18px_-6px_rgba(0,0,0,0.18)] hover:shadow-[0_14px_30px_-10px_rgba(0,0,0,0.25)]',
                                    )}
                                    style={{
                                        background: isCurrent
                                            ? `linear-gradient(180deg, ${accent}1f 0%, #ffffff 55%)`
                                            : `linear-gradient(180deg, ${accent}10 0%, #ffffff 50%)`,
                                    }}
                                >
                                    {/* Accent top stripe */}
                                    <span
                                        aria-hidden
                                        className="absolute inset-x-0 top-0 h-1.5"
                                        style={{ background: accent }}
                                    />

                                    {/* Decorative shimmer ring behind badge */}
                                    <span
                                        aria-hidden
                                        className="absolute top-3 left-1/2 size-20 -translate-x-1/2 rounded-full opacity-50 blur-xl transition-opacity duration-300 group-hover:opacity-80"
                                        style={{ background: `${accent}55` }}
                                    />

                                    {/* Badge medallion */}
                                    <div
                                        className="relative flex size-16 items-center justify-center rounded-full ring-2 ring-white shadow-lg transition-transform duration-300 group-hover:scale-105"
                                        style={{
                                            background: `radial-gradient(circle at 30% 28%, #ffffffcc, ${accent} 68%)`,
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
                                        <span
                                            aria-hidden
                                            className="absolute top-2 left-2.5 size-3 rounded-full bg-white/70 blur-[1px]"
                                        />
                                    </div>

                                    <p
                                        className="mt-3 text-sm font-extrabold tracking-wider uppercase"
                                        style={{ color: accent }}
                                    >
                                        {tier.name}
                                    </p>
                                    <p className="text-muted-foreground mt-0.5 text-[10px] font-medium">
                                        RM {rangeLabel}
                                    </p>

                                    <div
                                        aria-hidden
                                        className="my-2.5 h-px w-2/3"
                                        style={{
                                            background: `linear-gradient(90deg, transparent, ${accent}55, transparent)`,
                                        }}
                                    />

                                    <ul className="w-full space-y-1.5 text-[10.5px] leading-snug text-neutral-700">
                                        <li className="flex items-start gap-1.5">
                                            <Check
                                                className="mt-0.5 size-3 shrink-0"
                                                style={{ color: accent }}
                                                strokeWidth={3}
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
                                                    strokeWidth={3}
                                                />
                                                <span>{perk}</span>
                                            </li>
                                        ))}
                                    </ul>

                                    {isCurrent && (
                                        <span className="mt-3 inline-flex items-center gap-1 rounded-full bg-linear-to-r from-amber-500 to-amber-600 px-2.5 py-0.5 text-[9px] font-bold tracking-wider text-white uppercase shadow-md ring-1 ring-amber-300">
                                            ★ Current Tier
                                        </span>
                                    )}
                                </div>
                            );
                        })}
                    </div>
                </section>
            )}

            <div className="mb-3 flex items-center justify-between gap-2">
                <h1 className="text-xl font-bold">Your Rewards</h1>
                <PushToggle />
            </div>

            <div className="-mx-1 mb-4 flex snap-x snap-mandatory gap-2.5 overflow-x-auto px-1 pb-2">
                {[
                    { href: '/spin', label: 'Spin & Win', emoji: '🎡' },
                    { href: '/check-in', label: 'Check-in', emoji: '📅' },
                    { href: '/rewards', label: 'Claim Points', emoji: '🪙' },
                    { href: '/favourites', label: 'Favourites', emoji: '❤️' },
                    { href: '/vouchers', label: 'Vouchers', emoji: '🎟️' },
                    { href: '/referral', label: 'Invite', emoji: '🎁' },
                ].map((item) => (
                    <Link
                        key={item.href}
                        href={item.href}
                        className="bg-primary flex w-20 shrink-0 snap-start flex-col items-center gap-1.5 rounded-2xl px-2 py-3 shadow-md transition active:scale-95"
                    >
                        <span className="bg-primary/70 ring-primary-foreground/10 flex size-12 items-center justify-center rounded-xl text-3xl shadow-inner ring-1">
                            {item.emoji}
                        </span>
                        <span className="text-primary-foreground text-center text-[10.5px] leading-tight font-semibold">
                            {item.label}
                        </span>
                    </Link>
                ))}
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

            <section className="border-border mb-4 overflow-hidden rounded-2xl border bg-linear-to-br from-rose-50 to-amber-50 p-4 shadow-sm">
                <div className="flex items-start gap-3">
                    <div className="bg-primary text-primary-foreground flex size-10 shrink-0 items-center justify-center rounded-xl text-xl shadow">
                        <Gift className="size-5" />
                    </div>
                    <div className="min-w-0 flex-1">
                        <p className="text-sm font-bold text-neutral-900">Invite friends, earn points</p>
                        <p className="text-muted-foreground text-[11px] leading-snug">
                            Get <strong>{referral.referrer_bonus} pts</strong> each time a friend signs up with your code.
                        </p>
                    </div>
                </div>
                <div className="mt-3 flex items-stretch gap-2">
                    <div className="border-border flex flex-1 items-center justify-between gap-2 rounded-lg border border-dashed bg-white px-3 py-2">
                        <span className="font-mono text-sm font-bold tracking-wider text-neutral-800">
                            {referral.code}
                        </span>
                        <button
                            type="button"
                            onClick={copyReferral}
                            className="text-muted-foreground hover:text-primary inline-flex shrink-0 items-center gap-1 text-[11px] font-semibold"
                        >
                            <Copy className="size-3.5" /> {copied ? 'Copied!' : 'Copy'}
                        </button>
                    </div>
                    <button
                        type="button"
                        onClick={shareReferral}
                        className="bg-primary text-primary-foreground inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-xs font-bold shadow active:scale-95"
                    >
                        <Share2 className="size-3.5" /> Share
                    </button>
                </div>
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
