import { Head, Link, useForm } from '@inertiajs/react';
import { type FormEvent } from 'react';
import AuthLayout from '@/layouts/auth-layout';

export default function Login() {
    const { data, setData, post, processing, errors, reset } = useForm({
        identifier: '',
        password: '',
        remember: false,
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post('/login', {
            onFinish: () => reset('password'),
        });
    };

    return (
        <AuthLayout title="Welcome back" description="Sign in to continue your coffee journey">
            <Head title="Login" />
            <form onSubmit={submit} className="space-y-4">
                <div className="space-y-1.5">
                    <label htmlFor="identifier" className="text-xs font-medium text-white/70">
                        Email or Phone
                    </label>
                    <input
                        id="identifier"
                        type="text"
                        value={data.identifier}
                        onChange={(e) => setData('identifier', e.target.value)}
                        autoComplete="username"
                        required
                        className="w-full rounded-md border border-white/10 bg-[#1a1a1a] px-3 py-2 text-sm text-white placeholder:text-white/30 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 focus:outline-none"
                    />
                    {errors.identifier && (
                        <p className="text-xs text-red-400">{errors.identifier}</p>
                    )}
                </div>
                <div className="space-y-1.5">
                    <label htmlFor="password" className="text-xs font-medium text-white/70">
                        Password
                    </label>
                    <input
                        id="password"
                        type="password"
                        value={data.password}
                        onChange={(e) => setData('password', e.target.value)}
                        autoComplete="current-password"
                        required
                        className="w-full rounded-md border border-white/10 bg-[#1a1a1a] px-3 py-2 text-sm text-white placeholder:text-white/30 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 focus:outline-none"
                    />
                    {errors.password && <p className="text-xs text-red-400">{errors.password}</p>}
                </div>
                <label className="flex items-center gap-2 text-xs text-white/70">
                    <input
                        type="checkbox"
                        checked={data.remember}
                        onChange={(e) => setData('remember', e.target.checked)}
                        className="size-4 accent-amber-500"
                    />
                    Remember me
                </label>
                <button
                    type="submit"
                    disabled={processing}
                    className="w-full rounded-md bg-amber-500 px-4 py-2.5 text-sm font-bold text-black shadow-[0_4px_16px_rgba(245,158,11,0.35)] transition-all hover:-translate-y-px hover:bg-amber-400 hover:shadow-[0_6px_22px_rgba(245,158,11,0.50)] disabled:opacity-60"
                >
                    {processing ? 'Signing in…' : 'Sign in'}
                </button>
                <p className="text-center text-xs text-white/55">
                    New here?{' '}
                    <Link href="/register" className="font-semibold text-amber-400 hover:underline">
                        Create an account
                    </Link>
                </p>
            </form>
        </AuthLayout>
    );
}
