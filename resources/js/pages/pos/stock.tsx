import { Head, router } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { useState } from 'react';
import PosLayout from '@/layouts/pos-layout';

interface ProductRow {
    product_id: number;
    name: string;
    sku: string;
    category: string | null;
    is_available: boolean;
    track_quantity: boolean;
    quantity: number;
    low_threshold: number;
}

interface Props {
    products: ProductRow[];
    branch: { id: number; code: string; name: string };
    staff: { name: string };
}

export default function PosStock({ products, branch }: Props) {
    const [filter, setFilter] = useState('');
    const visible = filter
        ? products.filter((p) =>
              [p.name, p.sku, p.category].join(' ').toLowerCase().includes(filter.toLowerCase()),
          )
        : products;

    function toggle(productId: number) {
        router.post(`/pos/stock/${productId}/toggle`, {}, { preserveScroll: true });
    }

    return (
        <PosLayout>
            <Head title={`Stock · ${branch.name}`} />

            <div className="mb-4 flex items-center justify-between">
                <div>
                    <h2 className="text-xl font-bold">Stock control</h2>
                    <p className="text-xs text-slate-400">
                        Toggle items off when supplies run out — customers see updates instantly.
                    </p>
                </div>
                <div className="relative">
                    <Search className="absolute top-1/2 left-3 size-3.5 -translate-y-1/2 text-slate-500" />
                    <input
                        value={filter}
                        onChange={(e) => setFilter(e.target.value)}
                        placeholder="Search items…"
                        className="rounded-md border border-slate-700 bg-slate-900 py-1.5 pr-3 pl-8 text-sm"
                    />
                </div>
            </div>

            <div className="overflow-hidden rounded-lg border border-slate-800">
                <table className="w-full text-sm">
                    <thead className="bg-slate-900 text-xs text-slate-400 uppercase">
                        <tr>
                            <th className="px-4 py-3 text-left">Item</th>
                            <th className="px-4 py-3 text-left">Category</th>
                            <th className="px-4 py-3 text-right">Stock</th>
                            <th className="px-4 py-3 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-800 bg-slate-950">
                        {visible.map((p) => {
                            const lowQty = p.track_quantity && p.quantity <= p.low_threshold;
                            return (
                                <tr key={p.product_id}>
                                    <td className="px-4 py-3">
                                        <p className="font-semibold">{p.name}</p>
                                        <p className="text-[10px] text-slate-500">{p.sku}</p>
                                    </td>
                                    <td className="px-4 py-3 text-slate-400">
                                        {p.category ?? '—'}
                                    </td>
                                    <td className="px-4 py-3 text-right">
                                        {p.track_quantity ? (
                                            <span className={lowQty ? 'text-amber-400' : ''}>
                                                {p.quantity}
                                            </span>
                                        ) : (
                                            <span className="text-slate-500">untracked</span>
                                        )}
                                    </td>
                                    <td className="px-4 py-3 text-center">
                                        <button
                                            type="button"
                                            onClick={() => toggle(p.product_id)}
                                            className={`rounded-full px-3 py-1 text-xs font-semibold ${
                                                p.is_available
                                                    ? 'bg-emerald-700 text-white hover:bg-emerald-600'
                                                    : 'bg-red-800 text-white hover:bg-red-700'
                                            }`}
                                        >
                                            {p.is_available ? 'Available' : 'Out of stock'}
                                        </button>
                                    </td>
                                </tr>
                            );
                        })}
                    </tbody>
                </table>
            </div>
        </PosLayout>
    );
}
