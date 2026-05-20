import { Link, router, usePage } from '@inertiajs/react';
import { Banknote, Download, Gift, LogOut, ShoppingBag, Store, Tv } from 'lucide-react';
import { type ReactNode, useEffect, useState } from 'react';
import { cn } from '@/lib/utils';

interface BeforeInstallPromptEvent extends Event {
    prompt(): Promise<void>;
    userChoice: Promise<{ outcome: 'accepted' | 'dismissed' }>;
}

interface PosShared {
    branch?: { id: number; code: string; name: string };
    staff?: { name: string };
    pos_shift?: { id: number; opened_at: string } | null;
}

export default function PosLayout({ children }: { children: ReactNode }) {
    const props = usePage().props as unknown as PosShared & { url?: string };
    const branch = props.branch;
    const staff = props.staff;
    const posShift = props.pos_shift ?? null;
    const path = typeof window !== 'undefined' ? window.location.pathname : (props.url ?? '');

    function logout() {
        router.post('/pos/logout');
    }

    // The POS manifest is selected server-side in app.blade.php based on the
    // request path, so Chrome reads /pos.webmanifest on the first paint and
    // there's no manifest-swap race against the install criteria.
    const [installEvt, setInstallEvt] = useState<BeforeInstallPromptEvent | null>(null);
    const [installed, setInstalled] = useState(
        () =>
            typeof window !== 'undefined' &&
            (window.matchMedia?.('(display-mode: standalone)').matches ||
                (window.navigator as Navigator & { standalone?: boolean }).standalone === true),
    );

    useEffect(() => {
        const onPrompt = (e: Event) => {
            e.preventDefault();
            setInstallEvt(e as BeforeInstallPromptEvent);
        };
        const onInstalled = () => {
            setInstalled(true);
            setInstallEvt(null);
        };
        window.addEventListener('beforeinstallprompt', onPrompt);
        window.addEventListener('appinstalled', onInstalled);
        return () => {
            window.removeEventListener('beforeinstallprompt', onPrompt);
            window.removeEventListener('appinstalled', onInstalled);
        };
    }, []);

    async function installApp() {
        if (!installEvt) return;
        await installEvt.prompt();
        await installEvt.userChoice;
        setInstallEvt(null);
    }

    return (
        <div className="flex h-screen flex-col bg-slate-900 text-slate-100">
            <header className="flex items-center justify-between border-b border-slate-800 bg-slate-900 px-6 py-3">
                <div className="flex items-center gap-3">
                    <img
                        src="/images/logo.jpg"
                        alt="Star Coffee"
                        className="size-10 rounded-full object-cover"
                    />
                    <div>
                        <h1 className="text-lg font-bold tracking-tight">POS</h1>
                        {branch && (
                            <p className="text-xs text-slate-400">
                                {branch.name} · {branch.code}
                            </p>
                        )}
                    </div>
                </div>
                <nav className="flex items-center gap-1 text-sm">
                    <NavLink
                        href="/pos"
                        label="Queue"
                        icon={<ShoppingBag className="size-4" />}
                        active={path === '/pos'}
                    />
                    <NavLink
                        href="/pos/walk-in"
                        label="Walk-in"
                        icon={<Store className="size-4" />}
                        active={path.startsWith('/pos/walk-in')}
                    />
                    <NavLink
                        href="/pos/stock"
                        label="Stock"
                        icon={<Tv className="size-4" />}
                        active={path.startsWith('/pos/stock')}
                    />
                    <NavLink
                        href="/pos/reward-pickups"
                        label="Rewards"
                        icon={<Gift className="size-4" />}
                        active={path.startsWith('/pos/reward-pickups')}
                    />
                    <NavLink
                        href="/pos/shift"
                        label="Cash"
                        icon={<Banknote className="size-4" />}
                        active={path.startsWith('/pos/shift')}
                    />
                </nav>
                <div className="flex items-center gap-3">
                    {posShift ? (
                        <span className="hidden items-center gap-1.5 rounded-full border border-emerald-700/60 bg-emerald-950/40 px-2.5 py-1 text-[10px] font-semibold text-emerald-300 sm:inline-flex">
                            <span className="size-1.5 animate-pulse rounded-full bg-emerald-400" />
                            Shift open
                        </span>
                    ) : (
                        <Link
                            href="/pos/shift"
                            className="hidden items-center gap-1.5 rounded-full border border-amber-700/60 bg-amber-950/40 px-2.5 py-1 text-[10px] font-semibold text-amber-300 hover:bg-amber-900/40 sm:inline-flex"
                        >
                            No shift
                        </Link>
                    )}
                    {staff && <span className="text-xs text-slate-400">{staff.name}</span>}
                    {!installed && installEvt && (
                        <button
                            type="button"
                            onClick={installApp}
                            className="flex items-center gap-1 rounded-md bg-amber-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-amber-500"
                            title="Install POS as a home-screen app"
                        >
                            <Download className="size-3.5" /> Install POS
                        </button>
                    )}
                    <button
                        type="button"
                        onClick={logout}
                        className="flex items-center gap-1 rounded-md bg-slate-800 px-3 py-1.5 text-xs text-slate-200 hover:bg-slate-700"
                    >
                        <LogOut className="size-3.5" /> Logout
                    </button>
                </div>
            </header>
            <main className="mx-auto min-h-0 w-full max-w-7xl flex-1 p-4">{children}</main>
        </div>
    );
}

function NavLink({
    href,
    label,
    icon,
    active,
}: {
    href: string;
    label: string;
    icon: ReactNode;
    active: boolean;
}) {
    return (
        <Link
            href={href}
            className={cn(
                'flex items-center gap-1.5 rounded-md px-3 py-1.5 transition-colors',
                active ? 'bg-amber-600 text-white' : 'text-slate-300 hover:bg-slate-800',
            )}
        >
            {icon}
            <span>{label}</span>
        </Link>
    );
}
