import { Link, usePage } from '@inertiajs/react';
import { Coffee, Crown, Gift, ReceiptText, Home, ShoppingBag, User, Wallet } from 'lucide-react';
import { type ReactNode } from 'react';
import { Badge } from '@/components/ui/badge';
import { InstallPrompt } from '@/components/storefront/install-prompt';
import { useBranchStore } from '@/stores/branch-store';
import { cartTotals, useCartStore } from '@/stores/cart-store';
import { cn } from '@/lib/utils';

interface CustomerStats {
    wallet_balance: number;
    points: number;
    lifetime_spend: number;
    tier: { name: string; color: string | null; multiplier: number } | null;
    next_tier: { name: string; min_spend: number } | null;
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

            </header>

            {auth.user && customer_stats && (
                <CustomerStatsStrip stats={customer_stats} />
            )}

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
                        href={auth.user ? '/orders' : '/login?redirect=/orders'}
                        icon={<ReceiptText className="size-5" />}
                        label="My Orders"
                        active={path === '/orders' || path.startsWith('/orders/')}
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

function CustomerStatsStrip({ stats }: { stats: CustomerStats }) {
    const tierProgress = stats.next_tier
        ? `RM${stats.lifetime_spend.toFixed(0)} / RM${stats.next_tier.min_spend.toFixed(0)}`
        : 'MAX';
    const tierName = stats.tier?.name ?? 'Bronze';

    return (
        <div className="bg-white shadow-sm dark:bg-neutral-900">
            <div className="mx-auto w-full max-w-3xl px-4 py-4">
                <div className="grid grid-cols-3 gap-2">
                <StatCard
                    href="/wallet"
                    chip={{ label: 'Top up', color: 'bg-amber-700 text-amber-50' }}
                    label="Wallet (RM)"
                    value={stats.wallet_balance.toFixed(2)}
                    icon={<Coffee className="size-5 text-amber-700/60" />}
                />
                <StatCard
                    href="/loyalty"
                    chip={null}
                    label={tierName}
                    value={tierProgress}
                    icon={
                        <Crown
                            className="size-5 opacity-70"
                            style={{ color: stats.tier?.color ?? '#b45309' }}
                        />
                    }
                />
                <StatCard
                    href="/vouchers"
                    chip={{ label: 'Vouchers', color: 'bg-amber-300 text-amber-900' }}
                    label="Points"
                    value={`${stats.points.toLocaleString()} pts`}
                    icon={<Gift className="size-5 text-amber-700/60" />}
                />
                </div>
            </div>
        </div>
    );
}

function StatCard({
    href,
    chip,
    label,
    value,
    icon,
}: {
    href: string;
    chip: { label: string; color: string } | null;
    label: string;
    value: string;
    icon: ReactNode;
}) {
    return (
        <Link
            href={href}
            className="border-border bg-card relative flex min-h-[78px] flex-col gap-1 overflow-hidden rounded-2xl border p-3 pt-5 shadow-sm transition-all hover:-translate-y-0.5 hover:shadow"
        >
            {chip && (
                <span
                    className={cn(
                        'absolute top-0 left-2 z-10 rounded-b-md px-2 py-0.5 text-[9px] font-bold uppercase tracking-wide',
                        chip.color,
                    )}
                >
                    {chip.label}
                </span>
            )}
            <span className="text-muted-foreground relative z-10 truncate text-[10px] leading-tight">
                {label}
            </span>
            <span className="text-card-foreground relative z-10 truncate text-sm font-bold leading-tight sm:text-base">
                {value}
            </span>
            <span className="pointer-events-none absolute right-2 bottom-2 z-0">{icon}</span>
        </Link>
    );
}
