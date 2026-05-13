import { Head, router, useForm } from '@inertiajs/react';
import {
    Banknote,
    Clock,
    DoorClosed,
    DoorOpen,
    Minus,
    Plus,
    Receipt,
    TrendingDown,
    TrendingUp,
} from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import PosLayout from '@/layouts/pos-layout';
import { cn } from '@/lib/utils';

interface CashMovement {
    id: number;
    type: 'cash_in' | 'cash_out';
    amount: number;
    reason: string;
    recorded_at: string | null;
}

interface ShiftSummary {
    opening_float: number;
    cash_sales: number;
    card_sales: number;
    duitnow_sales: number;
    wallet_sales: number;
    other_sales: number;
    gross_sales: number;
    order_count: number;
    cash_in_total: number;
    cash_out_total: number;
    expected_cash: number;
    movements: CashMovement[];
}

interface CurrentShift {
    id: number;
    opened_at: string;
    opened_by: string | null;
    opening_float: number;
    summary: ShiftSummary;
}

interface ShiftRow {
    id: number;
    opened_at: string;
    closed_at: string | null;
    opened_by: string | null;
    closed_by: string | null;
    opening_float: number;
    expected_cash: number;
    counted_cash: number;
    variance: number;
}

interface Props {
    branch: { id: number; code: string; name: string };
    staff: { name: string };
    current: CurrentShift | null;
    recent: ShiftRow[];
}

export default function PosShiftIndex({ current, recent }: Props) {
    return (
        <PosLayout>
            <Head title="POS · Cash & Shift" />
            <div className="space-y-4">
                {current ? <ActiveShift shift={current} /> : <OpenShiftForm />}
                <RecentShifts recent={recent} />
            </div>
        </PosLayout>
    );
}

function OpenShiftForm() {
    const { data, setData, post, processing, errors } = useForm({ opening_float: '' });

    return (
        <section className="rounded-xl border border-slate-700 bg-slate-900 p-6 shadow">
            <div className="mb-4 flex items-center gap-3">
                <div className="flex size-10 items-center justify-center rounded-full bg-amber-900/40 text-amber-400">
                    <DoorOpen className="size-5" />
                </div>
                <div>
                    <h1 className="text-lg font-bold">No active shift</h1>
                    <p className="text-xs text-slate-400">
                        Open a shift before taking orders. Enter the cash float you're starting with.
                    </p>
                </div>
            </div>
            <form
                onSubmit={(e) => {
                    e.preventDefault();
                    post('/pos/shift/open', { preserveScroll: true });
                }}
                className="flex flex-col gap-3 sm:flex-row sm:items-end"
            >
                <div className="flex-1">
                    <label className="mb-1 block text-xs font-medium text-slate-400">
                        Opening float (RM)
                    </label>
                    <input
                        type="number"
                        step="0.01"
                        min="0"
                        value={data.opening_float}
                        onChange={(e) => setData('opening_float', e.target.value)}
                        className="w-full rounded-md border border-slate-700 bg-slate-950 px-3 py-2 text-base font-semibold tabular-nums outline-none focus:border-amber-500"
                        placeholder="0.00"
                        autoFocus
                    />
                    {errors.opening_float && (
                        <p className="mt-1 text-xs text-red-400">{errors.opening_float}</p>
                    )}
                </div>
                <Button
                    type="submit"
                    disabled={processing || data.opening_float === ''}
                    className="bg-amber-600 hover:bg-amber-500 sm:w-auto"
                >
                    Open shift
                </Button>
            </form>
        </section>
    );
}

