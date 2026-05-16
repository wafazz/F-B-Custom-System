import { Head, router, usePage } from '@inertiajs/react';
import { Check, Copy, Tag, Ticket, TimerReset } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import StorefrontLayout from '@/layouts/storefront-layout';
import { cn } from '@/lib/utils';
import type { Flash } from '@/types';

interface Voucher {
    id: number;
    code: string;
    name: string;
    description: string | null;
    discount_type: 'percentage' | 'fixed';
    discount_value: number;
    min_subtotal: number;
    max_discount: number | null;
    valid_until: string | null;
}

interface ClaimedVoucher {
    id: number;
    used: boolean;
    used_at: string | null;
    claimed_at: string | null;
    voucher: Voucher | null;
}

interface Props {
    available: Voucher[];
    claimed: ClaimedVoucher[];
}

export default function Vouchers({ available, claimed }: Props) {
    const flash = usePage<{ flash: Flash }>().props.flash;
    const [claiming, setClaiming] = useState<number | null>(null);
    const [copied, setCopied] = useState<string | null>(null);

    function handleClaim(voucher: Voucher) {
        setClaiming(voucher.id);
        router.post(
            `/vouchers/${voucher.id}/claim`,
            {},
            {
                preserveScroll: true,
                onFinish: () => setClaiming(null),
            },
        );
    }

    async function handleCopy(code: string) {
        try {
            await navigator.clipboard.writeText(code);
            setCopied(code);
            setTimeout(() => setCopied(null), 1500);
        } catch {
            // ignore
        }
    }

    const unused = claimed.filter((c) => !c.used && c.voucher);
    const used = claimed.filter((c) => c.used && c.voucher);

    return (
        <StorefrontLayout>
            <Head title="Vouchers" />

            <div className="mb-3 flex items-center gap-2">
                <Ticket className="text-primary size-5" />
                <h1 className="text-xl font-bold">Vouchers</h1>
            </div>

            {flash?.success && (
                <p className="mb-3 rounded-md border border-emerald-200 bg-emerald-50 p-3 text-xs text-emerald-700">
                    {flash.success}
                </p>
            )}
            {flash?.error && (
                <p className="mb-3 rounded-md border border-red-200 bg-red-50 p-3 text-xs text-red-700">
                    {flash.error}
                </p>
            )}

            <section className="mb-6">
                <h2 className="mb-2 text-sm font-semibold">My vouchers</h2>
                {unused.length === 0 ? (
                    <div className="border-border bg-card text-muted-foreground rounded-xl border border-dashed p-6 text-center text-sm">
                        No claimed vouchers yet. Pick one below.
                    </div>
                ) : (
                    <ul className="space-y-2">
                        {unused.map((c) =>
                            c.voucher ? (
                                <li
                                    key={c.id}
                                    className="border-border bg-card rounded-xl border p-4 shadow-sm"
                                >
                                    <VoucherBody voucher={c.voucher} />
                                    <button
                                        type="button"
                                        onClick={() => handleCopy(c.voucher!.code)}
                                        className={cn(
                                            'mt-3 flex w-full items-center justify-center gap-2 rounded-lg border-2 border-dashed py-2 text-sm font-mono font-bold tracking-wider transition-colors',
                                            copied === c.voucher.code
                                                ? 'border-emerald-400 bg-emerald-50 text-emerald-700'
                                                : 'border-amber-400 bg-amber-50 text-amber-700 hover:bg-amber-100',
                                        )}
                                    >
                                        {copied === c.voucher.code ? (
                                            <>
                                                <Check className="size-4" /> Copied!
                                            </>
                                        ) : (
                                            <>
                                                <Copy className="size-4" /> {c.voucher.code}
                                            </>
                                        )}
                                    </button>
                                </li>
                            ) : null,
                        )}
                    </ul>
                )}
            </section>

            <section className="mb-6">
                <h2 className="mb-2 text-sm font-semibold">Available to claim</h2>
                {available.length === 0 ? (
                    <div className="border-border bg-card text-muted-foreground rounded-xl border border-dashed p-6 text-center text-sm">
                        No active promos right now. Check back soon!
                    </div>
                ) : (
                    <ul className="space-y-2">
                        {available.map((v) => (
                            <li
                                key={v.id}
                                className="border-border bg-card rounded-xl border p-4 shadow-sm"
                            >
                                <VoucherBody voucher={v} />
                                <Button
                                    onClick={() => handleClaim(v)}
                                    disabled={claiming === v.id}
                                    className="mt-3 w-full"
                                >
                                    {claiming === v.id ? 'Claiming…' : 'Claim'}
                                </Button>
                            </li>
                        ))}
                    </ul>
                )}
            </section>

            {used.length > 0 && (
                <section>
                    <h2 className="mb-2 text-sm font-semibold">Used</h2>
                    <ul className="space-y-1.5 text-xs">
                        {used.map((c) =>
                            c.voucher ? (
                                <li
                                    key={c.id}
                                    className="border-border bg-card text-muted-foreground flex items-center justify-between gap-2 rounded-lg border p-3 opacity-60"
                                >
                                    <span className="font-mono font-semibold">{c.voucher.code}</span>
                                    <span className="text-[10px]">
                                        used{' '}
                                        {c.used_at
                                            ? new Date(c.used_at).toLocaleDateString()
                                            : ''}
                                    </span>
                                </li>
                            ) : null,
                        )}
                    </ul>
                </section>
            )}
        </StorefrontLayout>
    );
}

function VoucherBody({ voucher }: { voucher: Voucher }) {
    const discountLabel =
        voucher.discount_type === 'percentage'
            ? `${voucher.discount_value.toFixed(0)}% off`
            : `RM${voucher.discount_value.toFixed(2)} off`;

    return (
        <div>
            <div className="flex items-start justify-between gap-3">
                <div className="flex-1">
                    <p className="text-sm font-semibold">{voucher.name}</p>
                    {voucher.description && (
                        <p className="text-muted-foreground mt-0.5 text-xs">
                            {voucher.description}
                        </p>
                    )}
                </div>
                <div className="flex shrink-0 items-center gap-1 rounded-full bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-700">
                    <Tag className="size-3" /> {discountLabel}
                </div>
            </div>
            <div className="text-muted-foreground mt-2 flex flex-wrap gap-x-3 gap-y-1 text-[11px]">
                {voucher.min_subtotal > 0 && (
                    <span>Min RM{voucher.min_subtotal.toFixed(2)}</span>
                )}
                {voucher.max_discount !== null && (
                    <span>Cap RM{voucher.max_discount.toFixed(2)}</span>
                )}
                {voucher.valid_until && (
                    <span className="flex items-center gap-1">
                        <TimerReset className="size-3" />
                        Until {new Date(voucher.valid_until).toLocaleDateString()}
                    </span>
                )}
            </div>
        </div>
    );
}
