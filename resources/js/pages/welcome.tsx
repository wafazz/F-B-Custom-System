import { Head } from '@inertiajs/react';

export default function Welcome() {
    return (
        <>
            <Head title="Welcome" />
            <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-amber-50 to-orange-100">
                <div className="text-center px-6">
                    <h1 className="text-5xl font-bold text-amber-900 mb-4">Star Coffee</h1>
                    <p className="text-amber-700 text-lg mb-8">
                        F&amp;B Platform — Coming Soon
                    </p>
                    <div className="inline-flex gap-3 text-sm text-amber-600">
                        <span className="px-3 py-1 bg-amber-200 rounded-full">Laravel 12</span>
                        <span className="px-3 py-1 bg-amber-200 rounded-full">React 19 + TS</span>
                        <span className="px-3 py-1 bg-amber-200 rounded-full">Inertia 2</span>
                        <span className="px-3 py-1 bg-amber-200 rounded-full">Tailwind 4</span>
                    </div>
                </div>
            </div>
        </>
    );
}
