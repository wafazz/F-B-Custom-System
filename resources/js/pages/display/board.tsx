import { Head } from '@inertiajs/react';
import { Coffee, Wifi, WifiOff } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { getEcho } from '@/lib/echo';

interface Row {
    id: number;
    number: string;
    table: string | null;
}

interface Props {
    branch: { id: number; code: string; name: string; logo: string | null };
    token: string;
    preparing: Row[];
    ready: Row[];
    reverb: { channel: string; queued_event: string; ready_event: string };
    settings: Record<string, unknown>;
}

export default function DisplayBoard({
    branch,
    token,
    preparing: initialPreparing,
    ready: initialReady,
    reverb,
}: Props) {
    const [preparing, setPreparing] = useState<Row[]>(initialPreparing);
    const [ready, setReady] = useState<Row[]>(initialReady);
    const [now, setNow] = useState(new Date());
    const [connected, setConnected] = useState(true);
    const [flashId, setFlashId] = useState<number | null>(null);
    const audioRef = useRef<HTMLAudioElement | null>(null);

    useEffect(() => {
        const echo = getEcho();
        const channel = echo.channel(reverb.channel);

        const onQueued = (event: {
            order_id: number;
            order_number: string;
            dine_in_table: string | null;
        }) => {
            const row = {
                id: event.order_id,
                number: event.order_number,
                table: event.dine_in_table,
            };
            setPreparing((prev) => (prev.find((r) => r.id === row.id) ? prev : [...prev, row]));
        };
        const onReady = (event: {
            order_id: number;
            order_number: string;
            dine_in_table: string | null;
        }) => {
            const row = {
                id: event.order_id,
                number: event.order_number,
                table: event.dine_in_table,
            };
            setPreparing((prev) => prev.filter((r) => r.id !== row.id));
            setReady((prev) => (prev.find((r) => r.id === row.id) ? prev : [...prev, row]));
            setFlashId(row.id);
            audioRef.current?.play().catch(() => {});
            window.setTimeout(() => setFlashId(null), 2500);
        };

        channel.listen(`.${reverb.queued_event}`, onQueued);
        channel.listen(`.${reverb.ready_event}`, onReady);
        return () => {
            channel.stopListening(`.${reverb.queued_event}`, onQueued);
            channel.stopListening(`.${reverb.ready_event}`, onReady);
            echo.leaveChannel(reverb.channel);
        };
    }, [reverb.channel, reverb.queued_event, reverb.ready_event]);

    useEffect(() => {
        const tick = window.setInterval(() => setNow(new Date()), 1000);
        return () => window.clearInterval(tick);
    }, []);

    useEffect(() => {
        const ping = window.setInterval(() => {
            fetch(`/branch/${branch.id}/display/heartbeat?token=${token}`, {
                method: 'POST',
                credentials: 'same-origin',
            })
                .then((r) => setConnected(r.ok))
                .catch(() => setConnected(false));
        }, 30_000);
        return () => window.clearInterval(ping);
    }, [branch.id, token]);

    return (
        <>
            <Head title={`Display — ${branch.name}`} />
            <div className="flex h-screen flex-col bg-gradient-to-br from-slate-950 to-amber-950 text-white">
                <header className="flex items-center justify-between border-b border-white/10 px-12 py-6">
                    <div className="flex items-center gap-4">
                        {branch.logo ? (
                            <img
                                src={`/storage/${branch.logo}`}
                                alt={branch.name}
                                className="size-12 rounded-full"
                            />
                        ) : (
                            <div className="flex size-12 items-center justify-center rounded-full bg-amber-600">
                                <Coffee className="size-7" />
                            </div>
                        )}
                        <div>
                            <h1 className="text-3xl font-bold">{branch.name}</h1>
                            <p className="text-sm text-amber-200/80">Order Display Board</p>
                        </div>
                    </div>
                    <div className="text-right">
                        <p className="text-3xl font-bold tabular-nums">
                            {now.toLocaleTimeString('en-MY', {
                                hour: '2-digit',
                                minute: '2-digit',
                            })}
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

                <main className="grid flex-1 grid-cols-[1fr_2fr] gap-8 p-12">
                    <section>
                        <h2 className="mb-4 text-2xl font-bold tracking-widest text-amber-200 uppercase">
                            Now Preparing
                        </h2>
                        <div className="space-y-3">
                            {preparing.length === 0 && <p className="text-2xl text-white/30">—</p>}
                            {preparing.map((row) => (
                                <article
                                    key={row.id}
                                    className="rounded-2xl border-2 border-amber-500/30 bg-black/30 px-6 py-4 backdrop-blur"
                                >
                                    <p className="font-mono text-3xl font-bold">{row.number}</p>
                                    {row.table && (
                                        <p className="text-sm text-white/60">Table {row.table}</p>
                                    )}
                                </article>
                            ))}
                        </div>
                    </section>

                    <section>
                        <h2 className="mb-4 text-3xl font-bold tracking-widest text-emerald-300 uppercase">
                            Ready for Pickup
                        </h2>
                        <div className="grid grid-cols-2 gap-4">
                            {ready.length === 0 && (
                                <p className="col-span-2 text-3xl text-white/30">—</p>
                            )}
                            {ready.map((row) => (
                                <article
                                    key={row.id}
                                    className={`rounded-3xl border-4 px-8 py-10 text-center backdrop-blur transition-all ${
                                        flashId === row.id
                                            ? 'scale-105 border-emerald-300 bg-emerald-500/20'
                                            : 'border-emerald-500/40 bg-black/30'
                                    }`}
                                >
                                    <p className="font-mono text-6xl font-black tracking-tight">
                                        {row.number}
                                    </p>
                                    {row.table && (
                                        <p className="mt-2 text-lg text-emerald-200">
                                            Table {row.table}
                                        </p>
                                    )}
                                </article>
                            ))}
                        </div>
                    </section>
                </main>
            </div>
            <audio
                ref={audioRef}
                preload="auto"
                src="data:audio/wav;base64,UklGRiQAAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQAAAAA="
            />
        </>
    );
}
