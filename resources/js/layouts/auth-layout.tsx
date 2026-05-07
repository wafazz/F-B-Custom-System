import { type ReactNode } from 'react';
import { Link } from '@inertiajs/react';
import { Coffee } from 'lucide-react';
import { Card } from '@/components/ui/card';

interface AuthLayoutProps {
    children: ReactNode;
    title: string;
    description?: string;
}

export default function AuthLayout({ children, title, description }: AuthLayoutProps) {
    return (
        <div className="flex min-h-screen items-center justify-center bg-gradient-to-br from-amber-50 to-orange-100 p-4">
            <div className="w-full max-w-md space-y-6">
                <Link href="/" className="flex flex-col items-center gap-2">
                    <div className="flex size-12 items-center justify-center rounded-full bg-primary/10 text-primary">
                        <Coffee className="size-6" />
                    </div>
                    <span className="text-lg font-semibold">Star Coffee</span>
                </Link>
                <Card className="p-6">
                    <div className="mb-6 space-y-1 text-center">
                        <h1 className="text-2xl font-semibold tracking-tight">{title}</h1>
                        {description && (
                            <p className="text-sm text-muted-foreground">{description}</p>
                        )}
                    </div>
                    {children}
                </Card>
            </div>
        </div>
    );
}
