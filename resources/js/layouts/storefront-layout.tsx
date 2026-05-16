import { Link, usePage } from '@inertiajs/react';
import { Heart, Home, ShoppingBag, Sparkles, Trophy, User, Wallet } from 'lucide-react';
import { type ReactNode } from 'react';
import { Badge } from '@/components/ui/badge';
import { InstallPrompt } from '@/components/storefront/install-prompt';
import { useBranchStore } from '@/stores/branch-store';
import { cartTotals, useCartStore } from '@/stores/cart-store';
import { cn } from '@/lib/utils';

interface CustomerStats {
    wallet_balance: number;
    points: number;
    tier: { name: string; color: string | null; multiplier: number } | null;
}

interface Props {
    children: ReactNode;
    showBranchPicker?: boolean;
}

export default function StorefrontLayout({ children, showBranchPicker = true }: Props) {
    const { auth, url, customer_stats } = usePage().props as unknown as {
        auth: { user: { name: string } | null };
        url: string;
        customer_stats: CustomerStats | null;
    };
    const branch = useBranchStore((s) => s.selected);
    const lines = useCartStore((s) => s.lines);
    const { itemCount } = cartTotals(lines);
    const path = typeof window !== 'undefined' ? window.location.pathname : url;

    return (
        <div className="bg-background text-card-foreground flex min-h-screen flex-col pb-16">
            <header className="border-border bg-card/95 sticky top-0 z-30 border-b backdrop-blur">
                <div className="mx-auto flex max-w-3xl items-center justify-between gap-3 px-4 py-3">
                    <Link
                        href={branch ? `/branches/${branch.id}` : '/'}
                        className="flex items-center gap-2 font-semibold"
                    >
                        <img
                            src="/images/logo.jpg"
                            alt="Star Coffee"
                            className="size-9 rounded-full object-cover"
                        />
                        <span className="hidden text-sm sm:inline">Star Coffee</span>
                    </Link>
                    {showBranchPicker && (
                        <Link
                            href="/branches"
                            className="bg-secondary hover:bg-secondary/80 flex flex-1 items-center justify-center gap-2 rounded-full px-3 py-1.5 text-xs font-medium"
                        >
                            {branch ? (
                                <>
                                    <span className="truncate">{branch.name}</span>
                                    <Badge
                                        variant={branch.is_open_now ? 'success' : 'danger'}
                                        className="text-[10px]"
                                    >
                                        {branch.is_open_now ? 'Open' : 'Closed'}
                                    </Badge>
                                </>
                            ) : (
                                <span>Pick a branch</span>
                            )}
                        </Link>
                    )}
                    <div className="flex items-center gap-2 text-sm">
                        {auth.user && (
                            <span className="text-muted-foreground hidden text-xs sm:inline">
                                {auth.user.name}
                            </span>
                        )}
                        <Link
                            href={auth.user ? '/profile' : '/login'}
                            className="bg-primary/10 text-primary flex size-9 items-center justify-center rounded-full"
                            aria-label={auth.user ? 'Profile' : 'Login'}
                        >
                            <User className="size-4" />
                        </Link>
                    </div>
                </div>

                {auth.user && customer_stats && (
                    <div className="border-border border-t bg-gradient-to-r from-amber-50 to-orange-50 dark:from-amber-950/40 dark:to-orange-950/40">
                        <div className="mx-auto flex max-w-3xl items-center justify-between gap-2 px-4 py-2 text-xs">
                            <Link
                                href="/wallet"
                                className="flex items-center gap-1.5 font-semibold text-amber-900 transition-opacity hover:opacity-75 dark:text-amber-200"
                            >
                                <Wallet className="size-3.5" />
                                <span>RM{customer_stats.wallet_balance.toFixed(2)}</span>
                            </Link>
                            <Link
                                href="/loyalty"
                                className="flex items-center gap-1.5 font-semibold text-amber-900 transition-opacity hover:opacity-75 dark:text-amber-200"
                            >
                                <Sparkles className="size-3.5" />
                                <span>{customer_stats.points.toLocaleString()} pts</span>
                            </Link>
                            {customer_stats.tier && (
                                <Link
                                    href="/loyalty"
                                    className="flex items-center gap-1.5 rounded-full bg-white/70 px-2.5 py-0.5 font-bold uppercase tracking-wide text-amber-800 shadow-sm transition-opacity hover:opacity-75 dark:bg-amber-900/50 dark:text-amber-100"
                                    style={
                                        customer_stats.tier.color
                                            ? { borderLeft: `3px solid ${customer_stats.tier.color}` }
                                            : undefined
                                    }
                                >
                                    <Trophy className="size-3" />
                                    <span>{customer_stats.tier.name}</span>
                                </Link>
                            )}
                        </div>
                    </div>
                )}
            </header>

            <main className="bg-card mx-auto w-full max-w-3xl flex-1 px-4 py-4">{children}</main>

            <InstallPrompt />

            <nav className="border-border bg-card fixed inset-x-0 bottom-0 z-30 border-t">
                <div className="mx-auto grid max-w-3xl grid-cols-5 text-xs">
                    <NavItem
                        href={branch ? `/branches/${branch.id}` : '/'}
                        icon={<Home className="size-5" />}
                        label="Home"
                        active={path === '/' || (branch ? path === `/branches/${branch.id}` : false)}
                    />
                    <NavItem
                        href={branch ? `/branches/${branch.id}/menu` : '/branches'}
                        icon={<ShoppingBag className="size-5" />}
                        label="Order"
                        active={path.includes('/menu')}
                    />
                    <NavItem
                        href={auth.user ? '/wallet' : '/login'}
                        icon={<Wallet className="size-5" />}
                        label="Wallet"
                        active={path === '/wallet'}
                    />
                    <NavItem
                        href="/loyalty"
                        icon={<Heart className="size-5" />}
                        label="Loyalty"
                        active={path === '/loyalty'}
                    />
                    <Link
                        href={branch ? `/branches/${branch.id}/cart` : '/branches'}
                        className={cn(
                            'relative flex flex-col items-center gap-1 py-2.5 transition-colors',
                            path.endsWith('/cart')
                                ? 'text-primary'
                                : 'text-muted-foreground hover:text-foreground',
                        )}
                    >
                        <span className="relative">
                            <ShoppingBag className="size-5" />
                            {itemCount > 0 && (
                                <span className="bg-primary text-primary-foreground absolute -top-1.5 -right-2 flex size-4 items-center justify-center rounded-full text-[9px] font-semibold">
                                    {itemCount}
                                </span>
                            )}
                        </span>
                        <span>Cart</span>
                    </Link>
                </div>
            </nav>
        </div>
    );
}

function NavItem({
    href,
    icon,
    label,
    active,
}: {
    href: string;
    icon: ReactNode;
    label: string;
    active: boolean;
}) {
    return (
        <Link
            href={href}
            className={cn(
                'flex flex-col items-center gap-1 py-2.5 transition-colors',
                active ? 'text-primary' : 'text-muted-foreground hover:text-foreground',
            )}
        >
            {icon}
            <span>{label}</span>
        </Link>
    );
}
