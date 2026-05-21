import { Head } from '@inertiajs/react';
import { Coins, Sparkles } from 'lucide-react';
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

    const ALT_COLORS = ['#dc2626', '#facc15'];
    const segmentFill = (i: number) => ALT_COLORS[i % ALT_COLORS.length];
    const segmentTextColor = (i: number) => (i % 2 === 0 ? '#ffffff' : '#1f1300');
    const cardBg = (i: number) => (i % 2 === 0 ? '#fde68a' : '#fca5a5');

    const maxCharsPerLine = Math.max(7, Math.floor(80 / Math.max(1, segmentCount)));
    const baseFontSize =
        segmentCount <= 6 ? 10 : segmentCount <= 8 ? 8.5 : segmentCount <= 10 ? 7.5 : 6.8;

    function wrapLabel(label: string, maxChars: number, maxLines = 3): string[] {
        const words = label.split(/\s+/).filter(Boolean);
        const lines: string[] = [];
        let line = '';
        for (const word of words) {
            const next = line ? `${line} ${word}` : word;
            if (next.length <= maxChars) {
                line = next;
            } else {
                if (line) lines.push(line);
                line = word.length > maxChars ? word.slice(0, maxChars - 1) + '…' : word;
                if (lines.length >= maxLines) break;
            }
        }
        if (line && lines.length < maxLines) lines.push(line);
        return lines.slice(0, maxLines);
    }

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

    const bgConfetti = useMemo(() => {
        const colors = ['#ef4444', '#f59e0b', '#facc15', '#10b981', '#a855f7', '#ec4899', '#ffffff'];
        return Array.from({ length: 60 }, (_, i) => ({
            top: Math.random() * 100,
            left: Math.random() * 100,
            color: colors[i % colors.length],
            size: 3 + Math.random() * 4,
            rotate: Math.random() * 360,
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
            <Head title="Spin & Win" />

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
                @keyframes spin-wheel-pointer-bob {
                    0%, 100% { transform: translate(-50%, -2px); }
                    50% { transform: translate(-50%, 3px); }
                }
                @keyframes spin-wheel-pop {
                    0% { transform: scale(0.6); opacity: 0; }
                    60% { transform: scale(1.08); opacity: 1; }
                    100% { transform: scale(1); opacity: 1; }
                }
                @keyframes spin-wheel-idle {
                    0%, 100% { transform: rotate(-1.5deg); }
                    50% { transform: rotate(1.5deg); }
                }
                @keyframes spin-wheel-pulse {
                    0%, 100% { box-shadow: 0 0 0 0 rgba(255,255,255,0.7); }
                    70% { box-shadow: 0 0 0 18px rgba(255,255,255,0); }
                }
                .spin-stage {
                    background: linear-gradient(180deg, #7fc4e8 0%, #5fa9d4 100%);
                }
            `}</style>

            {segments.length === 0 ? (
                <div className="border-border bg-card rounded-2xl border border-dashed p-10 text-center text-sm text-neutral-500">
                    The wheel is being prepared. Check back soon!
                </div>
            ) : (
                <div className="spin-stage relative -mx-4 overflow-hidden rounded-3xl px-4 pt-5 pb-5 sm:-mx-6 sm:px-6">
                    {/* Background confetti specks */}
                    <div aria-hidden className="pointer-events-none absolute inset-0">
                        {bgConfetti.map((c, i) => (
                            <span
                                key={i}
                                className="absolute rounded-[1px]"
                                style={{
                                    top: `${c.top}%`,
                                    left: `${c.left}%`,
                                    width: `${c.size}px`,
                                    height: `${c.size * 0.4}px`,
                                    background: c.color,
                                    transform: `rotate(${c.rotate}deg)`,
                                    opacity: 0.7,
                                }}
                            />
                        ))}
                    </div>

                    {/* Hero header */}
                    <div className="relative mb-3 flex items-start justify-between gap-3 px-1">
                        <div>
                            <h1 className="text-3xl leading-tight font-extrabold text-amber-300 drop-shadow-md sm:text-4xl">
                                Spin &amp; Win
                            </h1>
                            <p className="mt-1 max-w-[14rem] text-xs leading-snug text-white/95 drop-shadow">
                                One free spin every day — points, vouchers, and surprise prizes.
                            </p>
                            <span className="mt-2 inline-flex items-center gap-1.5 rounded-full bg-white/95 px-3 py-1 text-[11px] font-bold text-neutral-800 shadow">
                                <Coins className="size-3.5 text-amber-500" /> 1 spin / day
                            </span>
                        </div>
                    </div>

                    {/* Winning confetti */}
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

                    {/* Wheel */}
                    <div className="relative mx-auto mt-2 w-fit">
                        {/* Pointer — beige downward arrow on top */}
                        <div
                            aria-hidden
                            className="absolute -top-1 left-1/2 z-20"
                            style={{
                                animation: spinning
                                    ? 'spin-wheel-pointer-shake 0.18s linear infinite'
                                    : 'spin-wheel-pointer-bob 1.8s ease-in-out infinite',
                                transform: 'translateX(-50%)',
                                transformOrigin: '50% 0%',
                            }}
                        >
                            <svg
                                width="44"
                                height="56"
                                viewBox="0 0 44 56"
                                style={{ filter: 'drop-shadow(0 4px 5px rgba(0,0,0,0.35))' }}
                            >
                                <defs>
                                    <linearGradient id="pointer-grad" x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="0%" stopColor="#fef3c7" />
                                        <stop offset="55%" stopColor="#fbbf24" />
                                        <stop offset="100%" stopColor="#92400e" />
                                    </linearGradient>
                                </defs>
                                <path
                                    d="M22 54 L6 10 Q22 1 38 10 Z"
                                    fill="url(#pointer-grad)"
                                    stroke="#5b2c0a"
                                    strokeWidth="1.8"
                                />
                                <circle cx="22" cy="14" r="3.5" fill="#fffbeb" opacity="0.85" />
                            </svg>
                        </div>

                        {/* Wheel disc */}
                        <div
                            className="relative aspect-square w-80 max-w-[86vw] overflow-hidden rounded-full border-[4px] border-black bg-white shadow-2xl"
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
                                    const labelRadius = s.image_path ? 50 : 62;
                                    const tx = labelRadius * Math.cos((midAngle * Math.PI) / 180);
                                    const ty = labelRadius * Math.sin((midAngle * Math.PI) / 180);
                                    const imgSize = Math.min(34, segmentAngle * 0.65);
                                    const ix = 76 * Math.cos((midAngle * Math.PI) / 180);
                                    const iy = 76 * Math.sin((midAngle * Math.PI) / 180);

                                    const fill = segmentFill(i);
                                    const txtColor = segmentTextColor(i);

                                    return (
                                        <g key={s.id}>
                                            <path
                                                d={path}
                                                fill={fill}
                                                stroke="#1a0f08"
                                                strokeWidth={1.6}
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
                                            {(() => {
                                                const lines = wrapLabel(s.label, maxCharsPerLine);
                                                const lineCount = lines.length;
                                                const fontSize =
                                                    lineCount === 3
                                                        ? baseFontSize * 0.85
                                                        : lineCount === 2
                                                          ? baseFontSize * 0.95
                                                          : baseFontSize;
                                                const firstDy = -(lineCount - 1) * 0.55;
                                                return (
                                                    <text
                                                        x={tx}
                                                        y={ty}
                                                        textAnchor="middle"
                                                        dominantBaseline="middle"
                                                        fontSize={fontSize}
                                                        fontWeight="800"
                                                        fill={txtColor}
                                                        transform={`rotate(${midAngle + 90} ${tx} ${ty})`}
                                                        style={{
                                                            textShadow:
                                                                txtColor === '#ffffff'
                                                                    ? '0 1px 2px rgba(0,0,0,0.55)'
                                                                    : 'none',
                                                            letterSpacing: '0.02em',
                                                        }}
                                                    >
                                                        {lines.map((line, li) => (
                                                            <tspan
                                                                key={li}
                                                                x={tx}
                                                                dy={`${li === 0 ? firstDy : 1.1}em`}
                                                            >
                                                                {line}
                                                            </tspan>
                                                        ))}
                                                    </text>
                                                );
                                            })()}
                                        </g>
                                    );
                                })}
                            </svg>
                        </div>

                        {/* Centre Spin button — clickable, stays still */}
                        <button
                            type="button"
                            onClick={handleSpin}
                            disabled={spinning || done}
                            aria-label="Spin"
                            className="absolute top-1/2 left-1/2 z-10 flex size-24 -translate-x-1/2 -translate-y-1/2 flex-col items-center justify-center rounded-full border-[3px] border-black bg-white shadow-xl transition-transform active:scale-95 disabled:opacity-70 disabled:active:scale-100"
                            style={{
                                animation:
                                    !spinning && !done
                                        ? 'spin-wheel-pulse 1.8s ease-in-out infinite'
                                        : 'none',
                            }}
                        >
                            <span className="text-xl leading-none font-extrabold tracking-wide text-neutral-900">
                                {spinning ? '…' : done ? '✓' : 'Spin'}
                            </span>
                            {!spinning && !done && (
                                <span className="mt-0.5 flex items-center gap-0.5 text-[10px] font-semibold text-neutral-500">
                                    1 / day
                                </span>
                            )}
                        </button>
                    </div>

                    {result && (
                        <div
                            className={`mx-auto mt-5 w-full max-w-xs rounded-2xl border p-4 text-center shadow-lg ${
                                won
                                    ? 'border-amber-300 bg-linear-to-br from-amber-50 via-yellow-50 to-amber-100'
                                    : 'border-neutral-200 bg-white'
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
                                className={`mt-1 text-xl font-extrabold ${
                                    won ? 'text-amber-900' : 'text-neutral-700'
                                }`}
                            >
                                {result.label}
                            </p>
                            <p className="text-muted-foreground mt-1 text-xs">{result.message}</p>
                        </div>
                    )}

                    {error && (
                        <p className="mx-auto mt-3 w-full max-w-xs rounded-md border border-red-200 bg-red-50 px-3 py-2 text-center text-xs text-red-700">
                            {error}
                        </p>
                    )}
                </div>
            )}

            {/* Prizes to win — horizontal scrollable list */}
            {segments.length > 0 && (
                <section className="mt-5">
                    <h2 className="mb-2 px-1 text-sm font-bold text-neutral-800">Prizes to win</h2>
                    <div className="-mx-1 flex snap-x snap-mandatory gap-2.5 overflow-x-auto px-1 pb-2">
                        {segments.map((s, i) => (
                            <div
                                key={s.id}
                                className="flex w-28 shrink-0 snap-start flex-col items-center gap-2 rounded-2xl border border-black/10 p-3 shadow-sm"
                                style={{ background: cardBg(i) }}
                            >
                                <div className="flex size-14 items-center justify-center overflow-hidden rounded-xl bg-white/60">
                                    {s.image_path ? (
                                        <img
                                            src={`/storage/${s.image_path}`}
                                            alt={s.label}
                                            className="size-full object-cover"
                                        />
                                    ) : (
                                        <Sparkles className="size-6 text-neutral-700" />
                                    )}
                                </div>
                                <p className="line-clamp-2 text-center text-[11px] leading-tight font-semibold text-neutral-900">
                                    {s.label}
                                </p>
                            </div>
                        ))}
                    </div>
                </section>
            )}
        </StorefrontLayout>
    );
}
