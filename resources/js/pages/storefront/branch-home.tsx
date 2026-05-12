import { Head, router } from '@inertiajs/react';
import { Coffee, ShoppingBag } from 'lucide-react';
import { useEffect, useState } from 'react';
import { Badge } from '@/components/ui/badge';
import StorefrontLayout from '@/layouts/storefront-layout';
import { useBranchStore } from '@/stores/branch-store';
import { cn } from '@/lib/utils';

interface Slide {
    type: 'cover' | 'product';
    image: string | null;
    title: string;
    subtitle: string;
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
    categories: CategoryCard[];
}

export default function BranchHome({ branch, slides, categories }: Props) {
    const setBranch = useBranchStore((s) => s.setBranch);
    const [active, setActive] = useState(0);

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

    function pickCategory(slug: string) {
        router.visit(`/branches/${branch.id}/menu?category=${slug}`);
    }

    return (
        <StorefrontLayout>
            <Head title={branch.name} />

            <section className="mb-5 overflow-hidden rounded-2xl shadow-sm">
                <div className="relative h-44 sm:h-56">
                    {slides.map((slide, i) => (
                        <div
                            key={i}
                            className={cn(
                                'absolute inset-0 flex flex-col justify-end bg-gradient-to-t from-black/70 via-black/20 to-transparent transition-opacity duration-700',
                                active === i ? 'opacity-100' : 'opacity-0',
                            )}
                            style={{
                                backgroundImage: slide.image
                                    ? `linear-gradient(to top, rgba(0,0,0,0.65), rgba(0,0,0,0.1)), url(/storage/${slide.image})`
                                    : 'linear-gradient(135deg, #4A2C18, #6B4423)',
                                backgroundSize: 'cover',
                                backgroundPosition: 'center',
                            }}
                        >
                            <div className="p-5 text-white">
                                <p className="text-lg font-bold drop-shadow-md sm:text-xl">
                                    {slide.title}
                                </p>
                                <p className="text-xs opacity-90 sm:text-sm">{slide.subtitle}</p>
                            </div>
                        </div>
                    ))}
                </div>
                {slides.length > 1 && (
                    <div className="bg-card flex justify-center gap-1.5 py-2">
                        {slides.map((_, i) => (
                            <button
                                key={i}
                                type="button"
                                onClick={() => setActive(i)}
                                aria-label={`Slide ${i + 1}`}
                                className={cn(
                                    'h-1.5 rounded-full transition-all',
                                    active === i ? 'bg-primary w-5' : 'bg-muted-foreground/30 w-1.5',
                                )}
                            />
                        ))}
                    </div>
                )}
            </section>

            <div className="mb-3 flex items-center justify-between">
                <div>
                    <h2 className="text-base font-bold">Browse by Category</h2>
                    <p className="text-muted-foreground text-xs">
                        {branch.is_open_now ? (
                            <Badge variant="success" className="text-[10px]">
                                Open now
                            </Badge>
                        ) : (
                            <Badge variant="danger" className="text-[10px]">
                                Closed
                            </Badge>
                        )}
                    </p>
                </div>
                <button
                    type="button"
                    onClick={() => router.visit(`/branches/${branch.id}/menu`)}
                    className="text-primary text-xs font-semibold hover:underline"
                >
                    See all →
                </button>
            </div>

            {categories.length === 0 ? (
                <div className="border-border bg-card text-muted-foreground rounded-xl border border-dashed p-6 text-center text-sm">
                    No categories available yet.
                </div>
            ) : (
                <ul className="grid grid-cols-2 gap-3 sm:grid-cols-3">
                    {categories.map((cat) => (
                        <li key={cat.id}>
                            <button
                                type="button"
                                onClick={() => pickCategory(cat.slug)}
                                className="border-border bg-card group flex w-full flex-col items-center overflow-hidden rounded-2xl border text-left shadow-sm transition-all hover:-translate-y-0.5 hover:shadow-md"
                            >
                                <div className="bg-secondary/40 flex aspect-square w-full items-center justify-center overflow-hidden">
                                    {cat.image ? (
                                        <img
                                            src={`/storage/${cat.image}`}
                                            alt={cat.name}
                                            className="size-full object-cover transition-transform group-hover:scale-105"
                                        />
                                    ) : (
                                        <Coffee className="text-primary size-10" />
                                    )}
                                </div>
                                <div className="w-full p-3">
                                    <p className="text-sm font-semibold">{cat.name}</p>
                                    <p className="text-muted-foreground mt-0.5 flex items-center gap-1 text-[10px]">
                                        <ShoppingBag className="size-3" /> Order now
                                    </p>
                                </div>
                            </button>
                        </li>
                    ))}
                </ul>
            )}
        </StorefrontLayout>
    );
}
