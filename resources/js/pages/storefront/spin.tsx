import { Head } from '@inertiajs/react';
import { Sparkles, Star } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { Button } from '@/components/ui/button';
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

const BULB_COUNT = 24;

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

    const bulbs = useMemo(
        () => Array.from({ length: BULB_COUNT }, (_, i) => (360 / BULB_COUNT) * i),
        [],
    );

    const confetti = useMemo(() => {
        const colors = ['#f59e0b', '#ef4444', '#10b981', '#3b82f6', '#ec4899', '#facc15'];
        return Array.from({ length: 28 }, (_, i) => ({
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
                @keyframes spin-wheel-bulb {
                    0%, 100% { opacity: 0.35; transform: scale(0.85); box-shadow: 0 0 4px rgba(255,255,255,0.4); }
                    50% { opacity: 1; transform: scale(1.15); box-shadow: 0 0 10px #fde68a, 0 0 18px #f59e0b; }
                }
                @keyframes spin-wheel-idle {
                    0%, 100% { transform: rotate(-2deg); }
                    50% { transform: rotate(2deg); }
                }
                @keyframes spin-wheel-glow {
                    0%, 100% { box-shadow: 0 0 24px rgba(245,158,11,0.45), 0 0 48px rgba(245,158,11,0.25); }
                    50% { box-shadow: 0 0 36px rgba(245,158,11,0.75), 0 0 72px rgba(245,158,11,0.45); }
                }
                @keyframes spin-wheel-confetti {
                    0% { transform: translateY(-20px) rotate(0deg); opacity: 0; }
                    10% { opacity: 1; }
                    100% { transform: translateY(360px) rotate(540deg); opacity: 0; }
                }
                @keyframes spin-wheel-pointer-bob {
                    0%, 100% { transform: translate(-50%, -4px); }
                    50% { transform: translate(-50%, 0px); }
                }
                @keyframes spin-wheel-pointer-shake {
                    0%, 100% { transform: translate(-50%, -2px) rotate(0deg); }
                    25% { transform: translate(-54%, 0px) rotate(-6deg); }
                    75% { transform: translate(-46%, 0px) rotate(6deg); }
                }
                @keyframes spin-wheel-pop {
                    0% { transform: scale(0.6); opacity: 0; }
                    60% { transform: scale(1.08); opacity: 1; }
                    100% { transform: scale(1); opacity: 1; }
                }
                @keyframes spin-wheel-sparkle {
                    0%, 100% { opacity: 0; transform: scale(0.4) rotate(0deg); }
                    50% { opacity: 1; transform: scale(1) rotate(180deg); }
                }
            `}</style>

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
                <div className="relative flex flex-col items-center">
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

                    {/* Floating sparkles around the wheel */}
                    <div aria-hidden className="pointer-events-none absolute top-8 left-1/2 -translate-x-1/2">
                        {[
                            { top: 20, left: -150, delay: '0s' },
                            { top: 80, left: 150, delay: '0.6s' },
                            { top: 180, left: -170, delay: '1.2s' },
                            { top: 220, left: 160, delay: '0.3s' },
                            { top: 300, left: -130, delay: '0.9s' },
                            { top: 320, left: 140, delay: '1.5s' },
                        ].map((s, i) => (
                            <Star
                                key={i}
                                className="absolute size-4 fill-amber-300 text-amber-400"
                                style={{
                                    top: s.top,
                                    left: s.left,
                                    animation: `spin-wheel-sparkle 2.4s ${s.delay} ease-in-out infinite`,
                                }}
                            />
                        ))}
                    </div>

                    {/* Wheel cluster */}
                    <div className="relative">
                        {/* Glow halo while spinning */}
                        <div
                            aria-hidden
                            className="absolute inset-0 rounded-full"
                            style={{
                                animation: spinning
                                    ? 'spin-wheel-glow 1.2s ease-in-out infinite'
                                    : 'none',
                            }}
                        />

                        {/* Outer ring with light bulbs */}
                        <div className="relative aspect-square w-88 max-w-[92vw] rounded-full bg-linear-to-br from-amber-900 via-amber-800 to-amber-950 p-3 shadow-2xl ring-4 ring-amber-950/40">
                            {bulbs.map((angle, i) => (
                                <span
                                    key={i}
                                    aria-hidden
                                    className="absolute top-1/2 left-1/2 size-2.5 rounded-full bg-amber-200"
                                    style={{
                                        transform: `translate(-50%, -50%) rotate(${angle}deg) translateY(calc(-50% + 6px))`,
                                        transformOrigin: '50% 50%',
                                        animation: `spin-wheel-bulb 1.4s ease-in-out ${(i % 6) * 0.18}s infinite`,
                                    }}
                                />
                            ))}

                            {/* Pointer */}
                            <div
                                aria-hidden
                                className="absolute top-0 left-1/2 z-20"
                                style={{
                                    animation: spinning
                                        ? 'spin-wheel-pointer-shake 0.18s linear infinite'
                                        : 'spin-wheel-pointer-bob 1.8s ease-in-out infinite',
                                    transformOrigin: '50% 0%',
                                }}
                            >
                                <svg
                                    width="44"
                                    height="56"
                                    viewBox="0 0 44 56"
                                    style={{ filter: 'drop-shadow(0 4px 6px rgba(0,0,0,0.35))' }}
                                >
                                    <defs>
                                        <linearGradient id="pointer-grad" x1="0" y1="0" x2="0" y2="1">
                                            <stop offset="0%" stopColor="#fde68a" />
                                            <stop offset="55%" stopColor="#f59e0b" />
                                            <stop offset="100%" stopColor="#7c2d12" />
                                        </linearGradient>
                                    </defs>
                                    <path
                                        d="M22 56 L4 8 Q22 -2 40 8 Z"
                                        fill="url(#pointer-grad)"
                                        stroke="#7c2d12"
                                        strokeWidth="2"
                                    />
                                    <circle cx="22" cy="14" r="4" fill="#fef3c7" opacity="0.85" />
                                </svg>
                            </div>

                            {/* Wheel itself */}
                            <div
                                className="relative aspect-square size-full overflow-hidden rounded-full border-4 border-amber-100/60 shadow-inner"
                                style={{
                                    transform: `rotate(${rotation}deg)`,
                                    transition: spinning
                                        ? 'transform 4.4s cubic-bezier(0.16, 0.68, 0.18, 0.995)'
                                        : 'none',
                                    animation:
                                        !spinning && !done
                                            ? 'spin-wheel-idle 4.5s ease-in-out infinite'
                                            : 'none',
                                }}
                            >
                                <svg
                                    viewBox="-100 -100 200 200"
                                    className="size-full"
                                    aria-hidden
                                >
                                    <defs>
                                        <radialGradient id="wheel-shine" cx="50%" cy="50%" r="50%">
                                            <stop offset="0%" stopColor="rgba(255,255,255,0.18)" />
                                            <stop offset="70%" stopColor="rgba(255,255,255,0)" />
                                        </radialGradient>
                                    </defs>
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
                                        const labelRadius = s.image_path ? 82 : 65;
                                        const tx = labelRadius * Math.cos((midAngle * Math.PI) / 180);
                                        const ty = labelRadius * Math.sin((midAngle * Math.PI) / 180);
                                        const imgSize = Math.min(28, segmentAngle * 0.55);
                                        const ix = 52 * Math.cos((midAngle * Math.PI) / 180);
                                        const iy = 52 * Math.sin((midAngle * Math.PI) / 180);

                                        return (
                                            <g key={s.id}>
                                                <path
                                                    d={path}
                                                    fill={s.color}
                                                    stroke="rgba(255,255,255,0.85)"
                                                    strokeWidth={1.2}
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
                                                            filter:
                                                                'drop-shadow(0 1px 2px rgba(0,0,0,0.35))',
                                                        }}
                                                    />
                                                )}
                                                <text
                                                    x={tx}
                                                    y={ty}
                                                    textAnchor="middle"
                                                    dominantBaseline="middle"
                                                    fontSize={s.image_path ? '7.5' : '9'}
                                                    fontWeight="800"
                                                    fill="white"
                                                    transform={`rotate(${midAngle + 90} ${tx} ${ty})`}
                                                    style={{
                                                        textShadow:
                                                            '0 1px 2px rgba(0,0,0,0.5)',
                                                        letterSpacing: '0.02em',
                                                    }}
                                                >
                                                    {s.label}
                                                </text>
                                            </g>
                                        );
                                    })}
                                    <circle cx="0" cy="0" r="100" fill="url(#wheel-shine)" />
                                </svg>
                            </div>

                            {/* Centre cap */}
                            <div className="pointer-events-none absolute inset-0 flex items-center justify-center">
                                <div className="relative flex size-16 items-center justify-center rounded-full bg-linear-to-br from-amber-300 via-amber-500 to-amber-800 shadow-lg ring-4 ring-amber-100">
                                    <Sparkles className="size-7 text-white drop-shadow" />
                                    <span
                                        aria-hidden
                                        className="absolute top-1.5 left-2.5 size-3 rounded-full bg-white/60 blur-[2px]"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <Button
                        onClick={handleSpin}
                        disabled={spinning || done}
                        className="mt-8 w-full max-w-xs bg-linear-to-r from-amber-500 to-amber-700 text-white shadow-md transition-transform hover:scale-[1.02] hover:from-amber-600 hover:to-amber-800 disabled:opacity-60"
                    >
                        {spinning ? 'Spinning…' : done ? 'See you tomorrow!' : 'Spin the wheel'}
                    </Button>

                    {result && (
                        <div
                            className={`mt-6 w-full max-w-xs rounded-2xl border p-5 text-center shadow-lg ${
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
                        <p className="mt-4 w-full max-w-xs rounded-md border border-red-200 bg-red-50 px-3 py-2 text-center text-xs text-red-700">
                            {error}
                        </p>
                    )}
                </div>
            )}
        </StorefrontLayout>
    );
}
