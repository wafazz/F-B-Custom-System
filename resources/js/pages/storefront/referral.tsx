import { Head } from '@inertiajs/react';
import { Copy, Gift, Share2 } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import StorefrontLayout from '@/layouts/storefront-layout';

interface RewardRow {
    id: number;
    referee_name: string;
    points_earned: number;
    created_at: string | null;
}

interface Props {
    code: string;
    share_url: string;
    enabled: boolean;
    referrer_bonus: number;
    referee_bonus: number;
    min_first_order_amount: number;
    share_text_template: string;
    rewards: RewardRow[];
    total_earned: number;
}

export default function Referral({
    code,
    share_url,
    enabled,
    referrer_bonus,
    referee_bonus,
    min_first_order_amount,
    share_text_template,
    rewards,
    total_earned,
}: Props) {
    const [copied, setCopied] = useState(false);

    function copyCode() {
        navigator.clipboard.writeText(share_url).then(() => {
            setCopied(true);
            window.setTimeout(() => setCopied(false), 1500);
        });
    }

    function share() {
        const text = (share_text_template || '')
            .replaceAll('{code}', code)
            .replaceAll('{points}', String(referee_bonus))
            .replaceAll('{url}', share_url);
        if (typeof navigator !== 'undefined' && 'share' in navigator) {
            void navigator.share({ title: 'Star Coffee', text, url: share_url });
        } else {
            const url = `https://wa.me/?text=${encodeURIComponent(text)}`;
            window.open(url, '_blank');
        }
    }

    return (
        <StorefrontLayout>
            <Head title="Refer a Friend" />

            <h1 className="mb-3 text-xl font-bold">Refer a Friend</h1>

            {!enabled && (
                <div className="mb-4 rounded-xl border border-slate-200 bg-slate-50 p-4 text-xs text-slate-700">
                    The referral program is currently paused. You can still share your code, but
                    new bonuses won't be awarded until it's reactivated.
                </div>
            )}

            <section className="border-border mb-4 rounded-2xl border bg-gradient-to-br from-amber-100 to-orange-200 p-5 shadow-sm">
                <div className="flex items-center gap-3">
                    <div className="flex size-12 items-center justify-center rounded-full bg-amber-700 text-white">
                        <Gift className="size-6" />
                    </div>
                    <div>
                        <p className="text-xs text-amber-900/70">Your referral code</p>
                        <p className="font-mono text-2xl font-bold tracking-widest text-amber-900">
                            {code}
                        </p>
                    </div>
                </div>
                <p className="mt-3 text-sm text-amber-900">
                    Share with a friend. They get <strong>{referee_bonus} pts</strong> on their
                    first order, you get <strong>{referrer_bonus} pts</strong>.
                </p>
                {min_first_order_amount > 0 && (
                    <p className="mt-1 text-[11px] text-amber-900/70">
                        Minimum first order RM{min_first_order_amount.toFixed(2)} to qualify.
                    </p>
                )}
                <div className="mt-3 grid grid-cols-2 gap-2">
                    <Button onClick={copyCode} variant="outline" className="bg-white/70">
                        <Copy className="mr-1.5 size-4" /> {copied ? 'Copied!' : 'Copy link'}
                    </Button>
                    <Button onClick={share}>
                        <Share2 className="mr-1.5 size-4" /> Share
                    </Button>
                </div>
            </section>

            <section className="border-border bg-card mb-4 rounded-xl border p-4 text-sm shadow-sm">
                <div className="flex items-center justify-between">
                    <span className="text-muted-foreground">Total earned</span>
                    <span className="text-primary text-lg font-bold">
                        {total_earned.toLocaleString()} pts
                    </span>
                </div>
            </section>

            <h2 className="mb-2 text-sm font-semibold">Friends who joined</h2>
            {rewards.length === 0 ? (
                <div className="border-border bg-card text-muted-foreground rounded-xl border border-dashed p-6 text-center text-sm">
                    No referrals yet. Share your code to get started.
                </div>
            ) : (
                <ul className="space-y-1.5 text-xs">
                    {rewards.map((row) => (
                        <li
                            key={row.id}
                            className="border-border bg-card flex items-center justify-between gap-2 rounded-lg border p-3"
                        >
                            <span className="font-semibold">{row.referee_name}</span>
                            <div className="text-right">
                                <p className="font-bold text-emerald-600">+{row.points_earned}</p>
                                <p className="text-muted-foreground text-[10px]">
                                    {row.created_at
                                        ? new Date(row.created_at).toLocaleDateString()
                                        : '—'}
                                </p>
                            </div>
                        </li>
                    ))}
                </ul>
            )}
        </StorefrontLayout>
    );
}
