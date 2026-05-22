import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Star } from 'lucide-react';
import StorefrontLayout from '@/layouts/storefront-layout';
import { cn } from '@/lib/utils';

interface Review {
    id: number;
    rating: number;
    comment: string | null;
    user_name: string;
    created_at: string | null;
}

interface Props {
    branch: {
        id: number;
        name: string;
        avg_rating: number;
        reviews_count: number;
    };
    reviews: Review[];
}

export default function BranchReviews({ branch, reviews }: Props) {
    const buckets = [5, 4, 3, 2, 1].map((stars) => ({
        stars,
        count: reviews.filter((r) => r.rating === stars).length,
    }));
    const max = Math.max(1, ...buckets.map((b) => b.count));

    return (
        <StorefrontLayout>
            <Head title={`${branch.name} — Reviews`} />

            <div className="mb-3 flex items-center gap-2">
                <Link
                    href={`/branches/${branch.id}`}
                    className="text-muted-foreground hover:text-primary flex size-8 items-center justify-center rounded-full"
                    aria-label="Back"
                >
                    <ArrowLeft className="size-4" />
                </Link>
                <h1 className="text-lg font-bold">{branch.name}</h1>
            </div>

            <section className="border-border mb-5 rounded-2xl border bg-linear-to-br from-amber-50 to-orange-100 p-5 shadow-sm">
                <div className="flex items-center gap-4">
                    <div className="flex flex-col items-center">
                        <p className="text-4xl leading-none font-extrabold text-amber-700">
                            {branch.avg_rating.toFixed(1)}
                        </p>
                        <div className="mt-1 flex items-center gap-0.5">
                            {[1, 2, 3, 4, 5].map((n) => (
                                <Star
                                    key={n}
                                    className={cn(
                                        'size-3.5',
                                        n <= Math.round(branch.avg_rating)
                                            ? 'fill-amber-400 text-amber-400'
                                            : 'text-neutral-300',
                                    )}
                                    strokeWidth={1.5}
                                />
                            ))}
                        </div>
                        <p className="text-muted-foreground mt-1 text-[10px]">
                            {branch.reviews_count}{' '}
                            {branch.reviews_count === 1 ? 'review' : 'reviews'}
                        </p>
                    </div>

                    <div className="flex-1 space-y-1">
                        {buckets.map((b) => (
                            <div key={b.stars} className="flex items-center gap-2 text-[11px]">
                                <span className="w-3 text-right font-semibold text-neutral-700">
                                    {b.stars}
                                </span>
                                <Star
                                    className="size-3 fill-amber-400 text-amber-400"
                                    strokeWidth={1.5}
                                />
                                <div className="h-1.5 flex-1 overflow-hidden rounded-full bg-white/70">
                                    <div
                                        className="h-full bg-amber-400"
                                        style={{ width: `${(b.count / max) * 100}%` }}
                                    />
                                </div>
                                <span className="text-muted-foreground w-6 text-right">
                                    {b.count}
                                </span>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            <h2 className="mb-2 text-sm font-semibold">All reviews</h2>
            {reviews.length === 0 ? (
                <div className="border-border bg-card text-muted-foreground rounded-xl border border-dashed p-8 text-center text-sm">
                    No reviews yet. Be the first to leave one after your next order!
                </div>
            ) : (
                <ul className="space-y-2">
                    {reviews.map((r) => (
                        <li
                            key={r.id}
                            className="border-border bg-card rounded-xl border p-4 shadow-sm"
                        >
                            <div className="mb-1.5 flex items-center justify-between">
                                <p className="text-sm font-semibold text-neutral-800">
                                    {r.user_name}
                                </p>
                                <div className="flex items-center gap-0.5">
                                    {[1, 2, 3, 4, 5].map((n) => (
                                        <Star
                                            key={n}
                                            className={cn(
                                                'size-3.5',
                                                n <= r.rating
                                                    ? 'fill-amber-400 text-amber-400'
                                                    : 'text-neutral-300',
                                            )}
                                            strokeWidth={1.5}
                                        />
                                    ))}
                                </div>
                            </div>
                            {r.comment && (
                                <p className="text-xs leading-relaxed text-neutral-700">
                                    {r.comment}
                                </p>
                            )}
                            {r.created_at && (
                                <p className="text-muted-foreground mt-1.5 text-[10px]">
                                    {new Date(r.created_at).toLocaleDateString()}
                                </p>
                            )}
                        </li>
                    ))}
                </ul>
            )}
        </StorefrontLayout>
    );
}
