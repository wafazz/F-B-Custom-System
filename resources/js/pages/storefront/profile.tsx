import { Head, Link, router, useForm } from '@inertiajs/react';
import { Bell, ChevronRight, Download, Home, Key, LogOut, Mail, Package, Phone, Trash2, Trophy, User } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import StorefrontLayout from '@/layouts/storefront-layout';
import { cn } from '@/lib/utils';

interface ProfileData {
    name: string;
    email: string;
    phone: string | null;
    date_of_birth: string | null;
    gender: string | null;
    address_line: string | null;
    city: string | null;
    postcode: string | null;
    state: string | null;
    preferred_branch_id: number | null;
    locale: string | null;
    marketing_consent: boolean;
    whatsapp_consent: boolean;
    push_consent: boolean;
    referral_code: string;
    created_at: string | null;
}

const MY_STATES = [
    'Johor',
    'Kedah',
    'Kelantan',
    'Kuala Lumpur',
    'Labuan',
    'Melaka',
    'Negeri Sembilan',
    'Pahang',
    'Perak',
    'Perlis',
    'Pulau Pinang',
    'Putrajaya',
    'Sabah',
    'Sarawak',
    'Selangor',
    'Terengganu',
];

interface Loyalty {
    balance: number;
    tier_name: string | null;
    tier_color: string | null;
    lifetime_spend: number;
}

interface BranchOption {
    id: number;
    name: string;
    code: string;
}

interface RecentOrder {
    id: number;
    number: string;
    status: 'pending' | 'preparing' | 'ready' | 'completed' | 'cancelled' | 'refunded';
    status_label: string;
    total: number;
    branch_name: string | null;
    items_summary: string;
    created_at: string | null;
}

interface Props {
    profile: ProfileData;
    loyalty: Loyalty;
    branches: BranchOption[];
    recent_orders: RecentOrder[];
}

const FIELD =
    'w-full rounded-md border border-border bg-background px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20';
const LABEL = 'text-xs font-medium text-muted-foreground';

