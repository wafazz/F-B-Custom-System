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

export default function Privacy({ page }: Props) {
    return (
        <StorefrontLayout hideStats>
            <Head title={page.title} />
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
                <h1 className="text-xl font-bold">{page.title}</h1>
                {page.last_updated_label && (
                    <p className="text-muted-foreground text-xs">
                        Last updated: {page.last_updated_label}
                    </p>
                )}
                <div
                    className="prose prose-sm max-w-none [&_h2]:text-base [&_h2]:font-semibold [&_p]:text-sm [&_ul]:text-sm"
                    dangerouslySetInnerHTML={{ __html: page.body }}
                />
            </article>
        </StorefrontLayout>
    );
}
