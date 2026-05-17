import { Link, usePage } from '@inertiajs/react';
import { Bell, ChevronDown, Coffee, CreditCard, Crown, Gift, Home, ShoppingBag, Star, User, Wallet } from 'lucide-react';
import { type ReactNode } from 'react';
import { InstallPrompt } from '@/components/storefront/install-prompt';
import { useBranchStore } from '@/stores/branch-store';
import { cartTotals, useCartStore } from '@/stores/cart-store';
import { cn } from '@/lib/utils';

function greetingFor(hour: number): string {
    if (hour < 12) return 'Good morning';
    if (hour < 18) return 'Good afternoon';
    return 'Good evening';
}

function firstName(name: string | null | undefined): string {
    if (!name) return 'Welcome';
    return name.split(/\s+/)[0] ?? name;
}

interface CustomerStats {
    wallet_balance: number;
    points: number;
    lifetime_spend: number;
    tier: { name: string; color: string | null; multiplier: number } | null;
    next_tier: { name: string; min_spend: number } | null;
}

interface Props {
    children: ReactNode;
    /** @deprecated kept for backward-compat — the default greeting header replaced the branch picker. */
    showBranchPicker?: boolean;
    headerSlot?: ReactNode;
    /** Hide the Wallet / Membership / Vouchers stat strip (e.g. on info pages). */
    hideStats?: boolean;
}

export default function StorefrontLayout({ children, headerSlot, hideStats = false }: Props) {
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
        <div className="bg-background text-card-foreground flex min-h-screen flex-col pb-24">
            <header className="bg-background sticky top-0 z-30">
                {headerSlot ? (
                    <div className="mx-auto w-full max-w-3xl px-4 py-3">{headerSlot}</div>
                ) : (
                    <DefaultGreetingHeader
                        userName={auth.user?.name ?? null}
                        branchName={branch?.name ?? null}
                        branchLogo={branch?.logo ?? null}
                        branchId={branch?.id ?? null}
                    />
                )}
            </header>

            {!hideStats && auth.user && customer_stats && (
                <CustomerStatsStrip stats={customer_stats} />
            )}

            <main className="bg-background mx-auto w-full max-w-3xl flex-1 px-4 py-4">{children}</main>

            <InstallPrompt />

            {/* Floating cart pill — only when items are in the cart */}
            {itemCount > 0 && branch && !path.endsWith('/cart') && !path.endsWith('/checkout') && (
                <Link
                    href={`/branches/${branch.id}/cart`}
                    className="bg-primary text-primary-foreground fixed bottom-24 left-1/2 z-40 flex -translate-x-1/2 items-center gap-2 rounded-full px-5 py-3 text-sm font-semibold shadow-lg transition-transform hover:scale-105"
                >
                    <ShoppingBag className="size-4" />
                    <span>View cart</span>
                    <span className="bg-primary-foreground/20 rounded-full px-2 py-0.5 text-[11px] font-bold">
                        {itemCount}
                    </span>
                </Link>
            )}

            <nav className="bg-background fixed inset-x-0 bottom-0 z-30">
                <div className="mx-auto grid max-w-3xl grid-cols-5 text-xs">
                    <NavItem
                        href={branch ? `/branches/${branch.id}` : '/'}
                        icon={<Home className="size-7" />}
                        label="Home"
                        active={path === '/' || (branch ? path === `/branches/${branch.id}` : false)}
                    />
                    <NavItem
                        href={branch ? `/branches/${branch.id}/menu` : '/branches'}
                        icon={<Coffee className="size-7" />}
                        label="Order"
                        active={path.includes('/menu') || path.endsWith('/cart') || path.endsWith('/checkout')}
                    />
                    <NavItem
                        href={auth.user ? '/vouchers' : '/login?redirect=/vouchers'}
                        icon={<Star className="size-7" />}
                        label="Rewards"
                        active={path === '/vouchers' || path.startsWith('/vouchers')}
                    />
                    <NavItem
                        href={auth.user ? '/loyalty' : '/login?redirect=/loyalty'}
                        icon={<CreditCard className="size-7" />}
                        label="Membership"
                        active={path === '/loyalty' || path === '/wallet' || path.startsWith('/wallet')}
                    />
                    <NavItem
                        href={auth.user ? '/profile' : '/login'}
                        icon={<User className="size-7" />}
                        label="Account"
                        active={
                            path === '/profile' ||
                            path === '/orders' ||
                            path.startsWith('/orders/') ||
                            path === '/login'
                        }
                    />
                </div>
            </nav>
        </div>
    );
}

