import { type ReactNode } from 'react';
import { Link, usePage } from '@inertiajs/react';
import { Coffee, User } from 'lucide-react';

export default function AppLayout({ children }: { children: ReactNode }) {
    const { auth } = usePage().props;

    return (
        <div className="bg-background min-h-screen">
            <header className="border-border bg-card border-b">
                <div className="mx-auto flex max-w-6xl items-center justify-between px-4 py-3">
                    <Link href="/" className="flex items-center gap-2 font-semibold">
                        <Coffee className="text-primary size-5" />
                        <span>Star Coffee</span>
                    </Link>
                    <nav className="flex items-center gap-3 text-sm">
                        {auth.user ? (
                            <Link
                                href="/profile"
                                className="text-foreground hover:text-primary flex items-center gap-2"
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
                                    className="bg-primary text-primary-foreground hover:bg-primary/90 rounded-md px-3 py-1.5"
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