export default function Profile({ profile, loyalty, branches, recent_orders }: Props) {
    const initialTab: 'profile' | 'security' | 'data' = (() => {
        if (typeof window === 'undefined') return 'profile';
        const q = new URLSearchParams(window.location.search).get('tab');
        if (q === 'security' || q === 'data') return q;
        return 'profile';
    })();
    const [tab, setTab] = useState<'profile' | 'security' | 'data'>(initialTab);
    const form = useForm({
        name: profile.name,
        phone: profile.phone ?? '',
        date_of_birth: profile.date_of_birth ?? '',
        gender: profile.gender ?? '',
        address_line: profile.address_line ?? '',
        city: profile.city ?? '',
        postcode: profile.postcode ?? '',
        state: profile.state ?? '',
        preferred_branch_id: profile.preferred_branch_id ?? '',
        locale: profile.locale ?? 'en',
        marketing_consent: profile.marketing_consent,
        whatsapp_consent: profile.whatsapp_consent,
        push_consent: profile.push_consent,
    });
    const passwordForm = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    function onSave(e: React.FormEvent) {
        e.preventDefault();
        form.put('/profile', { preserveScroll: true });
    }

    function onPasswordSave(e: React.FormEvent) {
        e.preventDefault();
        passwordForm.put('/profile/password', {
            preserveScroll: true,
            onSuccess: () =>
                passwordForm.reset('current_password', 'password', 'password_confirmation'),
        });
    }

    function logout() {
        router.post('/logout');
    }

    function deleteAccount() {
        if (
            !window.confirm(
                'Permanently delete your account? Your past orders are kept anonymously for accounting.',
            )
        )
            return;
        router.delete('/account');
    }

    return (
        <StorefrontLayout>
            <Head title="Profile" />

            <header className="border-border bg-card mb-4 flex items-center gap-4 rounded-2xl border p-5 shadow-sm">
                <div className="bg-primary/10 text-primary flex size-14 items-center justify-center rounded-full">
                    <User className="size-7" />
                </div>
                <div className="flex-1">
                    <p className="text-base font-bold">{profile.name}</p>
                    <p className="text-muted-foreground text-xs">{profile.email}</p>
                    {loyalty.tier_name && (
                        <p
                            className="mt-1 inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-semibold"
                            style={{ color: loyalty.tier_color ?? '#7c4a1e' }}
                        >
                            <Trophy className="size-3" /> {loyalty.tier_name} · {loyalty.balance}{' '}
                            pts
                        </p>
                    )}
                </div>
                <button
                    type="button"
                    onClick={logout}
                    className="text-muted-foreground hover:text-foreground rounded-md p-2"
                    aria-label="Sign out"
                >
                    <LogOut className="size-4" />
                </button>
            </header>

            <section className="mb-4">
                <div className="mb-2 flex items-center justify-between">
                    <h2 className="text-card-foreground flex items-center gap-1.5 text-sm font-bold">
                        <Package className="size-4" /> Recent orders
                    </h2>
                    <Link
                        href="/orders"
                        className="text-muted-foreground hover:text-amber-700 flex items-center gap-1 text-xs font-medium"
                    >
                        View all <ChevronRight className="size-3" />
                    </Link>
                </div>
                {recent_orders.length === 0 ? (
                    <div className="border-border bg-card text-muted-foreground flex flex-col items-center gap-2 rounded-xl border border-dashed p-6 text-sm">
                        <Package className="size-8 opacity-40" />
                        <p>No orders yet — your past orders will show up here.</p>
                    </div>
                ) : (
                    <ul className="space-y-2">
                        {recent_orders.slice(0, 3).map((order) => (
                            <li key={order.id}>
                                <Link
                                    href={`/orders/${order.id}`}
                                    className="border-border bg-card flex items-start gap-3 rounded-xl border p-3 shadow-sm transition-all hover:-translate-y-0.5 hover:shadow"
                                >
                                    <div
                                        className={cn(
                                            'mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-full',
                                            statusTint(order.status),
                                        )}
                                    >
                                        <Package className="size-4" />
                                    </div>
                                    <div className="min-w-0 flex-1">
                                        <div className="flex items-center justify-between gap-2">
                                            <p className="font-mono text-xs font-semibold">
                                                {order.number}
                                            </p>
                                            <span
                                                className={cn(
                                                    'rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide',
                                                    statusBadge(order.status),
                                                )}
                                            >
                                                {order.status_label}
                                            </span>
                                        </div>
                                        <p className="text-card-foreground mt-0.5 line-clamp-1 text-xs">
                                            {order.items_summary || 'Order items'}
                                        </p>
                                        <div className="text-muted-foreground mt-1 flex items-center justify-between gap-2 text-[10px]">
                                            <span className="truncate">
                                                {order.branch_name ?? '—'}
                                                {order.created_at &&
                                                    ` · ${new Date(order.created_at).toLocaleDateString('en-MY', { day: 'numeric', month: 'short', year: 'numeric' })}`}
                                            </span>
                                            <span className="text-primary shrink-0 text-xs font-bold">
                                                RM{order.total.toFixed(2)}
                                            </span>
                                        </div>
                                    </div>
                                    <ChevronRight className="text-muted-foreground mt-2 size-4 shrink-0" />
                                </Link>
                            </li>
                        ))}
                    </ul>
                )}
            </section>

            <div className="border-border bg-card mb-4 flex gap-1 overflow-x-auto rounded-full border p-1 text-xs">
                <TabBtn
                    active={tab === 'profile'}
                    onClick={() => setTab('profile')}
                    label="Details"
                />
                <TabBtn
                    active={tab === 'security'}
                    onClick={() => setTab('security')}
                    label="Security"
                />
                <TabBtn
                    active={tab === 'data'}
                    onClick={() => setTab('data')}
                    label="Privacy & Data"
                />
            </div>

            {tab === 'profile' && (
                <form onSubmit={onSave} className="space-y-3">
                    <Field>
                        <label className={LABEL}>Full name</label>
                        <input
                            type="text"
                            value={form.data.name}
                            onChange={(e) => form.setData('name', e.target.value)}
                            className={FIELD}
                            required
                        />
                        {form.errors.name && <Err>{form.errors.name}</Err>}
                    </Field>

                    <Field>
                        <label className={LABEL}>
                            <Mail className="mr-1 inline size-3" /> Email
                        </label>
                        <input value={profile.email} disabled className={cn(FIELD, 'opacity-60')} />
                    </Field>

                    <Field>
                        <label className={LABEL}>
                            <Phone className="mr-1 inline size-3" /> Phone
                        </label>
                        <input
                            type="tel"
                            value={form.data.phone}
                            onChange={(e) => form.setData('phone', e.target.value)}
                            className={FIELD}
                            required
                        />
                        {form.errors.phone && <Err>{form.errors.phone}</Err>}
                    </Field>

                    <div className="grid grid-cols-2 gap-3">
                        <Field>
                            <label className={LABEL}>Date of birth</label>
                            <input
                                type="date"
                                value={form.data.date_of_birth}
                                onChange={(e) => form.setData('date_of_birth', e.target.value)}
                                className={FIELD}
                            />
                        </Field>
                        <Field>
                            <label className={LABEL}>Gender</label>
                            <select
                                value={form.data.gender}
                                onChange={(e) => form.setData('gender', e.target.value)}
                                className={FIELD}
                            >
                                <option value="">—</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                        </Field>
                    </div>

                    <fieldset className="border-border bg-card space-y-3 rounded-xl border p-4">
                        <legend className="flex items-center gap-1 text-xs font-semibold">
                            <Home className="size-3" /> Address
                        </legend>
                        <Field>
                            <label className={LABEL}>Street address</label>
                            <input
                                type="text"
                                value={form.data.address_line}
                                onChange={(e) => form.setData('address_line', e.target.value)}
                                className={FIELD}
                                placeholder="e.g. No. 12, Jalan Mawar"
                            />
                            {form.errors.address_line && <Err>{form.errors.address_line}</Err>}
                        </Field>
                        <div className="grid grid-cols-2 gap-3">
                            <Field>
                                <label className={LABEL}>City</label>
                                <input
                                    type="text"
                                    value={form.data.city}
                                    onChange={(e) => form.setData('city', e.target.value)}
                                    className={FIELD}
                                    placeholder="e.g. Petaling Jaya"
                                />
                                {form.errors.city && <Err>{form.errors.city}</Err>}
                            </Field>
                            <Field>
                                <label className={LABEL}>Postcode</label>
                                <input
                                    type="text"
                                    inputMode="numeric"
                                    value={form.data.postcode}
                                    onChange={(e) => form.setData('postcode', e.target.value)}
                                    className={FIELD}
                                    placeholder="e.g. 47301"
                                    maxLength={6}
                                />
                                {form.errors.postcode && <Err>{form.errors.postcode}</Err>}
                            </Field>
                        </div>
                        <Field>
                            <label className={LABEL}>State</label>
                            <select
                                value={form.data.state}
                                onChange={(e) => form.setData('state', e.target.value)}
                                className={FIELD}
                            >
                                <option value="">Select state</option>
                                {MY_STATES.map((s) => (
                                    <option key={s} value={s}>
                                        {s}
                                    </option>
                                ))}
                            </select>
                            {form.errors.state && <Err>{form.errors.state}</Err>}
                        </Field>
                    </fieldset>

                    <Field>
                        <label className={LABEL}>Preferred branch</label>
                        <select
                            value={form.data.preferred_branch_id}
                            onChange={(e) =>
                                form.setData(
                                    'preferred_branch_id',
                                    e.target.value === '' ? '' : Number(e.target.value),
                                )
                            }
                            className={FIELD}
                        >
                            <option value="">No preference</option>
                            {branches.map((b) => (
                                <option key={b.id} value={b.id}>
                                    {b.name} · {b.code}
                                </option>
                            ))}
                        </select>
                    </Field>

                    <Field>
                        <label className={LABEL}>Language</label>
                        <select
                            value={form.data.locale}
                            onChange={(e) => form.setData('locale', e.target.value)}
                            className={FIELD}
                        >
                            <option value="en">English</option>
                            <option value="ms">Bahasa Malaysia</option>
                        </select>
                    </Field>

                    <fieldset className="border-border bg-card space-y-2 rounded-xl border p-4">
                        <legend className="text-xs font-semibold">Notifications</legend>
                        <Toggle
                            label="Marketing emails"
                            checked={form.data.marketing_consent}
                            onChange={(v) => form.setData('marketing_consent', v)}
                        />
                        <Toggle
                            label="WhatsApp messages"
                            checked={form.data.whatsapp_consent}
                            onChange={(v) => form.setData('whatsapp_consent', v)}
                        />
                        <Toggle
                            label="Push notifications"
                            checked={form.data.push_consent}
                            onChange={(v) => form.setData('push_consent', v)}
                            icon={<Bell className="size-3" />}
                        />
                    </fieldset>

                    <Button type="submit" disabled={form.processing} className="w-full">
                        {form.processing ? 'Saving…' : 'Save changes'}
                    </Button>
                </form>
            )}

            {tab === 'security' && (
                <form onSubmit={onPasswordSave} className="space-y-3">
                    <Field>
                        <label className={LABEL}>Current password</label>
                        <input
                            type="password"
                            value={passwordForm.data.current_password}
                            onChange={(e) =>
                                passwordForm.setData('current_password', e.target.value)
                            }
                            className={FIELD}
                            autoComplete="current-password"
                            required
                        />
                        {passwordForm.errors.current_password && (
                            <Err>{passwordForm.errors.current_password}</Err>
                        )}
                    </Field>
                    <Field>
                        <label className={LABEL}>New password</label>
                        <input
                            type="password"
                            value={passwordForm.data.password}
                            onChange={(e) => passwordForm.setData('password', e.target.value)}
                            className={FIELD}
                            autoComplete="new-password"
                            required
                        />
                        {passwordForm.errors.password && <Err>{passwordForm.errors.password}</Err>}
                    </Field>
                    <Field>
                        <label className={LABEL}>Confirm new password</label>
                        <input
                            type="password"
                            value={passwordForm.data.password_confirmation}
                            onChange={(e) =>
                                passwordForm.setData('password_confirmation', e.target.value)
                            }
                            className={FIELD}
                            autoComplete="new-password"
                            required
                        />
                    </Field>
                    <Button type="submit" disabled={passwordForm.processing} className="w-full">
                        <Key className="mr-1.5 size-4" />
                        {passwordForm.processing ? 'Updating…' : 'Change password'}
                    </Button>
                </form>
            )}

            {tab === 'data' && (
                <div className="space-y-3">
                    <p className="text-muted-foreground text-xs">
                        Under Malaysia's PDPA you can request a copy of your data, and delete your
                        account at any time.
                    </p>

                    <a
                        href="/account/data-export"
                        className="border-border bg-card hover:bg-secondary/40 flex items-center gap-3 rounded-xl border p-4 shadow-sm"
                    >
                        <Download className="text-primary size-5" />
                        <div className="flex-1">
                            <p className="text-sm font-semibold">Download my data</p>
                            <p className="text-muted-foreground text-xs">
                                JSON export of profile, orders, and points.
                            </p>
                        </div>
                    </a>

                    <Link
                        href="/terms"
                        className="border-border bg-card hover:bg-secondary/40 flex items-center justify-between rounded-xl border p-4 text-sm shadow-sm"
                    >
                        <span>Terms & Conditions</span>
                        <span className="text-muted-foreground">→</span>
                    </Link>
                    <Link
                        href="/privacy"
                        className="border-border bg-card hover:bg-secondary/40 flex items-center justify-between rounded-xl border p-4 text-sm shadow-sm"
                    >
                        <span>Privacy Policy</span>
                        <span className="text-muted-foreground">→</span>
                    </Link>
                    <Link
                        href="/faq"
                        className="border-border bg-card hover:bg-secondary/40 flex items-center justify-between rounded-xl border p-4 text-sm shadow-sm"
                    >
                        <span>FAQ</span>
                        <span className="text-muted-foreground">→</span>
                    </Link>

                    <button
                        type="button"
                        onClick={deleteAccount}
                        className="flex w-full items-center gap-3 rounded-xl border border-red-200 bg-red-50 p-4 text-left text-sm hover:bg-red-100"
                    >
                        <Trash2 className="size-5 text-red-600" />
                        <div>
                            <p className="font-semibold text-red-700">Delete my account</p>
                            <p className="text-xs text-red-600/80">
                                Anonymises your data. Past orders are retained without your name.
                            </p>
                        </div>
                    </button>
                </div>
            )}
        </StorefrontLayout>
    );
}

