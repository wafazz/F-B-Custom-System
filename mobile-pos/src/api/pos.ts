import AsyncStorage from '@react-native-async-storage/async-storage';
import { API_BASE } from '@/config';

const TOKEN_KEY = 'pos.token';
const BRANCH_KEY = 'pos.branch';

export interface PosBranch {
    id: number;
    code: string;
    name: string;
}

export interface PosStaff {
    id: number;
    name: string;
}

export interface PosOrder {
    id: number;
    number: string;
    status: 'pending' | 'preparing' | 'ready' | 'completed' | 'cancelled';
    order_type: 'pickup' | 'dine_in' | 'delivery';
    dine_in_table: string | null;
    customer_snapshot: { name?: string; phone?: string } | null;
    subtotal: number;
    total: number;
    created_at: string;
    items: {
        name: string;
        quantity: number;
        modifiers: string[];
        notes: string | null;
    }[];
}

async function token(): Promise<string | null> {
    return AsyncStorage.getItem(TOKEN_KEY);
}

async function request<T>(
    path: string,
    init: RequestInit = {},
): Promise<T> {
    const t = await token();
    const headers: Record<string, string> = {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        ...(init.headers as Record<string, string>),
    };
    if (t) headers.Authorization = `Bearer ${t}`;

    const res = await fetch(`${API_BASE}${path}`, { ...init, headers });
    const body = await res.text();
    const parsed = body ? (JSON.parse(body) as T & { message?: string }) : ({} as T);
    if (!res.ok) {
        throw new Error(
            (parsed as { message?: string }).message ?? `HTTP ${res.status}`,
        );
    }
    return parsed;
}

export async function login(branchCode: string, pin: string) {
    const result = await request<{
        token: string;
        branch: PosBranch;
        staff: PosStaff;
    }>('/api/pos/login', {
        method: 'POST',
        body: JSON.stringify({ branch_code: branchCode, pin }),
    });
    await AsyncStorage.setItem(TOKEN_KEY, result.token);
    await AsyncStorage.setItem(BRANCH_KEY, JSON.stringify(result.branch));
    return result;
}

export async function logout() {
    try {
        await request('/api/pos/logout', { method: 'POST' });
    } catch {
        /* swallow — clearing local is enough */
    }
    await AsyncStorage.multiRemove([TOKEN_KEY, BRANCH_KEY]);
}

export async function getStoredBranch(): Promise<PosBranch | null> {
    const v = await AsyncStorage.getItem(BRANCH_KEY);
    return v ? (JSON.parse(v) as PosBranch) : null;
}

export async function hasSession(): Promise<boolean> {
    return (await token()) !== null;
}

export async function fetchQueue(branchId: number): Promise<PosOrder[]> {
    const data = await request<{ orders: PosOrder[] }>(
        `/api/pos/branches/${branchId}/queue`,
    );
    return data.orders;
}

export async function transitionOrder(
    orderId: number,
    status: PosOrder['status'],
) {
    return request<{ order: PosOrder }>(`/api/pos/orders/${orderId}/transition`, {
        method: 'POST',
        body: JSON.stringify({ status }),
    });
}
