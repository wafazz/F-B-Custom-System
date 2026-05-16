export interface User {
    id: number;
    name: string;
    email: string;
    phone?: string | null;
    photo?: string | null;
    email_verified_at?: string | null;
    phone_verified_at?: string | null;
    date_of_birth?: string | null;
    referral_code?: string | null;
    created_at: string;
    updated_at: string;
}

export interface Flash {
    success?: string;
    error?: string;
}

export interface CustomerStats {
    wallet_balance: number;
    points: number;
    lifetime_spend: number;
    tier: {
        name: string;
        color: string | null;
        multiplier: number;
    } | null;
    next_tier: {
        name: string;
        min_spend: number;
    } | null;
}

export interface PageProps {
    name: string;
    auth: {
        user: User | null;
    };
    flash: Flash;
    customer_stats: CustomerStats | null;
    ziggy: {
        url: string;
        port: number | null;
        defaults: Record<string, unknown>;
        routes: Record<string, unknown>;
        location: string;
    };
}