function statusTint(status: RecentOrder['status']): string {
    switch (status) {
        case 'completed':
            return 'bg-emerald-100 text-emerald-700';
        case 'preparing':
            return 'bg-amber-100 text-amber-700';
        case 'ready':
            return 'bg-blue-100 text-blue-700';
        case 'cancelled':
        case 'refunded':
            return 'bg-red-100 text-red-700';
        case 'pending':
        default:
            return 'bg-gray-100 text-gray-700';
    }
}

function statusBadge(status: RecentOrder['status']): string {
    switch (status) {
        case 'completed':
            return 'bg-emerald-50 text-emerald-700';
        case 'preparing':
            return 'bg-amber-50 text-amber-700';
        case 'ready':
            return 'bg-blue-50 text-blue-700';
        case 'cancelled':
        case 'refunded':
            return 'bg-red-50 text-red-700';
        case 'pending':
        default:
            return 'bg-gray-50 text-gray-700';
    }
}

function Field({ children }: { children: React.ReactNode }) {
    return <div className="space-y-1">{children}</div>;
}

function Err({ children }: { children: React.ReactNode }) {
    return <p className="text-xs text-red-600">{children}</p>;
}

function TabBtn({
    active,
    onClick,
    label,
}: {
    active: boolean;
    onClick: () => void;
    label: string;
}) {
    return (
        <button
            type="button"
            onClick={onClick}
            className={cn(
                'flex-1 rounded-full px-3 py-1.5 font-medium transition-colors',
                active ? 'bg-primary text-primary-foreground' : 'text-muted-foreground',
            )}
        >
            {label}
        </button>
    );
}

function Toggle({
    label,
    checked,
    onChange,
    icon,
}: {
    label: string;
    checked: boolean;
    onChange: (v: boolean) => void;
    icon?: React.ReactNode;
}) {
    return (
        <label className="flex items-center justify-between gap-2 text-sm">
            <span className="flex items-center gap-2">
                {icon}
                {label}
            </span>
            <button
                type="button"
                role="switch"
                aria-checked={checked}
                onClick={() => onChange(!checked)}
                className={cn(
                    'relative h-5 w-9 rounded-full transition-colors',
                    checked ? 'bg-primary' : 'bg-secondary',
                )}
            >
                <span
                    className={cn(
                        'absolute top-0.5 size-4 rounded-full bg-white transition-transform',
                        checked ? 'translate-x-4' : 'translate-x-0.5',
                    )}
                />
            </button>
        </label>
    );
}
