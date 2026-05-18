import { Head, useForm } from '@inertiajs/react';
import { type FormEvent } from 'react';
import AuthLayout from '@/layouts/auth-layout';

interface Props {
    token: string;
    email: string;
}

export default function ResetPassword({ token, email }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        token,
        email,
        password: '',
        password_confirmation: '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post('/reset-password');
    };

    return (
        <AuthLayout title="Choose a new password" description="At least 8 characters">
            <Head title="Reset password" />
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
                        readOnly={Boolean(email)}
                        className="w-full rounded-md border border-white/10 bg-[#1a1a1a] px-3 py-2 text-sm text-white placeholder:text-white/30 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 focus:outline-none disabled:opacity-60"
                    />
                    {errors.email && <p className="text-xs text-red-400">{errors.email}</p>}
                </div>
                <div className="space-y-1.5">
                    <label htmlFor="password" className="text-xs font-medium text-white/70">
                        New password
                    </label>
                    <input
                        id="password"
                        type="password"
                        value={data.password}
                        onChange={(e) => setData('password', e.target.value)}
                        autoComplete="new-password"
                        required
                        minLength={8}
                        className="w-full rounded-md border border-white/10 bg-[#1a1a1a] px-3 py-2 text-sm text-white placeholder:text-white/30 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 focus:outline-none"
                    />
                    {errors.password && <p className="text-xs text-red-400">{errors.password}</p>}
                </div>
                <div className="space-y-1.5">
                    <label
                        htmlFor="password_confirmation"
                        className="text-xs font-medium text-white/70"
                    >
                        Confirm new password
                    </label>
                    <input
                        id="password_confirmation"
                        type="password"
                        value={data.password_confirmation}
                        onChange={(e) => setData('password_confirmation', e.target.value)}
                        autoComplete="new-password"
                        required
                        minLength={8}
                        className="w-full rounded-md border border-white/10 bg-[#1a1a1a] px-3 py-2 text-sm text-white placeholder:text-white/30 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 focus:outline-none"
                    />
                </div>
                <button
                    type="submit"
                    disabled={processing}
                    className="w-full rounded-md bg-amber-500 px-4 py-2.5 text-sm font-bold text-black shadow-[0_4px_16px_rgba(245,158,11,0.35)] transition-all hover:-translate-y-px hover:bg-amber-400 hover:shadow-[0_6px_22px_rgba(245,158,11,0.50)] disabled:opacity-60"
                >
                    {processing ? 'Saving…' : 'Reset password'}
                </button>
            </form>
        </AuthLayout>
    );
}
