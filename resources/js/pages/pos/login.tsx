import { Head, useForm } from '@inertiajs/react';
import { Coffee } from 'lucide-react';
import { Button } from '@/components/ui/button';

interface BranchOption {
    id: number;
    code: string;
    name: string;
}

interface Props {
    branches: BranchOption[];
}

export default function PosLogin({ branches }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        branch_id: branches[0]?.id ?? '',
        pin: '',
    });

    function onSubmit(e: React.FormEvent) {
        e.preventDefault();
        post('/pos/login');
    }

    return (
        <>
            <Head title="POS Login" />
            <div className="flex min-h-screen items-center justify-center bg-slate-950 p-6 text-slate-100">
                <form
                    onSubmit={onSubmit}
                    className="w-full max-w-sm space-y-4 rounded-2xl border border-slate-800 bg-slate-900 p-8 shadow-2xl"
                >
                    <div className="flex flex-col items-center">
                        <div className="mb-3 flex size-14 items-center justify-center rounded-full bg-amber-600 text-white">
                            <Coffee className="size-7" />
                        </div>
                        <h1 className="text-xl font-bold">Star Coffee POS</h1>
                        <p className="text-xs text-slate-400">Branch staff sign in</p>
                    </div>

                    <div className="space-y-1">
                        <label className="text-xs text-slate-400">Branch</label>
                        <select
                            value={String(data.branch_id)}
                            onChange={(e) => setData('branch_id', Number(e.target.value))}
                            className="w-full rounded-md border border-slate-700 bg-slate-800 px-3 py-2 text-sm"
                        >
                            {branches.map((b) => (
                                <option key={b.id} value={b.id}>
                                    {b.name} · {b.code}
                                </option>
                            ))}
                        </select>
                        {errors.branch_id && (
                            <p className="text-xs text-red-400">{errors.branch_id}</p>
                        )}
                    </div>

                    <div className="space-y-1">
                        <label className="text-xs text-slate-400">PIN</label>
                        <input
                            type="password"
                            inputMode="numeric"
                            pattern="\d{4,6}"
                            maxLength={6}
                            autoFocus
                            value={data.pin}
                            onChange={(e) => setData('pin', e.target.value.replace(/\D/g, ''))}
                            className="w-full rounded-md border border-slate-700 bg-slate-800 px-3 py-2 text-center text-2xl tracking-widest"
                            placeholder="••••"
                        />
                        {errors.pin && <p className="text-xs text-red-400">{errors.pin}</p>}
                    </div>

                    <Button
                        type="submit"
                        disabled={processing}
                        className="w-full bg-amber-600 hover:bg-amber-500"
                    >
                        {processing ? 'Signing in…' : 'Sign in'}
                    </Button>
                </form>
            </div>
        </>
    );
}
