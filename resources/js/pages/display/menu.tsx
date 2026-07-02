import { Head, router } from '@inertiajs/react';
import { Maximize, Minimize, Wifi, WifiOff } from 'lucide-react';
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
    posters: string[];
}

function imageUrl(path: string | null): string {
    return path ? `/storage/${path}` : '/images/logo.jpg';
}

export default function MenuDisplay({ display, branch, token, slides, posters }: Props) {
    const [now, setNow] = useState(new Date());
    const [connected, setConnected] = useState(true);
    const [index, setIndex] = useState(0);
    const [isFullscreen, setIsFullscreen] = useState(false);

    const toggleFullscreen = () => {
        if (document.fullscreenElement) {
            document.exitFullscreen().catch(() => {});
        } else {
            document.documentElement.requestFullscreen().catch(() => {});
        }
    };

    useEffect(() => {
        const onChange = () => setIsFullscreen(!!document.fullscreenElement);
        document.addEventListener('fullscreenchange', onChange);
        return () => document.removeEventListener('fullscreenchange', onChange);
    }, []);

    const frames = useMemo(() => {
        const menuFrames =
            display.layout === 'single'
                ? slides.flatMap((slide) =>
                      slide.items.map((item) => ({
                          kind: 'single' as const,
                          title: slide.title,
                          item,
                      })),
                  )
                : slides.map((slide) => ({ kind: 'grid' as const, slide }));

        const posterFrames = (posters ?? []).map((image, i) => ({
            kind: 'poster' as const,
            id: i,
            image,
        }));

        return [...menuFrames, ...posterFrames];
    }, [slides, posters, display.layout]);

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
            <button
                onClick={toggleFullscreen}
                title={isFullscreen ? 'Exit fullscreen' : 'Fullscreen'}
                className="fixed top-3 right-3 z-50 rounded-full bg-black/40 p-2 text-white/70 opacity-40 backdrop-blur transition hover:bg-black/70 hover:text-white hover:opacity-100"
            >
                {isFullscreen ? <Minimize className="size-5" /> : <Maximize className="size-5" />}
            </button>
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
                                {frame?.kind === 'grid'
                                    ? frame.slide.title
                                    : frame?.kind === 'single'
                                      ? frame.title
                                      : 'Menu Board'}
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

                <main className="relative flex-1 overflow-hidden p-2">
                    {total === 0 && (
                        <div className="flex h-full items-center justify-center text-4xl text-white/30">
                            No menu items to display
                        </div>
                    )}

                    {frame && frame.kind === 'grid' && (
                        <div key={frame.slide.id} className="flex h-full flex-col">
                            <h2 className="mb-2 text-5xl font-black tracking-wide text-amber-200">
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
                            className="absolute inset-0 flex items-end justify-start"
                            style={{
                                backgroundImage: `linear-gradient(0deg, rgba(10,8,6,0.92) 0%, rgba(10,8,6,0.45) 45%, rgba(10,8,6,0.15) 100%), url(${imageUrl(frame.item.image)})`,
                                backgroundSize: 'cover',
                                backgroundPosition: 'center',
                            }}
                        >
                            <div className="max-w-4xl p-16">
                                {frame.item.badge && (
                                    <span className="inline-block rounded-full bg-amber-500/20 px-4 py-1 text-lg font-semibold text-amber-200">
                                        {frame.item.badge}
                                    </span>
                                )}
                                <h2 className="mt-4 text-8xl font-black leading-none drop-shadow-lg">
                                    {frame.item.name}
                                </h2>
                                {frame.item.description && (
                                    <p className="mt-6 text-3xl text-white/80">
                                        {frame.item.description}
                                    </p>
                                )}
                                {frame.item.price && (
                                    <p className="mt-8 text-7xl font-black text-amber-300 drop-shadow-lg">
                                        RM {frame.item.price}
                                    </p>
                                )}
                            </div>
                        </div>
                    )}

                    {frame && frame.kind === 'poster' && (
                        <div
                            key={`poster-${frame.id}`}
                            className="absolute inset-0"
                            style={{
                                backgroundImage: `url(/storage/${frame.image})`,
                                backgroundSize: 'contain',
                                backgroundPosition: 'center',
                                backgroundRepeat: 'no-repeat',
                                backgroundColor: '#0a0806',
                            }}
                        />
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
