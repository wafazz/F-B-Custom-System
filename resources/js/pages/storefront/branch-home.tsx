import { Head, Link, router } from '@inertiajs/react';
import { ArrowRight, Coffee } from 'lucide-react';
import { useEffect, useState } from 'react';
import StorefrontLayout from '@/layouts/storefront-layout';
import { useBranchStore } from '@/stores/branch-store';
import { cn } from '@/lib/utils';

interface Slide {
    type: 'cover' | 'product' | 'managed';
    image: string | null;
    title: string;
    subtitle: string | null;
    cta_label: string | null;
    cta_url: string | null;
}

interface CategoryCard {
    id: number;
    slug: string;
    name: string;
    image: string | null;
    icon: string | null;
}

interface BranchInfo {
    id: number;
    code: string;
    name: string;
    logo: string | null;
    is_open_now: boolean;
    accepts_orders: boolean;
}

interface Props {
    branch: BranchInfo;
    slides: Slide[];
    rewards_slides: Slide[];
    categories: CategoryCard[];
}

function resolveCtaUrl(url: string, branchId: number): string {
    if (/^(https?:)?\/\//i.test(url)) return url;
    if (url.startsWith('/')) return url;
    return `/branches/${branchId}/${url}`;
}

export default function BranchHome({ branch, slides, rewards_slides, categories }: Props) {
    const setBranch = useBranchStore((s) => s.setBranch);
    const [active, setActive] = useState(0);
    const [rewardsActive, setRewardsActive] = useState(0);

    useEffect(() => {
        setBranch({
            id: branch.id,
            code: branch.code,
            name: branch.name,
            address: '',
            city: '',
            state: '',
            phone: '',
            latitude: null,
            longitude: null,
            operating_hours: null,
            logo: branch.logo,
            is_open_now: branch.is_open_now,
        });
    }, [branch, setBranch]);

    useEffect(() => {
        if (slides.length <= 1) return;
        const t = window.setInterval(() => setActive((i) => (i + 1) % slides.length), 4500);
        return () => window.clearInterval(t);
    }, [slides.length]);

    useEffect(() => {
        if (rewards_slides.length <= 1) return;
        const t = window.setInterval(
            () => setRewardsActive((i) => (i + 1) % rewards_slides.length),
            5500,
        );
        return () => window.clearInterval(t);
    }, [rewards_slides.length]);

    const slide = slides[active] ?? slides[0];

    return (
        <StorefrontLayout>
            <Head title={branch.name} />

            {/* Hero carousel */}
            {slide && (
                <section className="-mt-1 mb-6 overflow-hidden rounded-2xl shadow-md">
                    <div className="relative h-52 sm:h-60">
                        {slides.map((s, i) => (
                            <div
                                key={i}
                                className={cn(
                                    'absolute inset-0 transition-opacity duration-700',
                                    active === i ? 'opacity-100' : 'opacity-0',
                                )}
                                style={{
                                    backgroundImage: s.image
                                        ? `linear-gradient(110deg, rgba(20,15,12,0.85) 0%, rgba(20,15,12,0.55) 45%, rgba(20,15,12,0.1) 70%), url(/storage/${s.image})`
                                        : 'linear-gradient(135deg, #2a1d14, #4a2c18)',
                                    backgroundSize: 'cover',
                                    backgroundPosition: 'center',
                                }}
                            >
                                <div className="flex h-full flex-col justify-center p-6 text-white">
                                    <p className="text-2xl font-bold leading-none drop-shadow-lg sm:text-3xl">
                                        {s.title.split(' ')[0]}
                                    </p>
                                    <p className="-mt-1 font-serif text-3xl italic text-amber-100 drop-shadow-lg sm:text-4xl">
                                        {s.title.split(' ').slice(1).join(' ') || ''}
                                    </p>
                                    {s.subtitle && (
                                        <p className="mt-3 max-w-[55%] text-xs leading-snug text-amber-50/90 sm:text-sm">
                                            {s.subtitle}
                                        </p>
                                    )}
                                    {s.cta_label && s.cta_url && (
                                        <a
                                            href={resolveCtaUrl(s.cta_url, branch.id)}
                                            className="mt-4 inline-flex w-fit items-center gap-1.5 rounded-full bg-white px-4 py-2 text-[11px] font-bold uppercase tracking-wider text-neutral-900 shadow transition-transform hover:scale-105"
                                        >
                                            {s.cta_label} <ArrowRight className="size-3" />
                                        </a>
                                    )}
                                </div>
                            </div>
                        ))}

                        {slides.length > 1 && (
                            <div className="absolute bottom-2 left-1/2 z-10 flex -translate-x-1/2 gap-1.5">
                                {slides.map((_, i) => (
                                    <button
                                        key={i}
                                        type="button"
                                        onClick={() => setActive(i)}
                                        aria-label={`Slide ${i + 1}`}
                                        className={cn(
                                            'h-1.5 rounded-full transition-all',
                                            active === i ? 'w-5 bg-white' : 'w-1.5 bg-white/50',
                                        )}
                                    />
                                ))}
                            </div>
                        )}
                    </div>
                </section>
            )}

            {/* Explore Our Menu */}
            <section className="mb-6">
                <div className="mb-3 flex items-center justify-between">
                    <h2 className="text-card-foreground text-lg font-bold">Explore Our Menu</h2>
                    <button
                        type="button"
                        onClick={() => router.visit(`/branches/${branch.id}/menu`)}
                        className="text-muted-foreground flex items-center gap-1 text-xs font-medium hover:text-amber-700"
                    >
                        See all <ArrowRight className="size-3" />
                    </button>
                </div>

                {categories.length === 0 ? (
                    <div className="border-border bg-card text-muted-foreground rounded-xl border border-dashed p-6 text-center text-sm">
                        No categories yet.
                    </div>
                ) : (
                    <div className="-mx-1 flex snap-x snap-mandatory gap-3 overflow-x-auto px-1 pb-2">
                        {categories.map((cat) => (
                            <button
                                key={cat.id}
                                type="button"
                                onClick={() =>
                                    router.visit(`/branches/${branch.id}/menu?category=${cat.slug}`)
                                }
                                className="group flex w-20 shrink-0 snap-start flex-col items-center gap-2"
                            >
                                <div className="flex aspect-square w-full items-center justify-center overflow-hidden rounded-2xl bg-amber-50 shadow-sm ring-1 ring-amber-100 transition-transform group-hover:-translate-y-0.5 group-hover:shadow">
                                    {cat.image ? (
                                        <img
                                            src={`/storage/${cat.image}`}
                                            alt={cat.name}
                                            className="size-full object-cover"
                                        />
                                    ) : (
                                        <Coffee className="size-7 text-amber-700" />
                                    )}
                                </div>
                                <span className="text-card-foreground line-clamp-1 text-center text-xs font-semibold">
                                    {cat.name}
                                </span>
                            </button>
                        ))}
                    </div>
                )}
            </section>

            {/* Rewards / promo carousel */}
            {rewards_slides.length > 0 && (
                <section className="mb-2">
                    <div className="relative overflow-hidden rounded-2xl shadow-md">
                        <div className="relative h-32 sm:h-36">
                            {rewards_slides.map((r, i) => {
                                const href = r.cta_url
                                    ? resolveCtaUrl(r.cta_url, branch.id)
                                    : '/loyalty';
                                return (
                                    <Link
                                        key={i}
                                        href={href}
                                        className={cn(
                                            'absolute inset-0 flex transition-opacity duration-700',
                                            rewardsActive === i ? 'opacity-100' : 'pointer-events-none opacity-0',
                                        )}
                                        style={{
                                            background:
                                                'linear-gradient(135deg, #78350f 0%, #92400e 55%, #451a03 100%)',
                                        }}
                                    >
                                        <div className="relative z-10 flex flex-1 flex-col justify-center p-5 text-white">
                                            <p className="text-base font-bold leading-tight sm:text-lg">
                                                {r.title}
                                            </p>
                                            {r.subtitle && (
                                                <p className="mt-1 max-w-[75%] text-[11px] text-amber-100/90 sm:text-xs">
                                                    {r.subtitle}
                                                </p>
                                            )}
                                            <span className="mt-3 inline-flex w-fit items-center gap-1.5 rounded-full bg-white px-3.5 py-1.5 text-[11px] font-bold uppercase tracking-wider text-amber-900 shadow">
                                                {r.cta_label ?? 'View rewards'}
                                                <ArrowRight className="size-3" />
                                            </span>
                                        </div>
                                        <div
                                            className="pointer-events-none absolute inset-y-0 right-0 w-2/5 opacity-90"
                                            style={{
                                                backgroundImage: r.image
                                                    ? `url(/storage/${r.image})`
                                                    : "url('/images/logo.jpg')",
                                                backgroundSize: 'cover',
                                                backgroundPosition: 'center',
                                                maskImage:
                                                    'linear-gradient(to left, black 35%, transparent 100%)',
                                                WebkitMaskImage:
                                                    'linear-gradient(to left, black 35%, transparent 100%)',
                                            }}
                                        />
                                    </Link>
                                );
                            })}
                        </div>

                        {rewards_slides.length > 1 && (
                            <div className="absolute bottom-2 left-1/2 z-20 flex -translate-x-1/2 gap-1.5">
                                {rewards_slides.map((_, i) => (
                                    <button
                                        key={i}
                                        type="button"
                                        onClick={() => setRewardsActive(i)}
                                        aria-label={`Promo slide ${i + 1}`}
                                        className={cn(
                                            'h-1.5 rounded-full transition-all',
                                            rewardsActive === i
                                                ? 'w-5 bg-white'
                                                : 'w-1.5 bg-white/50',
                                        )}
                                    />
                                ))}
                            </div>
                        )}
                    </div>
                </section>
            )}
        </StorefrontLayout>
    );
}
