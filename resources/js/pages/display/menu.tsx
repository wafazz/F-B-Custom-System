import { Head, router } from '@inertiajs/react';
import { Wifi, WifiOff } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';

interface Item {
    id: number;
    name: string;
    description: string | null;
    price: string | null;
    image: string | null;
    badge: string | null;
}

interface Slide {
    id: number;
    title: string;
    image: string | null;
    items: Item[];
}

interface Props {
    display: {
        name: string;
        heading: string | null;
        layout: 'grid' | 'single';
        seconds: number;
        showPrice: boolean;
    };
    branch: { name: string; logo: string | null } | null;
    token: string;
    slides: Slide[];
}

function imageUrl(path: string | null): string {
    return path ? `/storage/${path}` : '/images/logo.jpg';
}

export default function MenuDisplay({ display, branch, token, slides }: Props) {
    const [now, setNow] = useState(new Date());
    const [connected, setConnected] = useState(true);
    const [index, setIndex] = useState(0);

    const frames = useMemo(() => {
        if (display.layout === 'single') {
            return slides.flatMap((slide) =>
                slide.items.map((item) => ({ kind: 'single' as const, title: slide.title, item })),
            );
        }
        return slides.map((slide) => ({ kind: 'grid' as const, slide }));
    }, [slides, display.layout]);

    const total = frames.length;

    useEffect(() => {
        if (index >= total && total > 0) setIndex(0);
    }, [total, index]);

    useEffect(() => {
        if (total <= 1) return;
        const seconds = Math.max(3, display.seconds || 8);
        const timer = window.setInterval(() => {
            setIndex((prev) => (prev + 1) % total);
        }, seconds * 1000);
        return () => window.clearInterval(timer);
    }, [total, display.seconds]);

    useEffect(() => {
        const tick = window.setInterval(() => setNow(new Date()), 1000);
        return () => window.clearInterval(tick);
    }, []);

    useEffect(() => {
        const ping = window.setInterval(() => {
            fetch(`/menu-display/${token}/heartbeat`, {
                method: 'POST',
                credentials: 'same-origin',
            })
                .then((r) => setConnected(r.ok))
                .catch(() => setConnected(false));
        }, 30_000);
        return () => window.clearInterval(ping);
    }, [token]);

    // Refresh menu data periodically to pick up price/availability changes.
    useEffect(() => {
        const refresh = window.setInterval(() => {
            router.reload({ only: ['slides'] });
        }, 300_000);
        return () => window.clearInterval(refresh);
    }, []);

    const frame = frames[Math.min(index, total - 1)];

    return (
        <>
            <Head title={`Menu — ${display.name}`} />
            <div className="flex h-screen flex-col overflow-hidden bg-gradient-to-br from-slate-950 to-amber-950 text-white">
                <header className="flex items-center justify-between border-b border-white/10 px-12 py-6">
                    <div className="flex items-center gap-4">
                        <img
                            src={branch?.logo ? `/storage/${branch.logo}` : '/images/logo.jpg'}
                            alt={branch?.name ?? 'Star Coffee'}
                            className="size-16 rounded-full object-cover ring-2 ring-amber-500/30"
                        />
                        <div>
                            <h1 className="text-3xl font-bold">
                                {display.heading ?? branch?.name ?? 'Our Menu'}
                            </h1>
                            <p className="text-sm text-amber-200/80">
                                {frame && frame.kind === 'grid'
                                    ? frame.slide.title
                                    : frame?.title ?? 'Menu Board'}
                            </p>
                        </div>
                    </div>
                    <div className="text-right">
                        <p className="text-3xl font-bold tabular-nums">
                            {now.toLocaleTimeString('en-MY', { hour: '2-digit', minute: '2-digit' })}
                        </p>
                        <p className="flex items-center justify-end gap-1 text-xs text-white/70">
                            {connected ? (
                                <Wifi className="size-3 text-emerald-400" />
                            ) : (
                                <WifiOff className="size-3 text-red-400" />
                            )}
                            {now.toLocaleDateString('en-MY', {
                                weekday: 'long',
                                month: 'short',
                                day: 'numeric',
                            })}
                        </p>
                    </div>
                </header>

                <main className="relative flex-1 overflow-hidden p-12">
                    {total === 0 && (
                        <div className="flex h-full items-center justify-center text-4xl text-white/30">
                            No menu items to display
                        </div>
                    )}

                    {frame && frame.kind === 'grid' && (
                        <div key={frame.slide.id} className="flex h-full flex-col">
                            <h2 className="mb-8 text-5xl font-black tracking-wide text-amber-200">
                                {frame.slide.title}
                            </h2>
                            <div className="grid flex-1 auto-rows-min grid-cols-3 gap-8 overflow-hidden">
                                {frame.slide.items.slice(0, 9).map((item) => (
                                    <article
                                        key={item.id}
                                        className="flex items-center gap-5 rounded-3xl border border-white/10 bg-black/30 p-5 backdrop-blur"
                                    >
                                        <img
                                            src={imageUrl(item.image)}
                                            alt={item.name}
                                            className="size-28 shrink-0 rounded-2xl object-cover"
                                        />
                                        <div className="min-w-0 flex-1">
                                            <div className="flex items-center gap-2">
                                                <h3 className="truncate text-2xl font-bold">
                                                    {item.name}
                                                </h3>
                                                {item.badge && (
                                                    <span className="rounded-full bg-amber-500/20 px-2 py-0.5 text-xs font-semibold text-amber-200">
                                                        {item.badge}
                                                    </span>
                                                )}
                                            </div>
                                            {item.description && (
                                                <p className="mt-1 line-clamp-2 text-sm text-white/60">
                                                    {item.description}
                                                </p>
                                            )}
                                            {item.price && (
                                                <p className="mt-2 text-3xl font-black text-amber-300">
                                                    RM {item.price}
                                                </p>
                                            )}
                                        </div>
                                    </article>
                                ))}
                            </div>
                        </div>
                    )}

                    {frame && frame.kind === 'single' && (
                        <div
                            key={frame.item.id}
                            className="flex h-full items-center justify-center gap-16"
                        >
                            <img
                                src={imageUrl(frame.item.image)}
                                alt={frame.item.name}
                                className="max-h-[70vh] w-1/2 rounded-[2.5rem] object-cover shadow-2xl"
                            />
                            <div className="w-1/2 max-w-2xl">
                                {frame.item.badge && (
                                    <span className="inline-block rounded-full bg-amber-500/20 px-4 py-1 text-lg font-semibold text-amber-200">
                                        {frame.item.badge}
                                    </span>
                                )}
                                <h2 className="mt-4 text-7xl font-black leading-tight">
                                    {frame.item.name}
                                </h2>
                                {frame.item.description && (
                                    <p className="mt-6 text-2xl text-white/70">
                                        {frame.item.description}
                                    </p>
                                )}
                                {frame.item.price && (
                                    <p className="mt-8 text-6xl font-black text-amber-300">
                                        RM {frame.item.price}
                                    </p>
                                )}
                            </div>
                        </div>
                    )}
                </main>

                {total > 1 && (
                    <div className="flex justify-center gap-2 pb-6">
                        {frames.map((_, i) => (
                            <span
                                key={i}
                                className={`h-2 rounded-full transition-all ${
                                    i === Math.min(index, total - 1)
                                        ? 'w-8 bg-amber-400'
                                        : 'w-2 bg-white/25'
                                }`}
                            />
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}