function DefaultGreetingHeader({
    userName,
    branchName,
    branchLogo,
    branchId,
}: {
    userName: string | null;
    branchName: string | null;
    branchLogo: string | null;
    branchId: number | null;
}) {
    const greeting = greetingFor(new Date().getHours());
    const name = firstName(userName);
    const avatarSrc = branchLogo ? `/storage/${branchLogo}` : '/images/logo.jpg';
    const homeHref = branchId ? `/branches/${branchId}` : '/';

    return (
        <div className="bg-background mx-2 mt-2 flex items-center justify-between gap-3 rounded-2xl px-3 py-2.5">
            <div className="flex min-w-0 flex-1 items-center gap-3">
                <Link href={homeHref} className="shrink-0" aria-label="Home">
                    <img
                        src={avatarSrc}
                        alt={branchName ?? 'Star Coffee'}
                        className="size-11 rounded-full object-cover ring-2 ring-amber-100"
                    />
                </Link>
                <div className="min-w-0">
                    <Link href={homeHref} className="block">
                        <p className="text-muted-foreground text-[11px] leading-tight">
                            {greeting},
                        </p>
                        <p className="text-card-foreground truncate text-sm font-bold leading-tight">
                            {name} <span aria-hidden>✨</span>
                        </p>
                    </Link>
                    <Link
                        href="/branches"
                        className="hover:bg-amber-50/70 mt-0.5 -ml-1 inline-flex max-w-full items-center gap-1 rounded-full px-1.5 py-0.5 transition-colors"
                        aria-label="Change branch"
                    >
                        <span className="text-muted-foreground truncate text-[10px] uppercase tracking-wider">
                            {branchName
                                ? `Star Coffee — ${branchName.replace(/^star coffee[\s—-]*/i, '')}`
                                : 'Choose a branch'}
                        </span>
                        <ChevronDown className="text-muted-foreground size-3 shrink-0" />
                    </Link>
                </div>
            </div>
            <div className="flex shrink-0 items-center gap-2">
                <Link
                    href={userName ? '/orders' : '/login?redirect=/orders'}
                    className="relative flex size-10 items-center justify-center rounded-full bg-amber-50 text-amber-800 transition-colors hover:bg-amber-100"
                    aria-label="Notifications"
                >
                    <Bell className="size-4" />
                    <span className="absolute right-2 top-2 size-2 rounded-full bg-red-500" />
                </Link>
                <Link
                    href={userName ? '/profile' : '/login'}
                    className="flex size-10 items-center justify-center rounded-full bg-amber-800 text-amber-50 transition-colors hover:bg-amber-700"
                    aria-label={userName ? 'Profile' : 'Login'}
                >
                    <User className="size-4" />
                </Link>
            </div>
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
                'relative flex flex-col items-center justify-end py-2 transition-colors',
                active ? 'text-amber-800' : 'text-muted-foreground hover:text-foreground',
            )}
        >
            <span
                aria-hidden
                className={cn(
                    'absolute top-0 left-1/2 h-1.5 w-12 -translate-x-1/2 rounded-b-full transition-all',
                    active ? 'bg-amber-700' : 'bg-transparent',
                )}
            />
            <span
                className={cn(
                    'flex flex-col items-center gap-1 rounded-2xl px-4 py-2 transition-all',
                    active ? 'bg-amber-100' : 'bg-transparent',
                )}
            >
                <span className={cn('transition-transform', active && 'scale-110')}>{icon}</span>
                <span
                    className={cn(
                        'text-[11px] leading-none transition-all',
                        active ? 'font-bold' : 'font-medium',
                    )}
                >
                    {label}
                </span>
            </span>
        </Link>
    );
}

function CustomerStatsStrip({ stats }: { stats: CustomerStats }) {
    const tierProgress = stats.next_tier
        ? `${stats.lifetime_spend.toFixed(0)} / ${stats.next_tier.min_spend.toFixed(0)}`
        : 'MAX';
    const tierName = stats.tier?.name ?? 'Bronze';
    const tierColor = stats.tier?.color ?? '#b45309';

    return (
        <div className="bg-background">
            <div className="mx-auto w-full max-w-3xl px-4 pt-3 pb-3">
                <div className="grid grid-cols-3 gap-2.5">
                    <StatCard
                        href="/wallet"
                        label="WALLET"
                        line1="RM"
                        line2={stats.wallet_balance.toFixed(2)}
                        icon={<Wallet className="size-5 text-amber-700/70" />}
                    />
                    <StatCard
                        href="/loyalty"
                        label="MEMBERSHIP"
                        line1={tierName}
                        line2={tierProgress}
                        icon={
                            <Crown
                                className="size-5"
                                style={{ color: tierColor, opacity: 0.85 }}
                            />
                        }
                    />
                    <StatCard
                        href="/vouchers"
                        label="VOUCHERS"
                        line1={stats.points.toLocaleString()}
                        line2="pts"
                        icon={<Gift className="size-5 text-amber-700/70" />}
                    />
                </div>
            </div>
        </div>
    );
}

function StatCard({
    href,
    label,
    line1,
    line2,
    icon,
}: {
    href: string;
    label: string;
    line1: string;
    line2: string;
    icon: ReactNode;
}) {
    return (
        <Link
            href={href}
            className="bg-card relative flex min-h-[88px] flex-col justify-between overflow-hidden rounded-2xl p-3 shadow-[0_2px_8px_rgba(0,0,0,0.06)] transition-all hover:-translate-y-0.5 hover:shadow-[0_4px_14px_rgba(0,0,0,0.10)]"
        >
            <span className="text-muted-foreground text-[10px] font-semibold tracking-wider">
                {label}
            </span>
            <div className="leading-tight">
                <p className="text-muted-foreground text-[11px]">{line1}</p>
                <p className="text-card-foreground text-lg font-extrabold leading-snug">{line2}</p>
            </div>
            <span className="pointer-events-none absolute right-2.5 bottom-2.5 opacity-90">
                {icon}
            </span>
        </Link>
    );
}
