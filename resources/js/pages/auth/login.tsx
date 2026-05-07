import { Head, Link, useForm } from '@inertiajs/react';
import { type FormEvent } from 'react';
import AuthLayout from '@/layouts/auth-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

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
        <AuthLayout title="Welcome back" description="Login to continue your coffee journey">
            <Head title="Login" />
            <form onSubmit={submit} className="space-y-4">
                <div className="space-y-2">
                    <Label htmlFor="identifier">Email or Phone</Label>
                    <Input
                        id="identifier"
                        type="text"
                        value={data.identifier}
                        onChange={(e) => setData('identifier', e.target.value)}
                        autoComplete="username"
                        required
                    />
                    {errors.identifier && (
                        <p className="text-xs text-destructive">{errors.identifier}</p>
                    )}
                </div>
                <div className="space-y-2">
                    <Label htmlFor="password">Password</Label>
                    <Input
                        id="password"
                        type="password"
                        value={data.password}
                        onChange={(e) => setData('password', e.target.value)}
                        autoComplete="current-password"
                        required
                    />
                    {errors.password && (
                        <p className="text-xs text-destructive">{errors.password}</p>
                    )}
                </div>
                <label className="flex items-center gap-2 text-sm">
                    <input
                        type="checkbox"
                        checked={data.remember}
                        onChange={(e) => setData('remember', e.target.checked)}
                    />
                    Remember me
                </label>
                <Button type="submit" className="w-full" disabled={processing}>
                    {processing ? 'Logging in...' : 'Login'}
                </Button>
                <p className="text-center text-sm text-muted-foreground">
                    New here?{' '}
                    <Link href="/register" className="text-primary hover:underline">
                        Create an account
                    </Link>
                </p>
            </form>
        </AuthLayout>
    );
}
