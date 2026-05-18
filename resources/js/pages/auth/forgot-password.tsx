import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { type FormEvent } from 'react';
import AuthLayout from '@/layouts/auth-layout';

export default function ForgotPassword() {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
    });

    const { flash } = usePage().props as unknown as {
        flash: { success?: string; error?: string };
    };

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post('/forgot-password', { preserveScroll: true });
    };

    return (
        <AuthLayout
            title="Forgot password"
            description="Enter the email on your account and we'll send a reset link"
        >
            <Head title="Forgot password" />

            {flash?.success && (
                <div className="mb-3 rounded-md border border-emerald-500/30 bg-emerald-500/10 px-3 py-2 text-xs text-emerald-300">
                    {flash.success}
                </div>
            )}

            <form onSubmit={submit} className="space-y-4">
                <div className="space-y-1.5">
                    <label htmlFor="email" className="text-xs font-medium text-white/70">
                        Email
                    </label>
                    <input
                        id="email"
                        type="email"
                        value={data.email}
                        onChange={(e) => setData('email', e.target.value)}
                        autoComplete="email"
                        required
                        className="w-full rounded-md border border-white/10 bg-[#1a1a1a] px-3 py-2 text-sm text-white placeholder:text-white/30 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 focus:outline-none"
                    />
                    {errors.email && <p className="text-xs text-red-400">{errors.email}</p>}
                </div>
                <p className="text-xs text-white/55">
                    The link expires in 60 minutes. Check your spam folder if it doesn't arrive
                    within a minute.
                </p>
                <button
                    type="submit"
                    disabled={processing}
                    className="w-full rounded-md bg-amber-500 px-4 py-2.5 text-sm font-bold text-black shadow-[0_4px_16px_rgba(245,158,11,0.35)] transition-all hover:-translate-y-px hover:bg-amber-400 hover:shadow-[0_6px_22px_rgba(245,158,11,0.50)] disabled:opacity-60"
                >
                    {processing ? 'Sending…' : 'Send reset link'}
                </button>
                <p className="text-center text-xs text-white/55">
                    Remembered it?{' '}
                    <Link href="/login" className="font-semibold text-amber-400 hover:underline">
                        Back to sign in
                    </Link>
                </p>
            </form>
        </AuthLayout>
    );
}
