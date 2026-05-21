import { Star } from 'lucide-react';
import { useEffect, useState } from 'react';
import { cn } from '@/lib/utils';

interface Review {
    id: number;
    rating: number;
    comment: string | null;
    user_name: string;
    created_at: string | null;
}

interface ReviewsPayload {
    avg_rating: number;
    reviews_count: number;
    reviews: Review[];
}

interface Props {
    productId: number;
}

export function ProductReviewsSlider({ productId }: Props) {
    const [data, setData] = useState<ReviewsPayload | null>(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        let cancelled = false;
        setLoading(true);
        fetch(`/products/${productId}/reviews`, {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        })
            .then((r) => (r.ok ? r.json() : Promise.reject(r.status)))
            .then((json: ReviewsPayload) => {
                if (!cancelled) setData(json);
            })
            .catch(() => {
                if (!cancelled) setData(null);
            })
            .finally(() => {
                if (!cancelled) setLoading(false);
            });
        return () => {
            cancelled = true;
        };
    }, [productId]);

    if (loading) return null;
    if (!data || data.reviews_count === 0) return null;

    return (
        <section className="border-border bg-card rounded-xl border p-3 shadow-sm">
            <div className="mb-2 flex items-center justify-between">
                <div className="flex items-center gap-1.5">
                    <Star className="size-4 fill-amber-400 text-amber-400" />
                    <span className="text-sm font-bold">{data.avg_rating.toFixed(1)}</span>
                    <span className="text-muted-foreground text-xs">
                        ({data.reviews_count} {data.reviews_count === 1 ? 'review' : 'reviews'})
                    </span>
                </div>
            </div>

            <div className="-mx-1 flex snap-x snap-mandatory gap-2 overflow-x-auto px-1 pb-1">
                {data.reviews.map((r) => (
                    <ReviewCard key={r.id} review={r} />
                ))}
            </div>
        </section>
    );
}

function ReviewCard({ review }: { review: Review }) {
    return (
        <div className="border-border bg-background flex w-60 shrink-0 snap-start flex-col gap-1.5 rounded-lg border p-3 sm:w-72">
            <div className="flex items-center justify-between">
                <p className="line-clamp-1 text-xs font-semibold text-neutral-800">
                    {review.user_name}
                </p>
                <div className="flex items-center gap-0.5">
                    {[1, 2, 3, 4, 5].map((n) => (
                        <Star
                            key={n}
                            className={cn(
                                'size-3',
                                n <= review.rating
                                    ? 'fill-amber-400 text-amber-400'
                                    : 'text-neutral-300',
                            )}
                            strokeWidth={1.5}
                        />
                    ))}
                </div>
            </div>
            {review.comment && (
                <p className="line-clamp-3 text-[11px] leading-snug text-neutral-700">
                    {review.comment}
                </p>
            )}
            {review.created_at && (
                <p className="text-muted-foreground text-[10px]">
                    {new Date(review.created_at).toLocaleDateString()}
                </p>
            )}
        </div>
    );
}
