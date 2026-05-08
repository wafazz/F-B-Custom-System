import { Head } from '@inertiajs/react';
import StorefrontLayout from '@/layouts/storefront-layout';

const QA = [
    {
        q: 'How do I earn loyalty points?',
        a: '1 point per RM 1 you spend (subtotal, before tax). Higher tiers earn faster — Silver 1.25×, Gold 1.5×, Platinum 2×.',
    },
    {
        q: 'How do I redeem points?',
        a: '100 points = RM 1.00 discount. Apply at checkout — the discount comes off your subtotal before tax.',
    },
    {
        q: 'Why does my cart clear when I switch branches?',
        a: 'Branches have different stock and pricing. Switching means we re-validate everything against the new branch.',
    },
    {
        q: 'Can I pay at the counter?',
        a: 'Yes for walk-ins. Online orders are paid in advance to hold the stock.',
    },
    {
        q: 'How does the referral programme work?',
        a: 'Share your code with a friend. When they place their first order both of you get bonus points.',
    },
    {
        q: 'Can I cancel my order?',
        a: 'Yes — before it moves to Preparing. After that, contact the branch directly.',
    },
];

export default function Faq() {
    return (
        <StorefrontLayout showBranchPicker={false}>
            <Head title="FAQ" />
            <h1 className="mb-4 text-xl font-bold">Frequently Asked Questions</h1>
            <div className="space-y-3">
                {QA.map((row) => (
                    <details
                        key={row.q}
                        className="border-border bg-card group rounded-xl border p-4 shadow-sm"
                    >
                        <summary className="cursor-pointer text-sm font-semibold marker:hidden">
                            {row.q}
                        </summary>
                        <p className="text-muted-foreground mt-2 text-sm">{row.a}</p>
                    </details>
                ))}
            </div>
        </StorefrontLayout>
    );
}
