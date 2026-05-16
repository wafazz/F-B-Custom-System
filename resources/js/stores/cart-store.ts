import { create } from 'zustand';
import { persist } from 'zustand/middleware';
import type { CartLine, MenuCombo, MenuProduct, SelectedModifier } from '@/types/menu';

interface CartState {
    branchId: number | null;
    lines: CartLine[];
    notes: string;
    add: (
        product: MenuProduct,
        modifiers: SelectedModifier[],
        quantity: number,
        branchId: number,
    ) => void;
    addCombo: (combo: MenuCombo, quantity: number, branchId: number) => void;
    increment: (lineId: string) => void;
    decrement: (lineId: string) => void;
    remove: (lineId: string) => void;
    setNotes: (notes: string) => void;
    clear: () => void;
    /** Returns true if the cart was cleared because branchId changed. */
    rebindBranch: (branchId: number) => boolean;
}

function lineKey(productId: number, modifiers: SelectedModifier[]): string {
    const sig = modifiers
        .map((m) => `${m.group_id}:${m.option_id}`)
        .sort()
        .join('|');
    return `${productId}#${sig}`;
}

function unitPrice(base: number, modifiers: SelectedModifier[]): number {
    return modifiers.reduce((sum, m) => sum + Number(m.price_delta), Number(base));
}

export const useCartStore = create<CartState>()(
    persist(
        (set, get) => ({
            branchId: null,
            lines: [],
            notes: '',
            addCombo: (combo, quantity, branchId) => {
                const key = `combo#${combo.id}`;
                const existing = get().lines.find((l) => l.id === key);
                const lines = existing
                    ? get().lines.map((l) =>
                          l.id === key ? { ...l, quantity: l.quantity + quantity } : l,
                      )
                    : [
                          ...get().lines,
                          {
                              id: key,
                              product_id: null,
                              combo_id: combo.id,
                              combo_items: combo.items,
                              name: combo.name,
                              image: combo.image,
                              unit_price: Number(combo.price),
                              tumbler_discount: 0,
                              quantity,
                              modifiers: [],
                          },
                      ];
                set({ lines, branchId });
            },
            add: (product, modifiers, quantity, branchId) => {
                const key = lineKey(product.id, modifiers);
                const existing = get().lines.find((l) => l.id === key);
                const lines = existing
                    ? get().lines.map((l) =>
                          l.id === key ? { ...l, quantity: l.quantity + quantity } : l,
                      )
                    : [
                          ...get().lines,
                          {
                              id: key,
                              product_id: product.id,
                              name: product.name,
                              image: product.image,
                              unit_price: unitPrice(product.price, modifiers),
                              tumbler_discount: Number(product.tumbler_discount ?? 0),
                              quantity,
                              modifiers,
                          },
                      ];
                set({ lines, branchId });
            },
            increment: (lineId) =>
                set({
                    lines: get().lines.map((l) =>
                        l.id === lineId ? { ...l, quantity: l.quantity + 1 } : l,
                    ),
                }),
            decrement: (lineId) =>
                set({
                    lines: get()
                        .lines.map((l) =>
                            l.id === lineId ? { ...l, quantity: l.quantity - 1 } : l,
                        )
                        .filter((l) => l.quantity > 0),
                }),
            remove: (lineId) => set({ lines: get().lines.filter((l) => l.id !== lineId) }),
            setNotes: (notes) => set({ notes }),
            clear: () => set({ lines: [], notes: '', branchId: null }),
            rebindBranch: (branchId) => {
                const current = get().branchId;
                if (current === branchId || current === null) {
                    set({ branchId });
                    return false;
                }
                set({ lines: [], notes: '', branchId });
                return true;
            },
        }),
        { name: 'star-coffee:cart' },
    ),
);

export function cartTotals(lines: CartLine[]): { itemCount: number; subtotal: number } {
    return lines.reduce(
        (acc, line) => ({
            itemCount: acc.itemCount + line.quantity,
            subtotal: acc.subtotal + line.unit_price * line.quantity,
        }),
        { itemCount: 0, subtotal: 0 },
    );
}
