import { Head } from '@inertiajs/react';
import StorefrontLayout from '@/layouts/storefront-layout';

export default function Terms() {
    return (
        <StorefrontLayout showBranchPicker={false}>
            <Head title="Terms & Conditions" />
            <article className="prose prose-sm dark:prose-invert max-w-none space-y-4">
                <h1 className="text-xl font-bold">Terms & Conditions</h1>
                <p className="text-muted-foreground text-xs">Last updated: 2026-05-09</p>

                <h2 className="text-base font-semibold">1. Acceptance of Terms</h2>
                <p className="text-sm">
                    By placing an order through the Star Coffee app, you agree to these terms.
                </p>

                <h2 className="text-base font-semibold">2. Orders</h2>
                <p className="text-sm">
                    All orders are subject to availability at the selected branch. Stock and pricing
                    may change in real time.
                </p>

                <h2 className="text-base font-semibold">3. Payment</h2>
                <p className="text-sm">
                    Online orders are processed via our payment partner. Walk-in orders may be paid
                    in cash, card, or DuitNow at the counter.
                </p>

                <h2 className="text-base font-semibold">4. Loyalty & Vouchers</h2>
                <p className="text-sm">
                    Loyalty points are earned on completed orders and may be redeemed at checkout
                    (100 points = RM 1.00). Vouchers have individual rules — see each voucher's
                    terms. Star Coffee reserves the right to adjust earn rates and tier thresholds.
                </p>

                <h2 className="text-base font-semibold">5. Cancellations & Refunds</h2>
                <p className="text-sm">
                    Orders can be cancelled before they enter the Preparing stage. Refunds are
                    processed back to the original payment method within 3–5 working days.
                </p>

                <h2 className="text-base font-semibold">6. Contact</h2>
                <p className="text-sm">
                    For questions, contact us at{' '}
                    <a href="mailto:hello@starcoffee.test" className="underline">
                        hello@starcoffee.test
                    </a>
                    .
                </p>
            </article>
        </StorefrontLayout>
    );
}
