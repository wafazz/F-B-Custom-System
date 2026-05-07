import { Head, Link } from '@inertiajs/react';
import { Coffee } from 'lucide-react';
import { Button } from '@/components/ui/button';

const messages: Record<number, { title: string; description: string }> = {
    403: { title: 'Forbidden', description: 'You do not have permission to access this page.' },
    404: { title: 'Not Found', description: "Sorry, the page you're looking for doesn't exist." },
    500: { title: 'Server Error', description: 'Something went wrong on our end. Please try again later.' },
    503: { title: 'Service Unavailable', description: 'We are temporarily down for maintenance.' },
};

export default function ErrorPage({ status }: { status: number }) {
    const meta = messages[status] ?? { title: 'Error', description: 'An error occurred.' };

    return (
        <>
            <Head title={`${status} — ${meta.title}`} />
            <div className="flex min-h-screen flex-col items-center justify-center bg-gradient-to-br from-amber-50 to-orange-100 px-6 text-center">
                <Coffee className="mb-4 size-12 text-primary" />
                <h1 className="text-6xl font-bold text-primary">{status}</h1>
                <h2 className="mt-2 text-xl font-semibold">{meta.title}</h2>
                <p className="mt-2 max-w-md text-muted-foreground">{meta.description}</p>
                <Button asChild className="mt-6">
                    <Link href="/">Back to home</Link>
                </Button>
            </div>
        </>
    );
}
