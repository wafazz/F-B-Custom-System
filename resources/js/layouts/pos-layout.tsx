import { Link, router, usePage } from '@inertiajs/react';
import { LogOut, ShoppingBag, Store, Tv } from 'lucide-react';
import { type ReactNode } from 'react';
import { cn } from '@/lib/utils';

interface PosShared {
    branch?: { id: number; code: string; name: string };
    staff?: { name: string };
}

export default function PosLayout({ children }: { children: ReactNode }) {
    const props = usePage().props as unknown as PosShared & { url?: string };
    const branch = props.branch;
    const staff = props.staff;
    const path = typeof window !== 'undefined' ? window.location.pathname : (props.url ?? '');

    function logout() {
        router.post('/pos/logout');
    }

    return (
        <div className="min-h-screen bg-slate-950 text-slate-100">
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
                </nav>
                <div className="flex items-center gap-3">
                    {staff && <span className="text-xs text-slate-400">{staff.name}</span>}
                    <button
                        type="button"
                        onClick={logout}
                        className="flex items-center gap-1 rounded-md bg-slate-800 px-3 py-1.5 text-xs text-slate-200 hover:bg-slate-700"
                    >
                        <LogOut className="size-3.5" /> Logout
                    </button>
                </div>
            </header>
            <main className="mx-auto max-w-7xl p-6">{children}</main>
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
