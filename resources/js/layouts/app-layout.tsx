import { type ReactNode } from 'react';
import { Link, usePage } from '@inertiajs/react';
import { Coffee, User } from 'lucide-react';

export default function AppLayout({ children }: { children: ReactNode }) {
    const { auth } = usePage().props;

    return (
        <div className="min-h-screen bg-background">
            <header className="border-b border-border bg-card">
                <div className="mx-auto flex max-w-6xl items-center justify-between px-4 py-3">
                    <Link href="/" className="flex items-center gap-2 font-semibold">
                        <Coffee className="size-5 text-primary" />
                        <span>Star Coffee</span>
                    </Link>
                    <nav className="flex items-center gap-3 text-sm">
                        {auth.user ? (
                            <Link
                                href="/profile"
                                className="flex items-center gap-2 text-foreground hover:text-primary"
                            >
                                <User className="size-4" />
                                <span>{auth.user.name}</span>
                            </Link>
                        ) : (
                            <>
                                <Link href="/login" className="hover:text-primary">
                                    Login
                                </Link>
                                <Link
                                    href="/register"
                                    className="rounded-md bg-primary px-3 py-1.5 text-primary-foreground hover:bg-primary/90"
                                >
                                    Register
                                </Link>
                            </>
                        )}
                    </nav>
                </div>
            </header>
            <main>{children}</main>
        </div>
    );
}
