import { Link } from '@inertiajs/react';
import { type ReactNode } from 'react';

interface AuthLayoutProps {
    children: ReactNode;
    title: string;
    description?: string;
}

export default function AuthLayout({ children, title, description }: AuthLayoutProps) {
    return (
        <div
            className="flex min-h-screen items-center justify-center p-4"
            style={{
                background:
                    'radial-gradient(circle at top left, rgba(245, 158, 11, 0.10), transparent 50%), radial-gradient(circle at bottom right, rgba(245, 158, 11, 0.06), transparent 55%), #000000',
            }}
        >
            <div className="w-full max-w-sm space-y-5">
                <Link href="/" className="flex flex-col items-center gap-3">
                    <img
                        src="/images/logo.jpg"
                        alt="Star Coffee House"
                        className="size-24 rounded-full object-cover"
                        style={{
                            boxShadow:
                                '0 0 0 4px rgba(245, 158, 11, 0.30), 0 0 30px rgba(245, 158, 11, 0.20)',
                        }}
                    />
                </Link>
                <div
                    className="rounded-2xl p-8 shadow-2xl"
                    style={{
                        backgroundColor: '#0c0c0c',
                        border: '1px solid rgba(245, 158, 11, 0.25)',
                        boxShadow:
                            '0 0 0 1px rgba(245, 158, 11, 0.05), 0 25px 50px -12px rgba(0, 0, 0, 0.85)',
                    }}
                >
                    <div className="mb-6 space-y-1 text-center">
                        <h1 className="text-2xl font-bold tracking-tight text-white">{title}</h1>
                        {description && <p className="text-sm text-white/55">{description}</p>}
                    </div>
                    {children}
                </div>
            </div>
        </div>
    );
}
