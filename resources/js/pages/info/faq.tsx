import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import StorefrontLayout from '@/layouts/storefront-layout';

interface Props {
    page: {
        title: string;
        body: string;
        last_updated_label: string | null;
    };
}

export default function Faq({ page }: Props) {
    return (
        <StorefrontLayout hideStats>
            <Head title={page.title} />
            <div className="mb-2 flex items-center gap-3">
                <Link
                    href="/profile?tab=data"
                    className="bg-card text-card-foreground hover:bg-amber-50 inline-flex items-center gap-1.5 rounded-full border border-amber-100 px-3 py-1.5 text-xs font-medium shadow-sm transition-colors"
                >
                    <ArrowLeft className="size-4" />
                    <span>Back to Privacy & Data</span>
                </Link>
            </div>
            <h1 className="mb-1 text-xl font-bold">{page.title}</h1>
            {page.last_updated_label && (
                <p className="text-muted-foreground mb-4 text-xs">
                    Last updated: {page.last_updated_label}
                </p>
            )}
            <article
                className="prose prose-sm max-w-none [&_h2]:mb-1 [&_h2]:mt-4 [&_h2]:text-sm [&_h2]:font-semibold [&_p]:text-sm [&_p]:text-muted-foreground"
                dangerouslySetInnerHTML={{ __html: page.body }}
            />
        </StorefrontLayout>
    );
}
