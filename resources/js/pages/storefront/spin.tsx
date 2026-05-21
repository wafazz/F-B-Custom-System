import { Head } from '@inertiajs/react';
import { Sparkles } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import StorefrontLayout from '@/layouts/storefront-layout';

interface Segment {
    id: number;
    label: string;
    color: string;
    image_path: string | null;
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
    const [showConfetti, setShowConfetti] = useState(false);

    const segmentCount = segments.length;
    const segmentAngle = segmentCount > 0 ? 360 / segmentCount : 0;
    const won = result !== null && (result.awarded_points > 0 || result.voucher_claimed);

    const confetti = useMemo(() => {
        const colors = ['#ef4444', '#f59e0b', '#facc15', '#10b981', '#3b82f6', '#a855f7', '#ec4899'];
        return Array.from({ length: 32 }, (_, i) => ({
            left: Math.random() * 100,
            delay: Math.random() * 0.4,
            duration: 1.4 + Math.random() * 1.2,
            color: colors[i % colors.length],
            rotate: Math.random() * 360,
            size: 6 + Math.random() * 6,
        }));
    }, []);

    useEffect(() => {
        if (!won) return;
        setShowConfetti(true);
        const t = window.setTimeout(() => setShowConfetti(false), 3200);
        return () => window.clearTimeout(t);
    }, [won]);

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

            const targetCentre = winnerIndex * segmentAngle + segmentAngle / 2;
            const baseRotation = 360 * 6;
            const finalAngle = baseRotation + (360 - targetCentre);
            setRotation(rotation + finalAngle);

