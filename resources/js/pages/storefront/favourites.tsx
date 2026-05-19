import { Head, Link } from '@inertiajs/react';
import { Coffee, Heart } from 'lucide-react';
import StorefrontLayout from '@/layouts/storefront-layout';
import { useBranchStore } from '@/stores/branch-store';

interface FavouriteProduct {
    id: number;
    name: string;
    slug: string;
    image: string | null;
    price: number;
}

interface Props {
    products: FavouriteProduct[];
}

export default function Favourites({ products }: Props) {
    const branch = useBranchStore((s) => s.selected);
    const menuHref = branch ? `/branches/${branch.id}/menu` : '/branches';

    return (
        <StorefrontLayout hideStats>
            <Head title="Favourites" />

            <div className="mb-4">
                <h1 className="text-xl font-bold">Your favourites</h1>
                <p className="text-muted-foreground text-xs">
                    {products.length === 0
                        ? 'Tap the heart on any menu item to save it here.'
                        : `${products.length} saved item${products.length === 1 ? '' : 's'}`}
                </p>
            </div>

            {products.length === 0 ? (
                <div className="border-border bg-card rounded-2xl border border-dashed py-12 text-center">
                    <Heart className="text-muted-foreground mx-auto mb-3 size-10" />
                    <p className="text-card-foreground text-sm font-semibold">No favourites yet</p>
                    <p className="text-muted-foreground mx-auto mt-1 max-w-xs text-xs">
                        Browse the menu and tap the heart on anything you want to order again.
                    </p>
                    <Link
                        href={menuHref}
                        className="bg-primary text-primary-foreground mt-4 inline-flex items-center gap-1.5 rounded-full px-4 py-2 text-xs font-bold"
                    >
                        Browse menu
                    </Link>
                </div>
            ) : (
                <div className="grid grid-cols-2 gap-2.5 sm:gap-3">
                    {products.map((p) => (
                        <Link
                            key={p.id}
                            href={`${menuHref}?product=${p.id}`}
                            className="group border-border bg-card flex h-full w-full flex-col rounded-xl border p-2.5 text-left shadow-sm transition-all hover:-translate-y-0.5 hover:shadow-md"
                        >
                            <div className="bg-secondary relative aspect-square w-full flex-shrink-0 overflow-hidden rounded-lg">
                                {p.image ? (
                                    <img
                                        src={`/storage/${p.image}`}
                                        alt={p.name}
                                        className="size-full object-cover"
                                    />
                                ) : (
                                    <div className="text-muted-foreground flex size-full items-center justify-center">
                                        <Coffee className="size-8" />
                                    </div>
                                )}
                                <span className="absolute top-1.5 right-1.5 flex size-7 items-center justify-center rounded-full bg-white/85 text-rose-500 shadow backdrop-blur-sm">
                                    <Heart className="size-3.5 fill-rose-500" />
                                </span>
                            </div>
                            <div className="mt-2 flex flex-1 flex-col">
                                <h3 className="line-clamp-2 text-sm leading-tight font-semibold">
                                    {p.name}
                                </h3>
                                <p className="text-primary mt-auto pt-2 text-sm font-bold">
                                    RM{p.price.toFixed(2)}
                                </p>
                            </div>
                        </Link>
                    ))}
                </div>
            )}
        </StorefrontLayout>
    );
}
