import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Printer } from 'lucide-react';
import { Button } from '@/components/ui/button';
import PosLayout from '@/layouts/pos-layout';
import { cn } from '@/lib/utils';

interface Props {
    branch: { id: number | null; code: string | null; name: string | null };
    staff: { name: string };
    shift: {
        id: number;
        opened_at: string;
        closed_at: string | null;
        opened_by: string | null;
        closed_by: string | null;
        opening_float: number;
        expected_cash: number;
        counted_cash: number;
        variance: number;
        notes: string | null;
    };
    summary: {
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
        movements: {
            id: number;
            type: 'cash_in' | 'cash_out';
            amount: number;
            reason: string;
            recorded_at: string | null;
        }[];
    };
}

export default function PosShiftReport({ branch, shift, summary }: Props) {
    const opened = new Date(shift.opened_at);
    const closed = shift.closed_at ? new Date(shift.closed_at) : null;
    const duration =
        closed
            ? Math.round((closed.getTime() - opened.getTime()) / 60000)
            : null;

    return (
        <PosLayout>
            <Head title={`Z-Report · Shift #${shift.id}`} />

            <div className="mx-auto max-w-2xl">
                <div className="mb-3 flex items-center justify-between print:hidden">
                    <Link
                        href="/pos/shift"
                        className="flex items-center gap-1 text-xs text-slate-400 hover:text-slate-200"
                    >
                        <ArrowLeft className="size-3.5" /> Back
                    </Link>
                    <Button
                        onClick={() => window.print()}
                        className="bg-amber-600 hover:bg-amber-500"
                    >
                        <Printer className="mr-1.5 size-4" /> Print
                    </Button>
                </div>

                <article
                    id="z-report"
                    className="rounded-xl border border-slate-700 bg-white p-6 text-slate-900 shadow print:rounded-none print:border-0 print:p-0 print:shadow-none"
                >
                    <header className="text-center">
                        <h1 className="text-lg font-black tracking-wider uppercase">Z-Report</h1>
                        <p className="text-xs">
                            {branch.name} · {branch.code}
                        </p>
                        <p className="mt-1 text-[10px] text-slate-500">
                            Shift #{shift.id}
                        </p>
                    </header>

                    <hr className="my-3 border-dashed border-slate-300" />

                    <section className="grid grid-cols-2 gap-1 text-xs">
                        <span className="text-slate-500">Opened</span>
                        <span className="text-right">
                            {opened.toLocaleString('en-MY', {
                                dateStyle: 'medium',
                                timeStyle: 'short',
                            })}
                        </span>
                        <span className="text-slate-500">Closed</span>
                        <span className="text-right">
                            {closed
                                ? closed.toLocaleString('en-MY', {
                                      dateStyle: 'medium',
                                      timeStyle: 'short',
                                  })
                                : '—'}
                        </span>
                        {duration !== null && (
                            <>
                                <span className="text-slate-500">Duration</span>
                                <span className="text-right">
                                    {Math.floor(duration / 60)}h {duration % 60}m
                                </span>
                            </>
                        )}
                        <span className="text-slate-500">Opened by</span>
                        <span className="text-right">{shift.opened_by ?? '—'}</span>
                        <span className="text-slate-500">Closed by</span>
                        <span className="text-right">{shift.closed_by ?? '—'}</span>
                    </section>

                    <hr className="my-3 border-dashed border-slate-300" />

                    <h2 className="mb-1 text-xs font-bold uppercase tracking-wider">
                        Sales by method
                    </h2>
                    <section className="text-xs">
                        <Line label="Cash" v={summary.cash_sales} />
                        <Line label="Card" v={summary.card_sales} />
                        <Line label="DuitNow" v={summary.duitnow_sales} />
                        <Line label="Wallet" v={summary.wallet_sales} />
                        {summary.other_sales > 0 && (
                            <Line label="Other" v={summary.other_sales} />
                        )}
                        <Line label="Gross sales" v={summary.gross_sales} bold />
                        <Line
                            label={`${summary.order_count} ${summary.order_count === 1 ? 'order' : 'orders'}`}
                            v={null}
                            muted
                        />
                    </section>

                    <hr className="my-3 border-dashed border-slate-300" />

                    <h2 className="mb-1 text-xs font-bold uppercase tracking-wider">
                        Cash drawer
                    </h2>
                    <section className="text-xs">
                        <Line label="Opening float" v={summary.opening_float} />
                        <Line label="+ Cash sales" v={summary.cash_sales} />
                        <Line label="+ Cash in" v={summary.cash_in_total} />
                        <Line label="− Cash out" v={-summary.cash_out_total} />
                        <Line label="Expected cash" v={shift.expected_cash} bold />
                        <Line label="Counted cash" v={shift.counted_cash} bold />
                        <Line label="Variance" v={shift.variance} bold variance />
                    </section>

                    {summary.movements.length > 0 && (
                        <>
                            <hr className="my-3 border-dashed border-slate-300" />
                            <h2 className="mb-1 text-xs font-bold uppercase tracking-wider">
                                Cash movements
                            </h2>
                            <ul className="space-y-0.5 text-[11px]">
                                {summary.movements.map((m) => (
                                    <li key={m.id} className="flex justify-between">
                                        <span>
                                            {m.type === 'cash_in' ? '+' : '−'} {m.reason}
                                        </span>
                                        <span
                                            className={cn(
                                                'tabular-nums',
                                                m.type === 'cash_in'
                                                    ? 'text-emerald-700'
                                                    : 'text-red-700',
                                            )}
                                        >
                                            {m.type === 'cash_in' ? '+' : '−'}RM{m.amount.toFixed(2)}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        </>
                    )}

                    {shift.notes && (
                        <>
                            <hr className="my-3 border-dashed border-slate-300" />
                            <p className="text-[11px] italic text-slate-700">{shift.notes}</p>
                        </>
                    )}

                    <hr className="my-3 border-dashed border-slate-300" />
                    <p className="text-center text-[10px] text-slate-500">
                        End of shift report
                    </p>
                </article>
            </div>
        </PosLayout>
    );
}

function Line({
    label,
    v,
    bold,
    muted,
    variance,
}: {
    label: string;
    v: number | null;
    bold?: boolean;
    muted?: boolean;
    variance?: boolean;
}) {
    const display =
        v === null
            ? null
            : `${v < 0 ? '−' : ''}RM${Math.abs(v).toFixed(2)}`;
    return (
        <div
            className={cn(
                'flex justify-between',
                bold && 'font-bold',
                muted && 'text-slate-500',
            )}
        >
            <span>{label}</span>
            {display !== null && (
                <span
                    className={cn(
                        'tabular-nums',
                        variance && v !== null && v !== 0
                            ? v > 0
                                ? 'text-emerald-700'
                                : 'text-red-700'
                            : '',
                    )}
                >
                    {variance && v !== null && v > 0 ? '+' : ''}
                    {display}
                </span>
            )}
        </div>
    );
}