function ActiveShift({ shift }: { shift: CurrentShift }) {
    const [moveOpen, setMoveOpen] = useState<'cash_in' | 'cash_out' | null>(null);
    const [closeOpen, setCloseOpen] = useState(false);
    const s = shift.summary;
    const openedAt = new Date(shift.opened_at);

    return (
        <section className="space-y-3">
            <div className="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-emerald-800/60 bg-emerald-950/30 px-4 py-3">
                <div className="flex items-center gap-3">
                    <span className="flex size-2.5 animate-pulse rounded-full bg-emerald-400" />
                    <div>
                        <p className="text-sm font-bold text-emerald-200">Shift in progress</p>
                        <p className="text-xs text-emerald-400/80">
                            Opened {openedAt.toLocaleString('en-MY', { dateStyle: 'medium', timeStyle: 'short' })}
                            {shift.opened_by && ` · ${shift.opened_by}`}
                        </p>
                    </div>
                </div>
                <Button
                    onClick={() => setCloseOpen(true)}
                    className="bg-red-700 hover:bg-red-600"
                >
                    <DoorClosed className="mr-1.5 size-4" /> Close shift
                </Button>
            </div>

            <div className="grid grid-cols-2 gap-2 md:grid-cols-4">
                <Stat label="Opening float" value={s.opening_float} icon={<Banknote className="size-4" />} tone="slate" />
                <Stat label="Cash sales" value={s.cash_sales} icon={<Banknote className="size-4" />} tone="emerald" />
                <Stat label="Cash in" value={s.cash_in_total} icon={<TrendingUp className="size-4" />} tone="emerald" />
                <Stat label="Cash out" value={s.cash_out_total} icon={<TrendingDown className="size-4" />} tone="red" />
            </div>

            <div className="rounded-xl border-2 border-amber-500/60 bg-amber-900/20 p-4">
                <div className="flex items-baseline justify-between">
                    <div>
                        <p className="text-xs font-semibold uppercase tracking-wider text-amber-300">
                            Expected cash in drawer
                        </p>
                        <p className="text-[10px] text-amber-200/70">
                            Float + cash sales + cash-in − cash-out
                        </p>
                    </div>
                    <p className="text-3xl font-bold tabular-nums text-amber-100">
                        RM{s.expected_cash.toFixed(2)}
                    </p>
                </div>
            </div>

            <div className="grid grid-cols-1 gap-3 lg:grid-cols-[1fr_280px]">
                <div className="rounded-xl border border-slate-700 bg-slate-900 p-4">
                    <div className="mb-2 flex items-center justify-between">
                        <h3 className="text-sm font-bold">Cash movements</h3>
                        <div className="flex gap-1">
                            <button
                                type="button"
                                onClick={() => setMoveOpen('cash_in')}
                                className="flex items-center gap-1 rounded-md bg-emerald-700 px-2 py-1 text-xs font-semibold text-white hover:bg-emerald-600"
                            >
                                <Plus className="size-3" /> Cash in
                            </button>
                            <button
                                type="button"
                                onClick={() => setMoveOpen('cash_out')}
                                className="flex items-center gap-1 rounded-md bg-red-700 px-2 py-1 text-xs font-semibold text-white hover:bg-red-600"
                            >
                                <Minus className="size-3" /> Cash out
                            </button>
                        </div>
                    </div>
                    {s.movements.length === 0 ? (
                        <p className="rounded-md border border-dashed border-slate-700 px-3 py-6 text-center text-xs text-slate-500">
                            No cash movements yet
                        </p>
                    ) : (
                        <ul className="space-y-1 text-xs">
                            {s.movements.map((m) => (
                                <li
                                    key={m.id}
                                    className="flex items-center justify-between rounded-md border border-slate-800 bg-slate-950 px-2.5 py-1.5"
                                >
                                    <div className="flex items-center gap-2">
                                        <span
                                            className={cn(
                                                'flex size-5 items-center justify-center rounded-full text-white',
                                                m.type === 'cash_in' ? 'bg-emerald-700' : 'bg-red-700',
                                            )}
                                        >
                                            {m.type === 'cash_in' ? (
                                                <Plus className="size-2.5" />
                                            ) : (
                                                <Minus className="size-2.5" />
                                            )}
                                        </span>
                                        <span className="truncate text-slate-200">{m.reason}</span>
                                    </div>
                                    <span
                                        className={cn(
                                            'font-semibold tabular-nums',
                                            m.type === 'cash_in' ? 'text-emerald-400' : 'text-red-400',
                                        )}
                                    >
                                        {m.type === 'cash_in' ? '+' : '−'}RM{m.amount.toFixed(2)}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>

                <div className="rounded-xl border border-slate-700 bg-slate-900 p-4">
                    <h3 className="mb-2 text-sm font-bold">Sales by method</h3>
                    <Row label="Cash" v={s.cash_sales} />
                    <Row label="Card" v={s.card_sales} />
                    <Row label="DuitNow" v={s.duitnow_sales} />
                    <Row label="Wallet" v={s.wallet_sales} />
                    {s.other_sales > 0 && <Row label="Other" v={s.other_sales} />}
                    <div className="mt-2 flex items-baseline justify-between border-t border-slate-800 pt-2">
                        <span className="text-xs font-bold">Gross</span>
                        <span className="text-base font-bold tabular-nums text-amber-400">
                            RM{s.gross_sales.toFixed(2)}
                        </span>
                    </div>
                    <p className="mt-1 text-[10px] text-slate-500">
                        {s.order_count} {s.order_count === 1 ? 'order' : 'orders'}
                    </p>
                </div>
            </div>

            {moveOpen && (
                <MovementModal
                    type={moveOpen}
                    shiftId={shift.id}
                    onClose={() => setMoveOpen(null)}
                />
            )}

            {closeOpen && (
                <CloseShiftModal
                    shiftId={shift.id}
                    expected={s.expected_cash}
                    onClose={() => setCloseOpen(false)}
                />
            )}
        </section>
    );
}

function Stat({
    label,
    value,
    icon,
    tone,
}: {
    label: string;
    value: number;
    icon: React.ReactNode;
    tone: 'slate' | 'emerald' | 'red';
}) {
    const tones = {
        slate: 'text-slate-200',
        emerald: 'text-emerald-400',
        red: 'text-red-400',
    };
    return (
        <div className="rounded-lg border border-slate-700 bg-slate-900 p-3">
            <div className="mb-1 flex items-center gap-1.5 text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                {icon}
                {label}
            </div>
            <p className={cn('text-xl font-bold tabular-nums', tones[tone])}>
                RM{value.toFixed(2)}
            </p>
        </div>
    );
}

function Row({ label, v }: { label: string; v: number }) {
    return (
        <div className="flex justify-between text-xs text-slate-300">
            <span>{label}</span>
            <span className="tabular-nums">RM{v.toFixed(2)}</span>
        </div>
    );
}

function MovementModal({
    type,
    shiftId,
    onClose,
}: {
    type: 'cash_in' | 'cash_out';
    shiftId: number;
    onClose: () => void;
}) {
    const { data, setData, post, processing, errors } = useForm({
        type,
        amount: '',
        reason: '',
    });

    return (
        <div className="fixed inset-0 z-40 flex items-center justify-center bg-black/70 p-4">
            <div className="w-full max-w-sm rounded-xl border border-slate-700 bg-slate-900 p-5">
                <h2 className="mb-3 text-base font-bold">
                    {type === 'cash_in' ? 'Record cash in' : 'Record cash out'}
                </h2>
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        post(`/pos/shift/${shiftId}/movements`, {
                            preserveScroll: true,
                            onSuccess: () => onClose(),
                        });
                    }}
                    className="space-y-3"
                >
                    <div>
                        <label className="mb-1 block text-xs font-medium text-slate-400">Amount (RM)</label>
                        <input
                            type="number"
                            step="0.01"
                            min="0.01"
                            value={data.amount}
                            onChange={(e) => setData('amount', e.target.value)}
                            className="w-full rounded-md border border-slate-700 bg-slate-950 px-3 py-2 text-base font-semibold tabular-nums outline-none focus:border-amber-500"
                            autoFocus
                        />
                        {errors.amount && <p className="mt-1 text-xs text-red-400">{errors.amount}</p>}
                    </div>
                    <div>
                        <label className="mb-1 block text-xs font-medium text-slate-400">Reason</label>
                        <input
                            type="text"
                            value={data.reason}
                            onChange={(e) => setData('reason', e.target.value)}
                            placeholder={type === 'cash_in' ? 'e.g. additional float' : 'e.g. petty cash — pastry supplier'}
                            className="w-full rounded-md border border-slate-700 bg-slate-950 px-3 py-2 text-sm outline-none focus:border-amber-500"
                        />
                        {errors.reason && <p className="mt-1 text-xs text-red-400">{errors.reason}</p>}
                    </div>
                    <div className="mt-4 flex gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={onClose}
                            className="flex-1 border-slate-700 bg-transparent text-slate-200 hover:bg-slate-800"
                        >
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            disabled={processing}
                            className={cn(
                                'flex-1',
                                type === 'cash_in'
                                    ? 'bg-emerald-700 hover:bg-emerald-600'
                                    : 'bg-red-700 hover:bg-red-600',
                            )}
                        >
                            Record
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    );
}

function CloseShiftModal({
    shiftId,
    expected,
    onClose,
}: {
    shiftId: number;
    expected: number;
    onClose: () => void;
}) {
    const { data, setData, post, processing, errors } = useForm({
        counted_cash: '',
        notes: '',
    });
    const counted = Number(data.counted_cash) || 0;
    const variance = counted - expected;

    return (
        <div className="fixed inset-0 z-40 flex items-center justify-center bg-black/70 p-4">
            <div className="w-full max-w-md rounded-xl border border-slate-700 bg-slate-900 p-5">
                <h2 className="mb-3 text-base font-bold">Close shift</h2>
                <p className="mb-3 text-xs text-slate-400">
                    Count the physical cash in the drawer and enter the total. The variance shows the
                    difference vs expected.
                </p>
                <div className="mb-3 rounded-md border border-slate-700 bg-slate-950 p-3 text-sm">
                    <div className="flex justify-between text-slate-400">
                        <span>Expected</span>
                        <span className="tabular-nums">RM{expected.toFixed(2)}</span>
                    </div>
                    <div className="flex justify-between text-slate-400">
                        <span>Counted</span>
                        <span className="tabular-nums">
                            {counted > 0 ? `RM${counted.toFixed(2)}` : '—'}
                        </span>
                    </div>
                    {data.counted_cash !== '' && (
                        <div
                            className={cn(
                                'mt-1 flex justify-between border-t border-slate-800 pt-1 font-bold',
                                variance === 0
                                    ? 'text-slate-200'
                                    : variance > 0
                                      ? 'text-emerald-400'
                                      : 'text-red-400',
                            )}
                        >
                            <span>Variance</span>
                            <span className="tabular-nums">
                                {variance >= 0 ? '+' : ''}RM{variance.toFixed(2)}
                            </span>
                        </div>
                    )}
                </div>
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        post(`/pos/shift/${shiftId}/close`, { preserveScroll: true });
                    }}
                    className="space-y-3"
                >
                    <div>
                        <label className="mb-1 block text-xs font-medium text-slate-400">
                            Counted cash (RM)
                        </label>
                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            value={data.counted_cash}
                            onChange={(e) => setData('counted_cash', e.target.value)}
                            className="w-full rounded-md border border-slate-700 bg-slate-950 px-3 py-2 text-base font-semibold tabular-nums outline-none focus:border-amber-500"
                            autoFocus
                        />
                        {errors.counted_cash && (
                            <p className="mt-1 text-xs text-red-400">{errors.counted_cash}</p>
                        )}
                    </div>
                    <div>
                        <label className="mb-1 block text-xs font-medium text-slate-400">
                            Notes (optional)
                        </label>
                        <textarea
                            value={data.notes}
                            onChange={(e) => setData('notes', e.target.value)}
                            rows={2}
                            placeholder="e.g. RM5 short — gave change wrong on order #SC-0042"
                            className="w-full rounded-md border border-slate-700 bg-slate-950 px-3 py-2 text-xs outline-none focus:border-amber-500"
                        />
                    </div>
                    <div className="mt-4 flex gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={onClose}
                            className="flex-1 border-slate-700 bg-transparent text-slate-200 hover:bg-slate-800"
                        >
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            disabled={processing || data.counted_cash === ''}
                            className="flex-1 bg-red-700 hover:bg-red-600"
                        >
                            Close & print report
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    );
}

function RecentShifts({ recent }: { recent: ShiftRow[] }) {
    if (recent.length === 0) return null;
    return (
        <section className="rounded-xl border border-slate-700 bg-slate-900 p-4">
            <div className="mb-2 flex items-center gap-2">
                <Clock className="size-4 text-slate-400" />
                <h2 className="text-sm font-bold">Recent closed shifts</h2>
            </div>
            <ul className="divide-y divide-slate-800">
                {recent.map((s) => {
                    const closedAt = s.closed_at ? new Date(s.closed_at) : null;
                    return (
                        <li
                            key={s.id}
                            className="flex items-center justify-between gap-3 py-2.5 text-xs"
                        >
                            <div className="min-w-0 flex-1">
                                <p className="font-semibold text-slate-200">
                                    {closedAt
                                        ? closedAt.toLocaleString('en-MY', {
                                              dateStyle: 'medium',
                                              timeStyle: 'short',
                                          })
                                        : '—'}
                                </p>
                                <p className="text-[10px] text-slate-500">
                                    {s.opened_by} → {s.closed_by ?? '—'}
                                </p>
                            </div>
                            <div className="text-right">
                                <p className="text-slate-300 tabular-nums">
                                    Counted RM{s.counted_cash.toFixed(2)} · Expected RM
                                    {s.expected_cash.toFixed(2)}
                                </p>
                                <p
                                    className={cn(
                                        'text-[10px] font-semibold tabular-nums',
                                        s.variance === 0
                                            ? 'text-slate-500'
                                            : s.variance > 0
                                              ? 'text-emerald-400'
                                              : 'text-red-400',
                                    )}
                                >
                                    Variance {s.variance >= 0 ? '+' : ''}RM{s.variance.toFixed(2)}
                                </p>
                            </div>
                            <button
                                type="button"
                                onClick={() => router.get(`/pos/shift/${s.id}/report`)}
                                className="rounded-md border border-slate-700 p-1.5 text-slate-300 hover:border-amber-500 hover:text-amber-300"
                                title="View report"
                            >
                                <Receipt className="size-3.5" />
                            </button>
                        </li>
                    );
                })}
            </ul>
        </section>
    );
}
