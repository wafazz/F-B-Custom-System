import { Star } from 'lucide-react';
import { useState } from 'react';
import { cn } from '@/lib/utils';

interface Props {
    endpoint: string;
    label: string;
    onDone?: () => void;
}

export function ReviewForm({ endpoint, label, onDone }: Props) {
    const [rating, setRating] = useState(0);
    const [hover, setHover] = useState(0);
    const [comment, setComment] = useState('');
    const [busy, setBusy] = useState(false);
    const [done, setDone] = useState(false);
    const [error, setError] = useState<string | null>(null);

    async function submit() {
        if (rating < 1 || busy) return;
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
            const res = await fetch(endpoint, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-XSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ rating, comment: comment.trim() || null }),
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok) {
                setError((data as { message?: string }).message ?? `HTTP ${res.status}`);
                return;
            }
            setDone(true);
            onDone?.();
        } catch (e) {
            setError(e instanceof Error ? e.message : String(e));
        } finally {
            setBusy(false);
        }
    }

    if (done) {
        return (
            <p className="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-center text-xs text-emerald-700">
                Thanks for rating!
            </p>
        );
    }

    const active = hover || rating;

    return (
        <div className="border-border bg-card rounded-lg border p-3">
            <p className="mb-1.5 text-xs font-semibold text-neutral-700">{label}</p>
            <div className="mb-2 flex items-center gap-1">
                {[1, 2, 3, 4, 5].map((n) => (
                    <button
                        key={n}
                        type="button"
                        onClick={() => setRating(n)}
                        onMouseEnter={() => setHover(n)}
                        onMouseLeave={() => setHover(0)}
                        aria-label={`Rate ${n}`}
                        className="transition-transform active:scale-90"
                    >
                        <Star
                            className={cn(
                                'size-6 transition-colors',
                                n <= active
                                    ? 'fill-amber-400 text-amber-400'
                                    : 'text-neutral-300',
                            )}
                            strokeWidth={1.5}
                        />
                    </button>
                ))}
                {rating > 0 && (
                    <span className="ml-2 text-xs font-semibold text-amber-700">
                        {rating}/5
                    </span>
                )}
            </div>
            <textarea
                value={comment}
                onChange={(e) => setComment(e.target.value)}
                maxLength={500}
                rows={2}
                placeholder="Optional comment…"
                className="border-border w-full rounded-md border bg-white p-2 text-xs focus:border-amber-400 focus:outline-none"
            />
            <div className="mt-2 flex items-center justify-between gap-2">
                {error && <p className="text-[11px] text-red-600">{error}</p>}
                <button
                    type="button"
                    onClick={submit}
                    disabled={busy || rating < 1}
                    className="bg-primary text-primary-foreground ml-auto rounded-full px-3 py-1.5 text-[11px] font-bold tracking-wider uppercase shadow disabled:opacity-50"
                >
                    {busy ? 'Submitting…' : 'Submit'}
                </button>
            </div>
        </div>
    );
}
