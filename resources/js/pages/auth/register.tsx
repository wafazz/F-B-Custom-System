import { Head, Link, useForm } from '@inertiajs/react';
import { type FormEvent } from 'react';
import AuthLayout from '@/layouts/auth-layout';

const FIELD_CLASS =
    'w-full rounded-md border border-white/10 bg-[#1a1a1a] px-3 py-2 text-sm text-white placeholder:text-white/30 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/20';
const LABEL_CLASS = 'text-xs font-medium text-white/70';
const ERR_CLASS = 'text-xs text-red-400';

export default function Register() {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        email: '',
        phone: '',
        password: '',
        password_confirmation: '',
        referral_code: '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post('/register', {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <AuthLayout title="Create an account" description="Join Star Coffee rewards">
            <Head title="Register" />
            <form onSubmit={submit} className="space-y-3.5">
                <div className="space-y-1.5">
                    <label htmlFor="name" className={LABEL_CLASS}>
                        Full name
                    </label>
                    <input
                        id="name"
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        autoComplete="name"
                        required
                        className={FIELD_CLASS}
                    />
                    {errors.name && <p className={ERR_CLASS}>{errors.name}</p>}
                </div>
                <div className="space-y-1.5">
                    <label htmlFor="email" className={LABEL_CLASS}>
                        Email
                    </label>
                    <input
                        id="email"
                        type="email"
                        value={data.email}
                        onChange={(e) => setData('email', e.target.value)}
                        autoComplete="email"
                        required
                        className={FIELD_CLASS}
                    />
                    {errors.email && <p className={ERR_CLASS}>{errors.email}</p>}
                </div>
                <div className="space-y-1.5">
                    <label htmlFor="phone" className={LABEL_CLASS}>
                        Phone (Malaysian)
                    </label>
                    <input
                        id="phone"
                        type="tel"
                        value={data.phone}
                        onChange={(e) => setData('phone', e.target.value)}
                        placeholder="+60123456789"
                        autoComplete="tel"
                        required
                        className={FIELD_CLASS}
                    />
                    {errors.phone && <p className={ERR_CLASS}>{errors.phone}</p>}
                </div>
                <div className="space-y-1.5">
                    <label htmlFor="password" className={LABEL_CLASS}>
                        Password
                    </label>
                    <input
                        id="password"
                        type="password"
                        value={data.password}
                        onChange={(e) => setData('password', e.target.value)}
                        autoComplete="new-password"
                        required
                        className={FIELD_CLASS}
                    />
                    {errors.password && <p className={ERR_CLASS}>{errors.password}</p>}
                </div>
                <div className="space-y-1.5">
                    <label htmlFor="password_confirmation" className={LABEL_CLASS}>
                        Confirm password
                    </label>
                    <input
                        id="password_confirmation"
                        type="password"
                        value={data.password_confirmation}
                        onChange={(e) => setData('password_confirmation', e.target.value)}
                        autoComplete="new-password"
                        required
                        className={FIELD_CLASS}
                    />
                </div>
                <div className="space-y-1.5">
                    <label htmlFor="referral_code" className={LABEL_CLASS}>
                        Referral code <span className="text-white/40">(optional)</span>
                    </label>
                    <input
                        id="referral_code"
                        value={data.referral_code}
                        onChange={(e) => setData('referral_code', e.target.value.toUpperCase())}
                        placeholder="ABCD1234"
                        className={FIELD_CLASS}
                    />
                    {errors.referral_code && <p className={ERR_CLASS}>{errors.referral_code}</p>}
                </div>
                <button
                    type="submit"
                    disabled={processing}
                    className="w-full rounded-md bg-amber-500 px-4 py-2.5 text-sm font-bold text-black shadow-[0_4px_16px_rgba(245,158,11,0.35)] transition-all hover:-translate-y-px hover:bg-amber-400 hover:shadow-[0_6px_22px_rgba(245,158,11,0.50)] disabled:opacity-60"
                >
                    {processing ? 'Creating account…' : 'Create account'}
                </button>
                <p className="text-center text-xs text-white/55">
                    Already have an account?{' '}
                    <Link href="/login" className="font-semibold text-amber-400 hover:underline">
                        Sign in
                    </Link>
                </p>
            </form>
        </AuthLayout>
    );
}
