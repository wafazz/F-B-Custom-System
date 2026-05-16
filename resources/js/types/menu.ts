export interface MenuModifierOption {
    id: number;
    name: string;
    price_delta: number;
    is_default: boolean;
}

export interface MenuModifierGroup {
    id: number;
    name: string;
    selection_type: 'single' | 'multiple';
    is_required: boolean;
    min_select: number;
    max_select: number;
    options: MenuModifierOption[];
}

export interface MenuProduct {
    id: number;
    sku: string;
    name: string;
    slug: string;
    description: string | null;
    price: number;
    base_price: number;
    tumbler_discount: number;
    image: string | null;
    gallery: string[] | null;
    calories: number | null;
    prep_time_minutes: number;
    is_featured: boolean;
    sst_applicable: boolean;
    modifier_groups: MenuModifierGroup[];
}

export interface MenuCategory {
    id: number;
    name: string;
    slug: string;
    icon: string | null;
    sort_order: number;
    products: MenuProduct[];
}

export interface MenuPayload {
    branch: {
        id: number;
        code: string;
        name: string;
        sst_rate: number;
        sst_enabled: boolean;
        status: string;
    };
    categories: MenuCategory[];
    message?: string;
}

export interface BranchSummary {
    id: number;
    code: string;
    name: string;
    address: string;
    city: string | null;
    state: string | null;
    phone: string | null;
    latitude: number | null;
    longitude: number | null;
    operating_hours: Record<string, { enabled: boolean; open: string; close: string }> | null;
    logo: string | null;
    cover_image?: string | null;
    is_open_now: boolean;
    debug_status?: string;
    debug_accepts_orders?: boolean;
    debug_today?: string;
    debug_today_hours?: { enabled?: boolean; open?: string; close?: string } | null;
}

export interface BranchContext {
    id: number;
    code: string;
    name: string;
    logo: string | null;
    cover_image: string | null;
    sst_rate: number;
    sst_enabled: boolean;
    service_charge_rate: number;
    service_charge_enabled: boolean;
    status: string;
    accepts_orders: boolean;
    is_open_now: boolean;
}

export interface SelectedModifier {
    group_id: number;
    group_name: string;
    option_id: number;
    option_name: string;
    price_delta: number;
}

export interface CartLine {
    id: string;
    product_id: number;
    name: string;
    image: string | null;
    unit_price: number;
    tumbler_discount: number;
    quantity: number;
    modifiers: SelectedModifier[];
    notes?: string;
}

export interface StockChangedEvent {
    product_id: number;
    is_available: boolean;
    quantity: number;
}
