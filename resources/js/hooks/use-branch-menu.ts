import { useQuery, useQueryClient } from '@tanstack/react-query';
import { useEffect } from 'react';
import { getEcho } from '@/lib/echo';
import type { MenuPayload, StockChangedEvent } from '@/types/menu';

export function useBranchMenu(branchId: number) {
    return useQuery<MenuPayload>({
        queryKey: ['menu', branchId],
        queryFn: async () => {
            const response = await fetch(`/api/branches/${branchId}/menu`, {
                headers: { Accept: 'application/json' },
            });
            if (!response.ok) throw new Error('Failed to load menu');
            return response.json() as Promise<MenuPayload>;
        },
    });
}

/** Subscribe to live stock changes and patch a known-out-of-stock set in cache. */
export function useStockSubscription(
    branchId: number,
    onChange: (event: StockChangedEvent) => void,
) {
    const qc = useQueryClient();

    useEffect(() => {
        const echo = getEcho();
        const channel = echo.channel(`branch.${branchId}.stock`);
        const handler = (event: StockChangedEvent) => {
            onChange(event);
            qc.invalidateQueries({ queryKey: ['menu', branchId] });
        };
        channel.listen('.stock.changed', handler);
        return () => {
            channel.stopListening('.stock.changed', handler);
            echo.leaveChannel(`branch.${branchId}.stock`);
        };
    }, [branchId, onChange, qc]);
}
