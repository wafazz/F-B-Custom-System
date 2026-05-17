import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import StorefrontLayout from '@/layouts/storefront-layout';

export default function Privacy() {
    return (
        <StorefrontLayout hideStats>
            <Head title="Privacy Policy" />
            <article className="prose prose-sm dark:prose-invert max-w-none space-y-4">
                <div className="not-prose mb-2 flex items-center gap-3">
                    <Link
                        href="/profile?tab=data"
                        className="bg-card text-card-foreground hover:bg-amber-50 inline-flex items-center gap-1.5 rounded-full border border-amber-100 px-3 py-1.5 text-xs font-medium shadow-sm transition-colors"
                    >
                        <ArrowLeft className="size-4" />
                        <span>Back to Privacy & Data</span>
                    </Link>
                </div>
                <h1 className="text-xl font-bold">Privacy Policy</h1>
                <p className="text-muted-foreground text-xs">
                    Compliant with PDPA Malaysia · Last updated 2026-05-09
                </p>

                <h2 className="text-base font-semibold">What we collect</h2>
                <ul className="list-disc pl-5 text-sm">
                    <li>Account data: name, email, phone, optional date of birth.</li>
                    <li>Order data: items, modifiers, branch, total, payment method.</li>
                    <li>Usage data: branch preference, push subscriptions, device info.</li>
                </ul>

                <h2 className="text-base font-semibold">How we use it</h2>
                <ul className="list-disc pl-5 text-sm">
                    <li>To fulfil your orders and notify you about order status.</li>
                    <li>To run the loyalty programme and apply earned points + tier upgrades.</li>
                    <li>To prevent abuse (referral fraud, multi-account voucher misuse).</li>
                </ul>

                <h2 className="text-base font-semibold">Sharing</h2>
                <p className="text-sm">
                    We share order data only with branch staff fulfilling your order. Payment data
                    is handled by our processor and never stored on our servers in plain text.
                </p>

                <h2 className="text-base font-semibold">Your rights</h2>
                <p className="text-sm">
                    Under Malaysia's PDPA you can request access to, correction of, or deletion of
                    your data. Email{' '}
                    <a href="mailto:privacy@starcoffee.test" className="underline">
                        privacy@starcoffee.test
                    </a>
                    .
                </p>

                <h2 className="text-base font-semibold">Cookies</h2>
                <p className="text-sm">
                    We use a small number of essential cookies for session and CSRF protection. We
                    do not use third-party advertising trackers.
                </p>
            </article>
        </StorefrontLayout>
    );
}
