import { Head, router } from '@inertiajs/react';
import { Maximize, Minimize } from 'lucide-react';
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';

declare global {
    interface Window {
        // eslint-disable-next-line @typescript-eslint/no-explicit-any
        YT?: any;
        onYouTubeIframeAPIReady?: () => void;
    }
}

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
    videos: string[];
}

function imageUrl(path: string | null): string {
    return path ? `/storage/${path}` : '/images/logo.jpg';
}

export default function MenuDisplay({ display, token, slides, posters, videos }: Props) {
    const [index, setIndex] = useState(0);
    const [isFullscreen, setIsFullscreen] = useState(false);
    const playerRef = useRef<unknown>(null);
    const soundEnabledRef = useRef(false);

    // Turn on sound after a user gesture (browsers block unmuted autoplay otherwise).
    const enableSound = useCallback(() => {
        soundEnabledRef.current = true;
        const p = playerRef.current as { unMute?: () => void; setVolume?: (v: number) => void } | null;
        try {
            p?.unMute?.();
            p?.setVolume?.(100);
        } catch {
            /* noop */
        }
    }, []);

    const toggleFullscreen = () => {
        enableSound();
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

    // First tap/key anywhere unmutes video sound for the rest of the session.
    useEffect(() => {
        const on = () => enableSound();
        window.addEventListener('pointerdown', on);
        window.addEventListener('keydown', on);
        return () => {
            window.removeEventListener('pointerdown', on);
            window.removeEventListener('keydown', on);
        };
    }, [enableSound]);

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

        const videoFrames = (videos ?? []).map((videoId, i) => ({
            kind: 'video' as const,
            id: i,
            videoId,
        }));

        return [...menuFrames, ...posterFrames, ...videoFrames];
    }, [slides, posters, videos, display.layout]);

    const total = frames.length;
    const current = frames[Math.min(index, total - 1)];
    const activeVideo = current?.kind === 'video' ? current : null;

    const advance = useCallback(() => {
        setIndex((prev) => (total > 0 ? (prev + 1) % total : 0));
    }, [total]);

    useEffect(() => {
        if (index >= total && total > 0) setIndex(0);
    }, [total, index]);

    // Timed auto-advance for non-video slides. Video slides advance themselves on end.
    useEffect(() => {
        if (total <= 1 || current?.kind === 'video') return;
        const seconds = Math.max(3, display.seconds || 8);
        const timer = window.setTimeout(advance, seconds * 1000);
        return () => window.clearTimeout(timer);
    }, [index, total, current?.kind, display.seconds, advance]);

    // YouTube player: play the active video slide, advance ONLY when it ends.
    useEffect(() => {
        if (!activeVideo) return;
        const elementId = `yt-player-${activeVideo.id}`;
        let cancelled = false;
        let poll: number | undefined;
        let loadGuard: number | undefined;

        const build = () => {
            if (cancelled || !window.YT?.Player) return;
            // Player is loading — cancel the API-load guard so long videos aren't cut off.
            if (loadGuard) {
                window.clearTimeout(loadGuard);
                loadGuard = undefined;
            }
            playerRef.current = new window.YT.Player(elementId, {
                videoId: activeVideo.videoId,
                width: '100%',
                height: '100%',
                playerVars: {
                    autoplay: 1,
                    mute: 1,
                    controls: 0,
                    rel: 0,
                    modestbranding: 1,
                    playsinline: 1,
                    fs: 0,
                    disablekb: 1,
                },
                events: {
                    // eslint-disable-next-line @typescript-eslint/no-explicit-any
                    onReady: (e: any) => {
                        if (soundEnabledRef.current) {
                            e.target.unMute();
                            e.target.setVolume(100);
                        }
                        e.target.playVideo();
                    },
                    // eslint-disable-next-line @typescript-eslint/no-explicit-any
                    onStateChange: (e: any) => {
                        if (e.data === window.YT.PlayerState.ENDED) advance();
                    },
                    onError: () => advance(),
                },
            });
        };

        if (window.YT?.Player) {
            build();
        } else {
            if (!document.getElementById('yt-iframe-api')) {
                const tag = document.createElement('script');
                tag.id = 'yt-iframe-api';
                tag.src = 'https://www.youtube.com/iframe_api';
                document.body.appendChild(tag);
            }
            poll = window.setInterval(() => {
                if (window.YT?.Player) {
                    window.clearInterval(poll);
                    build();
                }
            }, 200);
            // Guard ONLY against the API never loading — cleared once the player builds.
            loadGuard = window.setTimeout(() => {
                if (poll) window.clearInterval(poll);
                if (!window.YT?.Player) advance();
            }, 15_000);
        }

        return () => {
            cancelled = true;
            if (poll) window.clearInterval(poll);
            if (loadGuard) window.clearTimeout(loadGuard);
            const p = playerRef.current as { destroy?: () => void } | null;
            try {
                p?.destroy?.();
            } catch {
                /* noop */
            }
            playerRef.current = null;
        };
    }, [activeVideo, advance]);

    // Heartbeat so the admin sees the screen as online (updates last_seen_at).
    useEffect(() => {
        const ping = window.setInterval(() => {
            fetch(`/menu-display/${token}/heartbeat`, {
                method: 'POST',
                credentials: 'same-origin',
            }).catch(() => {});
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

    const frame = current;

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

                    {frame && frame.kind === 'video' && (
                        <div key={`video-${frame.id}`} className="absolute inset-0 bg-black">
                            <div id={`yt-player-${frame.id}`} className="h-full w-full" />
                        </div>
                    )}
                </main>

            </div>
        </>
    );
}
