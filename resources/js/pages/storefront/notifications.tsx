import { Head, Link, router } from '@inertiajs/react';
import { Bell, BellOff, CheckCheck, Inbox } from 'lucide-react';
import StorefrontLayout from '@/layouts/storefront-layout';
import { cn } from '@/lib/utils';

interface NotificationRow {
    id: string;
    type: string;
    title: string;
    body: string;
    url: string | null;
    read_at: string | null;
    created_at: string;
    human_time: string;
}

interface Props {
    items: NotificationRow[];
    unread_count: number;
}

export default function Notifications({ items, unread_count }: Props) {
    function markRead(id: string) {
        router.post(
            `/notifications/${id}/read`,
            {},
            { preserveScroll: true, preserveState: true },
        );
    }

    function markAllRead() {
        if (unread_count === 0) return;
        router.post(
            '/notifications/mark-all-read',
            {},
            { preserveScroll: true, preserveState: true },
        );
    }

    function handleClick(notif: NotificationRow) {
        if (!notif.read_at) markRead(notif.id);
        if (notif.url) router.visit(notif.url);
    }

    return (
        <StorefrontLayout hideStats>
            <Head title="Notifications" />

            <div className="mb-4 flex items-center justify-between gap-3">
                <div>
                    <h1 className="text-xl font-bold">Notifications</h1>
                    <p className="text-muted-foreground text-xs">
                        Last 50 messages
                        {unread_count > 0 && (
                            <span className="ml-1">
                                · <strong className="text-red-600">{unread_count} unread</strong>
                            </span>
                        )}
                    </p>
                </div>
                {unread_count > 0 && (
                    <button
                        type="button"
                        onClick={markAllRead}
                        className="bg-primary/10 text-primary hover:bg-primary/20 flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-semibold"
                    >
                        <CheckCheck className="size-3.5" /> Mark all read
                    </button>
                )}
            </div>

            {items.length === 0 ? (
                <div className="border-border bg-card rounded-2xl border border-dashed py-12 text-center">
                    <Inbox className="text-muted-foreground mx-auto mb-3 size-10" />
                    <p className="text-card-foreground text-sm font-semibold">No notifications yet</p>
                    <p className="text-muted-foreground mt-1 text-xs">
                        Order updates and rewards will appear here.
                    </p>
                </div>
            ) : (
                <ul className="space-y-2">
                    {items.map((n) => {
                        const isUnread = n.read_at === null;
                        return (
                            <li key={n.id}>
                                <button
                                    type="button"
                                    onClick={() => handleClick(n)}
                                    className={cn(
                                        'border-border bg-card flex w-full items-start gap-3 rounded-2xl border p-3 text-left shadow-sm transition-all hover:-translate-y-0.5 hover:shadow-md',
                                        isUnread && 'border-amber-200 bg-amber-50/60',
                                    )}
                                >
                                    <div
                                        className={cn(
                                            'mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-full',
                                            isUnread
                                                ? 'bg-amber-100 text-amber-800'
                                                : 'bg-muted text-muted-foreground',
                                        )}
                                    >
                                        {isUnread ? (
                                            <Bell className="size-4" />
                                        ) : (
                                            <BellOff className="size-4" />
                                        )}
                                    </div>
                                    <div className="min-w-0 flex-1">
                                        <div className="flex items-baseline justify-between gap-2">
                                            <p
                                                className={cn(
                                                    'text-sm leading-tight',
                                                    isUnread ? 'font-bold' : 'font-medium',
                                                )}
                                            >
                                                {n.title}
                                            </p>
                                            <span className="text-muted-foreground shrink-0 text-[10px]">
                                                {n.human_time}
                                            </span>
                                        </div>
                                        {n.body && (
                                            <p className="text-muted-foreground mt-0.5 text-xs leading-snug">
                                                {n.body}
                                            </p>
                                        )}
                                    </div>
                                    {isUnread && (
                                        <span
                                            className="mt-2 size-2 shrink-0 rounded-full bg-red-500"
                                            aria-hidden
                                        />
                                    )}
                                </button>
                            </li>
                        );
                    })}
                </ul>
            )}

            <p className="text-muted-foreground mt-6 text-center text-[11px]">
                Looking for past orders?{' '}
                <Link href="/orders" className="font-semibold text-amber-700 hover:underline">
                    View order history →
                </Link>
            </p>
        </StorefrontLayout>
    );
}
