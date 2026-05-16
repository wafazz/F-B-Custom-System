@php
    /** @var array<string, mixed> $summary */
    /** @var iterable<array<string, mixed>> $by_branch */
    /** @var iterable<array<string, mixed>> $top_products */
    /** @var iterable<array<string, mixed>> $series */
    /** @var iterable<array<string, mixed>> $orders */
    /** @var bool $orders_truncated */
    /** @var int $orders_cap */
    /** @var \Carbon\CarbonInterface $from */
    /** @var \Carbon\CarbonInterface $to */
@endphp

<x-filament-panels::page>
    <form wire:submit.prevent class="space-y-6">
        {{ $this->form }}
    </form>

    <div class="fi-section flex items-center justify-between rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
        <div>
            <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ ucfirst($period) }} report</p>
            <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-100">
                {{ $from->isoFormat('D MMM Y, HH:mm') }} → {{ $to->isoFormat('D MMM Y, HH:mm') }}
            </p>
            @if ($branch_name)
                <p class="text-xs text-gray-500 dark:text-gray-400">Branch: {{ $branch_name }}</p>
            @else
                <p class="text-xs text-gray-500 dark:text-gray-400">All branches</p>
            @endif
        </div>
    </div>

    @php
        $stats = [
            ['label' => 'Paid orders', 'value' => number_format($summary['orders']), 'highlight' => false],
            ['label' => 'Revenue', 'value' => 'RM ' . number_format($summary['revenue'], 2), 'highlight' => true],
            ['label' => 'Avg ticket', 'value' => 'RM ' . number_format($summary['avg_ticket'], 2), 'highlight' => false],
            ['label' => 'Discounts', 'value' => 'RM ' . number_format($summary['discounts'], 2), 'highlight' => false],
            ['label' => 'Subtotal', 'value' => 'RM ' . number_format($summary['subtotal'], 2), 'highlight' => false],
            ['label' => 'SST', 'value' => 'RM ' . number_format($summary['sst'], 2), 'highlight' => false],
            ['label' => 'Service charge', 'value' => 'RM ' . number_format($summary['service_charge'], 2), 'highlight' => false],
            ['label' => 'Cancelled / Refunded', 'value' => $summary['cancelled'] . ' / ' . $summary['refunded'], 'highlight' => false],
        ];
    @endphp
    <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
        @foreach ($stats as $stat)
            <div class="fi-section rounded-xl border @if ($stat['highlight']) border-primary-500 bg-primary-50 dark:border-primary-400 dark:bg-primary-950 @else border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900 @endif p-4">
                <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $stat['label'] }}</p>
                <p class="mt-1 text-xl font-bold @if ($stat['highlight']) text-primary-700 dark:text-primary-300 @else text-gray-900 dark:text-gray-100 @endif">{{ $stat['value'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <div class="fi-section rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
            <h3 class="mb-3 text-sm font-semibold text-gray-900 dark:text-gray-100">Revenue by branch</h3>
            @if (count($by_branch) === 0)
                <p class="text-sm text-gray-500">No data for this period.</p>
            @else
                <table class="w-full text-sm">
                    <thead class="text-left text-xs uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="py-1 font-medium">Branch</th>
                            <th class="py-1 text-right font-medium">Orders</th>
                            <th class="py-1 text-right font-medium">Revenue</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($by_branch as $row)
                            <tr>
                                <td class="py-1.5 text-gray-900 dark:text-gray-100">{{ $row['branch_name'] }}</td>
                                <td class="py-1.5 text-right text-gray-700 dark:text-gray-300">{{ number_format($row['orders']) }}</td>
                                <td class="py-1.5 text-right font-medium text-gray-900 dark:text-gray-100">RM {{ number_format($row['revenue'], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <div class="fi-section rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
            <h3 class="mb-3 text-sm font-semibold text-gray-900 dark:text-gray-100">Top products</h3>
            @if (count($top_products) === 0)
                <p class="text-sm text-gray-500">No data for this period.</p>
            @else
                <table class="w-full text-sm">
                    <thead class="text-left text-xs uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="py-1 font-medium">Product</th>
                            <th class="py-1 text-right font-medium">Qty</th>
                            <th class="py-1 text-right font-medium">Revenue</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($top_products as $row)
                            <tr>
                                <td class="py-1.5 text-gray-900 dark:text-gray-100">{{ $row['product_name'] }}</td>
                                <td class="py-1.5 text-right text-gray-700 dark:text-gray-300">{{ number_format($row['quantity']) }}</td>
                                <td class="py-1.5 text-right font-medium text-gray-900 dark:text-gray-100">RM {{ number_format($row['revenue'], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    <div class="fi-section rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
        <div class="mb-3 flex items-center justify-between gap-2">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Orders ({{ count($orders) }})</h3>
            @if ($orders_truncated)
                <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-amber-800">
                    First {{ $orders_cap }} shown · download Excel for full list
                </span>
            @endif
        </div>
        @if (count($orders) === 0)
            <p class="text-sm text-gray-500">No orders in this period.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-left text-xs uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="py-1 font-medium">Order</th>
                            <th class="py-1 font-medium">Placed</th>
                            <th class="py-1 font-medium">Branch</th>
                            <th class="py-1 font-medium">Items</th>
                            <th class="py-1 font-medium">Status</th>
                            <th class="py-1 text-right font-medium">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($orders as $order)
                            <tr class="align-top">
                                <td class="py-1.5">
                                    <a href="{{ route('filament.admin.resources.orders.view', $order['id']) }}" class="font-mono text-xs font-semibold text-primary-600 hover:underline dark:text-primary-400">
                                        {{ $order['number'] }}
                                    </a>
                                    @if ($order['customer'])
                                        <p class="text-[10px] text-gray-500">{{ $order['customer'] }}</p>
                                    @endif
                                </td>
                                <td class="py-1.5 whitespace-nowrap text-xs text-gray-700 dark:text-gray-300">{{ $order['created_at'] }}</td>
                                <td class="py-1.5 text-xs text-gray-700 dark:text-gray-300">{{ $order['branch'] }}</td>
                                <td class="py-1.5 text-xs text-gray-700 dark:text-gray-300">
                                    <ul class="space-y-0.5">
                                        @foreach ($order['items'] as $item)
                                            <li>
                                                {{ $item['quantity'] }}× {{ $item['name'] }}
                                                @if ($item['modifiers'] !== '')
                                                    <span class="text-gray-500">({{ $item['modifiers'] }})</span>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                </td>
                                <td class="py-1.5 text-xs">
                                    <span class="inline-block rounded-full bg-gray-100 px-2 py-0.5 font-medium capitalize text-gray-700 dark:bg-gray-800 dark:text-gray-300">{{ str_replace('_', ' ', $order['status']) }}</span>
                                    <p class="mt-0.5 text-[10px] capitalize text-gray-500">{{ str_replace('_', ' ', $order['payment_status']) }}</p>
                                </td>
                                <td class="py-1.5 text-right font-semibold text-gray-900 dark:text-gray-100">RM {{ number_format($order['total'], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="fi-section rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
        <h3 class="mb-3 text-sm font-semibold text-gray-900 dark:text-gray-100">Daily breakdown</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-left text-xs uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="py-1 font-medium">Date</th>
                        <th class="py-1 text-right font-medium">Orders</th>
                        <th class="py-1 text-right font-medium">Revenue</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach ($series as $row)
                        <tr>
                            <td class="py-1.5 text-gray-900 dark:text-gray-100">{{ $row['date'] }}</td>
                            <td class="py-1.5 text-right text-gray-700 dark:text-gray-300">{{ number_format($row['orders']) }}</td>
                            <td class="py-1.5 text-right font-medium text-gray-900 dark:text-gray-100">RM {{ number_format($row['revenue'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>

@once
    @push('scripts')
    @endpush
@endonce
