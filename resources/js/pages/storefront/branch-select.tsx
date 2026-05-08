import { Head, router } from '@inertiajs/react';
import { MapPin, Phone } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import StorefrontLayout from '@/layouts/storefront-layout';
import { useBranchStore } from '@/stores/branch-store';
import { useCartStore } from '@/stores/cart-store';
import type { BranchSummary } from '@/types/menu';

interface Props {
    branches: BranchSummary[];
}

export default function BranchSelect({ branches }: Props) {
    const setBranch = useBranchStore((s) => s.setBranch);
    const rebindBranch = useCartStore((s) => s.rebindBranch);

    function handlePick(branch: BranchSummary) {
        const cleared = rebindBranch(branch.id);
        if (cleared) {
            window.alert('Switching branch cleared your existing cart.');
        }
        setBranch(branch);
        router.visit(`/branches/${branch.id}/menu`);
    }

    return (
        <StorefrontLayout showBranchPicker={false}>
            <Head title="Pick a Branch" />
            <div className="mb-4">
                <h1 className="text-xl font-bold">Choose your Star Coffee branch</h1>
                <p className="text-muted-foreground text-sm">
                    Pricing and availability depend on the branch you pick.
                </p>
            </div>

            {branches.length === 0 ? (
                <div className="border-border bg-card text-muted-foreground rounded-lg border border-dashed p-6 text-center text-sm">
                    No branches are currently active.
                </div>
            ) : (
                <ul className="grid gap-3">
                    {branches.map((branch) => (
                        <li key={branch.id}>
                            <button
                                type="button"
                                onClick={() => handlePick(branch)}
                                className="border-border bg-card flex w-full items-start gap-3 rounded-xl border p-4 text-left shadow-sm transition-all hover:-translate-y-0.5 hover:shadow-md"
                            >
                                <img
                                    src={
                                        branch.logo ? `/storage/${branch.logo}` : '/images/logo.jpg'
                                    }
                                    alt={branch.name}
                                    className="size-12 flex-shrink-0 rounded-full object-cover"
                                />
                                <div className="flex-1">
                                    <div className="flex items-center justify-between gap-2">
                                        <h3 className="font-semibold">{branch.name}</h3>
                                        <Badge variant={branch.is_open_now ? 'success' : 'danger'}>
                                            {branch.is_open_now ? 'Open' : 'Closed'}
                                        </Badge>
                                    </div>
                                    <p className="text-muted-foreground mt-1 flex items-start gap-1.5 text-xs">
                                        <MapPin className="mt-0.5 size-3 flex-shrink-0" />
                                        <span>
                                            {branch.address}
                                            {branch.city && `, ${branch.city}`}
                                        </span>
                                    </p>
                                    {branch.phone && (
                                        <p className="text-muted-foreground mt-1 flex items-center gap-1.5 text-xs">
                                            <Phone className="size-3" />
                                            <span>{branch.phone}</span>
                                        </p>
                                    )}
                                </div>
                            </button>
                        </li>
                    ))}
                </ul>
            )}
        </StorefrontLayout>
    );
}
