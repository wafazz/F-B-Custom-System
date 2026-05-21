import { Head } from '@inertiajs/react';
import { CalendarCheck, Check, Coins, Sparkles, Ticket } from 'lucide-react';
import { useState } from 'react';
import StorefrontLayout from '@/layouts/storefront-layout';
import { cn } from '@/lib/utils';

interface RewardRow {
    day_number: number;
    label: string | null;
    reward_type: 'points' | 'voucher';
    points: number | null;
    voucher_name: string | null;
}

interface RecentRow {
    id: number;
    check_in_date: string;
    day_number: number;
    reward_type: 'points' | 'voucher';
    awarded_points: number;
    voucher_claim_id: number | null;
}

interface Props {
    settings: { max_days: number; reset_on_skip: boolean };
    rewards: RewardRow[];
    recent: RecentRow[];
    can_check_in: boolean;
    current_streak: number;
    next_day: number;
}

interface CheckInResult {
    day_number: number;
    reward_type: 'points' | 'voucher';
    awarded_points: number;
    voucher_claimed: boolean;
    message: string;
}

export default function CheckIn({
    settings,
    rewards,
    recent,
    can_check_in,
    current_streak,
    next_day,
}: Props) {
    const [busy, setBusy] = useState(false);
    const [done, setDone] = useState(!can_check_in);
    const [result, setResult] = useState<CheckInResult | null>(null);
    const [error, setError] = useState<string | null>(null);
    const [streak, setStreak] = useState(current_streak);
    const [nextDay, setNextDay] = useState(next_day);

    const rewardsByDay = new Map(rewards.map((r) => [r.day_number, r]));

    async function handleCheckIn() {
        if (busy || done) return;
        setBusy(true);
        setError(null);

        const csrf = (() => {
            const v = document.cookie
                .split('; ')
                .find((c) => c.startsWith('XSRF-TOKEN='))
                ?.substring('XSRF-TOKEN='.length);
            return v ? decodeURIComponent(v) : '';
        })();

        try {
            const res = await fetch('/check-in', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-XSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok) {
                setError((data as { message?: string }).message ?? `HTTP ${res.status}`);
                setBusy(false);
                return;
            }
            const r = data as CheckInResult;
            setResult(r);
            setStreak(r.day_number);
            setNextDay(r.day_number >= settings.max_days ? 1 : r.day_number + 1);
            setDone(true);
        } catch (e) {
            setError(e instanceof Error ? e.message : String(e));
        } finally {
            setBusy(false);
        }
    }

    return (
        <StorefrontLayout>
            <Head title="Daily Check-in" />

            <div className="mb-3 flex items-center gap-2">
                <CalendarCheck className="size-5 text-amber-600" />
                <h1 className="text-xl font-bold">Daily Check-in</h1>
            </div>
            <p className="text-muted-foreground mb-4 text-xs">
                Check in once a day to earn rewards.{' '}
                {settings.reset_on_skip
                    ? 'Miss a day and the streak restarts at Day 1.'
                    : 'Skip a day and the streak picks up where you left off.'}
            </p>

            <section className="mb-4 rounded-2xl border border-amber-200 bg-linear-to-br from-amber-50 to-orange-100 p-4 shadow-sm">
                <div className="flex items-center justify-between gap-3">
                    <div>
                        <p className="text-muted-foreground text-[11px] tracking-wider uppercase">
                            Current streak
                        </p>
                        <p className="text-3xl font-extrabold text-amber-700">
                            Day {streak} <span className="text-base font-bold text-amber-700/60">/ {settings.max_days}</span>
                        </p>
                        {!done && (
                            <p className="text-muted-foreground mt-1 text-xs">
                                Next: <strong>Day {nextDay}</strong>
                            </p>
                        )}
                    </div>
                    <button
                        type="button"
                        onClick={handleCheckIn}
                        disabled={busy || done}
                        className="bg-primary text-primary-foreground rounded-full px-4 py-2.5 text-xs font-bold tracking-wider uppercase shadow-md transition-transform active:scale-95 disabled:opacity-60"
                    >
                        {busy ? 'Checking in…' : done ? 'Checked in ✓' : 'Check in'}
                    </button>
                </div>
            </section>

            {result && (
                <div className="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-center text-sm text-emerald-800">
                    <p className="flex items-center justify-center gap-1 text-xs font-semibold tracking-wider uppercase">
                        <Sparkles className="size-3.5" /> {result.message}
                    </p>
                </div>
            )}
            {error && (
                <p className="mb-4 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-center text-xs text-red-700">
                    {error}
                </p>
            )}

            <h2 className="mb-2 text-sm font-semibold">Reward calendar</h2>
            <div className="mb-5 grid grid-cols-3 gap-2 sm:grid-cols-4">
                {Array.from({ length: settings.max_days }, (_, i) => i + 1).map((day) => {
                    const reward = rewardsByDay.get(day);
                    const isPast = day <= streak && (done || day < streak);
                    const isToday = !done && day === nextDay;
                    return (
                        <div
                            key={day}
                            className={cn(
                                'flex flex-col items-center gap-1 rounded-xl border p-2.5 text-center',
                                isPast
                                    ? 'border-emerald-300 bg-emerald-50'
                                    : isToday
                                      ? 'border-amber-400 bg-amber-50 ring-2 ring-amber-300'
                                      : 'border-border bg-card',
                            )}
                        >
                            <p
                                className={cn(
                                    'text-[10px] font-bold tracking-wider uppercase',
                                    isPast
                                        ? 'text-emerald-600'
                                        : isToday
                                          ? 'text-amber-700'
                                          : 'text-muted-foreground',
                                )}
                            >
                                Day {day}
                            </p>
                            {reward ? (
                                reward.reward_type === 'points' ? (
                                    <>
                                        <Coins
                                            className={cn(
                                                'size-5',
                                                isPast ? 'text-emerald-500' : 'text-amber-500',
                                            )}
                                        />
                                        <p className="text-xs font-bold text-neutral-900">
                                            {reward.points} pts
                                        </p>
                                    </>
                                ) : (
                                    <>
                                        <Ticket
                                            className={cn(
                                                'size-5',
                                                isPast ? 'text-emerald-500' : 'text-fuchsia-500',
                                            )}
                                        />
                                        <p className="line-clamp-2 text-[10px] leading-tight font-semibold text-neutral-800">
                                            {reward.voucher_name ?? 'Voucher'}
                                        </p>
                                    </>
                                )
                            ) : (
                                <p className="text-muted-foreground text-[10px] italic">
                                    No reward
                                </p>
                            )}
                            {reward?.label && (
                                <p className="text-muted-foreground text-[9px] tracking-wider uppercase">
                                    {reward.label}
                                </p>
                            )}
                            {isPast && <Check className="absolute -top-1 -right-1 size-3 text-emerald-600" />}
                        </div>
                    );
                })}
            </div>

            {recent.length > 0 && (
                <>
                    <h2 className="mb-2 text-sm font-semibold">Recent check-ins</h2>
                    <ul className="space-y-1.5 text-xs">
                        {recent.map((r) => (
                            <li
                                key={r.id}
                                className="border-border bg-card flex items-center justify-between gap-2 rounded-lg border p-3"
                            >
                                <div>
                                    <p className="font-semibold">Day {r.day_number}</p>
                                    <p className="text-muted-foreground text-[10px]">
                                        {r.check_in_date}
                                    </p>
                                </div>
                                <p className="font-bold text-emerald-600">
                                    {r.reward_type === 'points'
                                        ? `+${r.awarded_points} pts`
                                        : r.voucher_claim_id !== null
                                          ? 'Voucher'
                                          : '—'}
                                </p>
                            </li>
                        ))}
                    </ul>
                </>
            )}
        </StorefrontLayout>
    );
}
