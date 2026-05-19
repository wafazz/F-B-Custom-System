import { Head } from '@inertiajs/react';
import { Sparkles } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import StorefrontLayout from '@/layouts/storefront-layout';

interface Segment {
    id: number;
    label: string;
    color: string;
}

interface Props {
    segments: Segment[];
    can_spin: boolean;
}

interface SpinResult {
    segment_id: number;
    label: string;
    awarded_points: number;
    voucher_claimed: boolean;
    message: string;
}

export default function Spin({ segments, can_spin }: Props) {
    const [rotation, setRotation] = useState(0);
    const [spinning, setSpinning] = useState(false);
    const [result, setResult] = useState<SpinResult | null>(null);
    const [error, setError] = useState<string | null>(null);
    const [done, setDone] = useState(!can_spin);

    const segmentCount = segments.length;
    const segmentAngle = segmentCount > 0 ? 360 / segmentCount : 0;

    async function handleSpin() {
        if (spinning || done || segmentCount === 0) return;
        setError(null);
        setResult(null);
        setSpinning(true);

        const csrf = (() => {
            const v = document.cookie
                .split('; ')
                .find((c) => c.startsWith('XSRF-TOKEN='))
                ?.substring('XSRF-TOKEN='.length);
            return v ? decodeURIComponent(v) : '';
        })();

        try {
            const res = await fetch('/spin', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-XSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            if (!res.ok) {
                const data = await res.json().catch(() => ({}));
                setError((data as { message?: string }).message ?? `HTTP ${res.status}`);
                setSpinning(false);
                return;
            }
            const data = (await res.json()) as SpinResult;
            const winnerIndex = segments.findIndex((s) => s.id === data.segment_id);
            if (winnerIndex === -1) {
                setError('Unexpected response from server.');
                setSpinning(false);
                return;
            }

            // Land the wheel so the pointer at top (12 o'clock) targets the
            // winning segment's centre. The wheel rotates by `rotation` deg
            // clockwise; segment 0 starts at 12 o'clock pre-rotation. To
            // bring winnerIndex's centre to 12 we need -targetCentre offset,
            // plus 5 extra spins for drama.
            const targetCentre = winnerIndex * segmentAngle + segmentAngle / 2;
            const baseRotation = 360 * 5;
            const finalAngle = baseRotation + (360 - targetCentre);
            setRotation(rotation + finalAngle);

            // Reveal after the CSS transition.
            window.setTimeout(() => {
                setSpinning(false);
                setResult(data);
                setDone(true);
            }, 4200);
        } catch (e) {
            setError(e instanceof Error ? e.message : String(e));
            setSpinning(false);
        }
    }

    return (
        <StorefrontLayout hideStats>
            <Head title="Spin the wheel" />

            <div className="mb-2 flex items-center justify-between gap-2">
                <h1 className="flex items-center gap-2 text-xl font-bold">
                    <Sparkles className="size-5 text-amber-500" /> Daily spin
                </h1>
            </div>
            <p className="text-muted-foreground mb-6 text-xs">
                Spin once a day for free points or vouchers. Comes back tomorrow.
            </p>

            {segments.length === 0 ? (
                <div className="border-border bg-card rounded-2xl border border-dashed p-10 text-center text-sm text-neutral-500">
                    The wheel is being prepared. Check back soon!
                </div>
            ) : (
                <div className="flex flex-col items-center">
                    {/* Pointer */}
                    <div className="relative">
                        <div
                            aria-hidden
                            className="absolute top-0 left-1/2 z-10 -translate-x-1/2 -translate-y-1"
                            style={{
                                width: 0,
                                height: 0,
                                borderLeft: '14px solid transparent',
                                borderRight: '14px solid transparent',
                                borderTop: '24px solid #7c4a1e',
                                filter: 'drop-shadow(0 2px 4px rgba(0,0,0,0.25))',
                            }}
                        />

                        {/* Wheel */}
                        <div
                            className="relative aspect-square w-72 max-w-full overflow-hidden rounded-full border-4 border-amber-800 shadow-xl"
                            style={{
                                transform: `rotate(${rotation}deg)`,
                                transition: spinning
                                    ? 'transform 4s cubic-bezier(0.17, 0.67, 0.21, 0.99)'
                                    : 'none',
                            }}
                        >
                            <svg viewBox="-100 -100 200 200" className="size-full" aria-hidden>
                                {segments.map((s, i) => {
                                    const startAngle = i * segmentAngle - 90;
                                    const endAngle = (i + 1) * segmentAngle - 90;
                                    const largeArc = segmentAngle > 180 ? 1 : 0;
                                    const x1 = 100 * Math.cos((startAngle * Math.PI) / 180);
                                    const y1 = 100 * Math.sin((startAngle * Math.PI) / 180);
                                    const x2 = 100 * Math.cos((endAngle * Math.PI) / 180);
                                    const y2 = 100 * Math.sin((endAngle * Math.PI) / 180);
                                    const path = `M 0 0 L ${x1.toFixed(3)} ${y1.toFixed(3)} A 100 100 0 ${largeArc} 1 ${x2.toFixed(3)} ${y2.toFixed(3)} Z`;
                                    const midAngle = startAngle + segmentAngle / 2;
                                    const tx = 65 * Math.cos((midAngle * Math.PI) / 180);
                                    const ty = 65 * Math.sin((midAngle * Math.PI) / 180);
                                    return (
                                        <g key={s.id}>
                                            <path
                                                d={path}
                                                fill={s.color}
                                                stroke="white"
                                                strokeWidth={1}
                                            />
                                            <text
                                                x={tx}
                                                y={ty}
                                                textAnchor="middle"
                                                dominantBaseline="middle"
                                                fontSize="9"
                                                fontWeight="700"
                                                fill="white"
                                                transform={`rotate(${midAngle + 90} ${tx} ${ty})`}
                                                style={{
                                                    textShadow: '0 1px 2px rgba(0,0,0,0.4)',
                                                }}
                                            >
                                                {s.label}
                                            </text>
                                        </g>
                                    );
                                })}
                            </svg>
                        </div>

                        {/* Centre cap */}
                        <div className="pointer-events-none absolute inset-0 flex items-center justify-center">
                            <div className="size-12 rounded-full bg-amber-800 shadow-lg ring-4 ring-amber-100" />
                        </div>
                    </div>

                    <Button
                        onClick={handleSpin}
                        disabled={spinning || done}
                        className="mt-8 w-full max-w-xs"
                    >
                        {spinning ? 'Spinning…' : done ? 'See you tomorrow!' : 'Spin the wheel'}
                    </Button>

                    {result && (
                        <div className="mt-4 w-full max-w-xs rounded-2xl border border-amber-300 bg-amber-50 p-4 text-center shadow">
                            <p className="text-xs font-semibold tracking-wider text-amber-700 uppercase">
                                {result.awarded_points > 0 || result.voucher_claimed
                                    ? 'You won!'
                                    : 'Not this time'}
                            </p>
                            <p className="mt-1 text-2xl font-extrabold text-amber-900">
                                {result.label}
                            </p>
                            <p className="text-muted-foreground mt-2 text-xs">{result.message}</p>
                        </div>
                    )}

                    {error && (
                        <p className="mt-4 w-full max-w-xs rounded-md border border-red-200 bg-red-50 px-3 py-2 text-center text-xs text-red-700">
                            {error}
                        </p>
                    )}
                </div>
            )}
        </StorefrontLayout>
    );
}
