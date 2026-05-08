import { Head, router } from '@inertiajs/react';
import { Coffee } from 'lucide-react';
import { useEffect } from 'react';
import { useBranchStore } from '@/stores/branch-store';

interface Props {
    hasBranches: boolean;
}

export default function Splash({ hasBranches }: Props) {
    const branch = useBranchStore((s) => s.selected);

    useEffect(() => {
        const timer = setTimeout(() => {
            if (!hasBranches) return;
            if (branch) {
                router.visit(`/branches/${branch.id}/menu`);
            } else {
                router.visit('/branches');
            }
        }, 1200);
        return () => clearTimeout(timer);
    }, [branch, hasBranches]);

    return (
        <>
            <Head title="Welcome" />
            <div className="flex min-h-screen flex-col items-center justify-center gap-4 bg-gradient-to-br from-amber-50 via-orange-50 to-orange-100 p-6">
                <div className="bg-primary text-primary-foreground flex size-20 animate-pulse items-center justify-center rounded-full shadow-lg">
                    <Coffee className="size-10" />
                </div>
                <h1 className="text-3xl font-bold tracking-tight">Star Coffee</h1>
                <p className="text-muted-foreground text-sm">Brewing your experience…</p>
                {!hasBranches && (
                    <p className="mt-4 max-w-xs text-center text-xs text-amber-700">
                        No branches are currently active. Please check back soon.
                    </p>
                )}
            </div>
        </>
    );
}
