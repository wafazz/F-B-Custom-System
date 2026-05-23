<div class="space-y-3">
    <div class="flex items-center justify-between text-sm text-gray-600 dark:text-gray-300">
        <span>
            Total redemptions:
            <strong class="text-gray-900 dark:text-white">{{ $voucher->used_count ?? $redemptions->count() }}</strong>
        </span>
        <span>
            Total discount given:
            <strong class="text-gray-900 dark:text-white">RM{{ number_format((float) $redemptions->sum('discount_amount'), 2) }}</strong>
        </span>
    </div>

    @if ($redemptions->isEmpty())
        <div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 p-6 text-center text-sm text-gray-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400">
            No one has used this voucher yet.
        </div>
    @else
        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
            <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                <thead class="bg-gray-50 text-xs uppercase tracking-wider text-gray-500 dark:bg-gray-900 dark:text-gray-400">
                    <tr>
                        <th class="px-3 py-2 text-left font-semibold">Customer</th>
                        <th class="px-3 py-2 text-left font-semibold">Contact</th>
                        <th class="px-3 py-2 text-left font-semibold">Order</th>
                        <th class="px-3 py-2 text-right font-semibold">Discount</th>
                        <th class="px-3 py-2 text-right font-semibold">When</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-800 dark:bg-gray-950">
                    @foreach ($redemptions as $r)
                        <tr>
                            <td class="px-3 py-2 font-medium text-gray-900 dark:text-white">
                                {{ $r->user?->name ?? '— Guest —' }}
                            </td>
                            <td class="px-3 py-2 text-gray-600 dark:text-gray-300">
                                @if ($r->user)
                                    <div class="text-xs">{{ $r->user->email }}</div>
                                    @if ($r->user->phone)
                                        <div class="text-xs text-gray-500">{{ $r->user->phone }}</div>
                                    @endif
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-3 py-2">
                                @if ($r->order)
                                    <a href="{{ route('filament.admin.resources.orders.view', $r->order_id) }}"
                                       class="font-mono text-xs text-primary-600 hover:underline dark:text-primary-400">
                                        #{{ $r->order->number }}
                                    </a>
                                    <div class="text-[10px] text-gray-500">RM{{ number_format((float) $r->order->total, 2) }}</div>
                                @else
                                    <span class="text-xs text-gray-400">deleted</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-right font-semibold text-emerald-600 dark:text-emerald-400">
                                −RM{{ number_format((float) $r->discount_amount, 2) }}
                            </td>
                            <td class="px-3 py-2 text-right text-xs text-gray-500">
                                {{ $r->created_at?->format('d M Y, H:i') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($redemptions->count() >= 200)
            <p class="text-center text-xs text-gray-400">
                Showing the most recent 200 redemptions.
            </p>
        @endif
    @endif
</div>