            window.setTimeout(() => {
                setSpinning(false);
                setResult(data);
                setDone(true);
            }, 4600);
        } catch (e) {
            setError(e instanceof Error ? e.message : String(e));
            setSpinning(false);
        }
    }

    return (
        <StorefrontLayout hideStats>
            <Head title="Spin the wheel" />

            <style>{`
                @keyframes spin-wheel-confetti {
                    0% { transform: translateY(-20px) rotate(0deg); opacity: 0; }
                    10% { opacity: 1; }
                    100% { transform: translateY(360px) rotate(540deg); opacity: 0; }
                }
                @keyframes spin-wheel-pointer-shake {
                    0%, 100% { transform: translate(-50%, 0) rotate(0deg); }
                    25% { transform: translate(-54%, 0) rotate(-6deg); }
                    75% { transform: translate(-46%, 0) rotate(6deg); }
                }
                @keyframes spin-wheel-pop {
                    0% { transform: scale(0.6); opacity: 0; }
                    60% { transform: scale(1.08); opacity: 1; }
                    100% { transform: scale(1); opacity: 1; }
                }
                .spin-grid-bg {
                    background-color: #eef2f6;
                    background-image:
                        linear-gradient(to right, rgba(148,163,184,0.35) 1px, transparent 1px),
                        linear-gradient(to bottom, rgba(148,163,184,0.35) 1px, transparent 1px);
                    background-size: 28px 28px;
                }
            `}</style>

            {segments.length === 0 ? (
                <div className="border-border bg-card rounded-2xl border border-dashed p-10 text-center text-sm text-neutral-500">
                    The wheel is being prepared. Check back soon!
                </div>
            ) : (
                <div className="spin-grid-bg relative -mx-4 overflow-hidden rounded-2xl px-4 pt-4 pb-6 sm:-mx-6 sm:px-6">
                    {/* Title */}
                    <h1 className="mb-2 text-center text-sm font-extrabold tracking-[0.18em] text-neutral-800 uppercase">
                        Loyalty Rewards Wheel
                    </h1>

                    {/* Confetti */}
                    {showConfetti && (
                        <div className="pointer-events-none absolute inset-x-0 top-0 z-30 h-96 overflow-hidden">
                            {confetti.map((c, i) => (
                                <span
                                    key={i}
                                    style={{
                                        position: 'absolute',
                                        top: 0,
                                        left: `${c.left}%`,
                                        width: `${c.size}px`,
                                        height: `${c.size * 0.4}px`,
                                        background: c.color,
                                        transform: `rotate(${c.rotate}deg)`,
                                        animation: `spin-wheel-confetti ${c.duration}s ${c.delay}s ease-in forwards`,
                                        borderRadius: 2,
                                    }}
                                />
                            ))}
                        </div>
                    )}

                    <div className="relative mx-auto w-fit">
                        {/* Pointer — beige downward arrow on top */}
                        <div
                            aria-hidden
                            className="absolute -top-1 left-1/2 z-20"
                            style={{
                                animation: spinning
                                    ? 'spin-wheel-pointer-shake 0.18s linear infinite'
                                    : 'none',
                                transform: 'translateX(-50%)',
                                transformOrigin: '50% 0%',
                            }}
                        >
                            <svg
                                width="46"
                                height="58"
                                viewBox="0 0 46 58"
                                style={{ filter: 'drop-shadow(0 3px 4px rgba(0,0,0,0.25))' }}
                            >
                                <path
                                    d="M23 56 L8 12 Q23 4 38 12 Z"
                                    fill="#d6c5a8"
                                    stroke="#a08a6a"
                                    strokeWidth="1.5"
                                />
                            </svg>
                        </div>

                        {/* Wheel — thick black border, no outer ring */}
                        <div
                            className="relative aspect-square w-88 max-w-[88vw] overflow-hidden rounded-full border-[3px] border-black bg-white shadow-xl"
                            style={{
                                transform: `rotate(${rotation}deg)`,
                                transition: spinning
                                    ? 'transform 4.4s cubic-bezier(0.16, 0.68, 0.18, 0.995)'
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
                                    const labelRadius = s.image_path ? 48 : 60;
                                    const tx = labelRadius * Math.cos((midAngle * Math.PI) / 180);
                                    const ty = labelRadius * Math.sin((midAngle * Math.PI) / 180);
                                    const imgSize = Math.min(36, segmentAngle * 0.7);
                                    const ix = 75 * Math.cos((midAngle * Math.PI) / 180);
                                    const iy = 75 * Math.sin((midAngle * Math.PI) / 180);

                                    return (
                                        <g key={s.id}>
                                            <path
                                                d={path}
                                                fill={s.color}
                                                stroke="#000"
                                                strokeWidth={1.4}
                                            />
                                            {s.image_path && (
                                                <image
                                                    href={`/storage/${s.image_path}`}
                                                    x={ix - imgSize / 2}
                                                    y={iy - imgSize / 2}
                                                    width={imgSize}
                                                    height={imgSize}
                                                    preserveAspectRatio="xMidYMid slice"
                                                    transform={`rotate(${midAngle + 90} ${ix} ${iy})`}
                                                    style={{
                                                        filter: 'drop-shadow(0 1px 2px rgba(0,0,0,0.35))',
                                                    }}
                                                />
                                            )}
                                            <text
                                                x={tx}
                                                y={ty}
                                                textAnchor="middle"
                                                dominantBaseline="middle"
                                                fontSize="8.5"
                                                fontWeight="800"
                                                fill="white"
                                                transform={`rotate(${midAngle + 90} ${tx} ${ty})`}
                                                style={{
                                                    textShadow: '0 1px 2px rgba(0,0,0,0.55)',
                                                    letterSpacing: '0.02em',
                                                }}
                                            >
                                                {s.label}
                                            </text>
                                        </g>
                                    );
                                })}
                            </svg>
                        </div>
                    </div>

                    {/* Spin button — dark pill, bottom-right corner like image */}
                    <div className="mt-6 flex justify-end">
                        <button
                            type="button"
                            onClick={handleSpin}
                            disabled={spinning || done}
                            className="rounded-full bg-[#3d2817] px-5 py-2.5 text-xs font-bold tracking-wider text-white uppercase shadow-md transition-transform hover:scale-[1.03] disabled:opacity-60"
                        >
                            {spinning ? 'Spinning…' : done ? 'Come back tomorrow' : 'Spin for 10 points'}
                        </button>
                    </div>

                    {result && (
                        <div
                            className={`mx-auto mt-6 w-full max-w-xs rounded-2xl border p-5 text-center shadow-lg ${
                                won
                                    ? 'border-amber-300 bg-linear-to-br from-amber-50 via-yellow-50 to-amber-100'
                                    : 'border-neutral-200 bg-neutral-50'
                            }`}
                            style={{ animation: 'spin-wheel-pop 0.5s cubic-bezier(0.18, 0.89, 0.32, 1.28) both' }}
                        >
                            <p
                                className={`flex items-center justify-center gap-1 text-xs font-semibold tracking-wider uppercase ${
                                    won ? 'text-amber-700' : 'text-neutral-500'
                                }`}
                            >
                                {won && <Sparkles className="size-3.5" />}
                                {won ? 'You won!' : 'Not this time'}
                                {won && <Sparkles className="size-3.5" />}
                            </p>
                            <p
                                className={`mt-1 text-2xl font-extrabold ${
                                    won ? 'text-amber-900' : 'text-neutral-700'
                                }`}
                            >
                                {result.label}
                            </p>
                            <p className="text-muted-foreground mt-2 text-xs">{result.message}</p>
                        </div>
                    )}

                    {error && (
                        <p className="mx-auto mt-4 w-full max-w-xs rounded-md border border-red-200 bg-red-50 px-3 py-2 text-center text-xs text-red-700">
                            {error}
                        </p>
                    )}
                </div>
            )}
        </StorefrontLayout>
    );
}
