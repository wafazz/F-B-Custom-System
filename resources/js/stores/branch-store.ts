import { create } from 'zustand';
import { persist } from 'zustand/middleware';
import type { BranchSummary } from '@/types/menu';

interface BranchState {
    selected: BranchSummary | null;
    setBranch: (branch: BranchSummary) => void;
    clear: () => void;
}

export const useBranchStore = create<BranchState>()(
    persist(
        (set) => ({
            selected: null,
            setBranch: (branch) => set({ selected: branch }),
            clear: () => set({ selected: null }),
        }),
        { name: 'star-coffee:branch' },
    ),
);
