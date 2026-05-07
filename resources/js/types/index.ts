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

export interface PageProps {
    name: string;
    auth: {
        user: User | null;
    };
    flash: Flash;
    ziggy: {
        url: string;
        port: number | null;
        defaults: Record<string, unknown>;
        routes: Record<string, unknown>;
        location: string;
    };
}
