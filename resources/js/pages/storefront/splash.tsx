import { Head, router } from '@inertiajs/react';
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
            <div className="flex min-h-screen flex-col items-center justify-center gap-4 bg-black p-6 text-white">
                <img
                    src="/images/logo.jpg"
                    alt="Star Coffee House"
                    className="size-48 animate-pulse rounded-full object-cover shadow-2xl ring-4 ring-amber-500/20"
                />
                <p className="text-sm text-white/60">Brewing your experience…</p>
                {!hasBranches && (
                    <p className="mt-4 max-w-xs text-center text-xs text-amber-400">
                        No branches are currently active. Please check back soon.
                    </p>
                )}
            </div>
        </>
    );
}
