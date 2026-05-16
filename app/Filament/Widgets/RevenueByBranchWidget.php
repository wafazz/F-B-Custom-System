<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use Filament\Widgets\ChartWidget;

class RevenueByBranchWidget extends ChartWidget
{
    protected static ?int $sort = 5;

    protected static ?string $heading = 'Revenue by branch (last 30 days)';

    protected static ?string $pollingInterval = '120s';

    protected int|string|array $columnSpan = 1;

    public static function canView(): bool
    {
        return auth()->user()?->hasRole(['super_admin', 'hq_admin', 'ops_manager']) ?? false;
    }

    protected function getData(): array
    {
        $rows = Order::query()
            ->selectRaw('branches.name AS branch_name, SUM(orders.total) AS revenue')
            ->join('branches', 'branches.id', '=', 'orders.branch_id')
            ->where('orders.payment_status', PaymentStatus::Paid->value)
            ->whereNotIn('orders.status', [OrderStatus::Cancelled->value, OrderStatus::Refunded->value])
            ->where('orders.created_at', '>=', now()->subDays(30))
            ->groupBy('branches.id', 'branches.name')
            ->orderByDesc('revenue')
            ->get();

        $palette = ['#f59e0b', '#6366f1', '#10b981', '#ef4444', '#8b5cf6', '#ec4899', '#06b6d4', '#84cc16'];

        return [
            'datasets' => [[
                'label' => 'Revenue (RM)',
                'data' => $rows->pluck('revenue')->map(fn ($v) => (float) $v)->all(),
                'backgroundColor' => $rows->keys()->map(fn ($i) => $palette[$i % count($palette)])->all(),
            ]],
            'labels' => $rows->pluck('branch_name')->all(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
