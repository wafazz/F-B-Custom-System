<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use Filament\Widgets\ChartWidget;

class RevenueChartWidget extends ChartWidget
{
    protected static ?int $sort = 4;

    protected static ?string $heading = 'Revenue (last 14 days)';

    protected static ?string $pollingInterval = '60s';

    protected int|string|array $columnSpan = 'full';

    public ?string $filter = '14d';

    protected function getFilters(): ?array
    {
        return [
            '7d' => 'Last 7 days',
            '14d' => 'Last 14 days',
            '30d' => 'Last 30 days',
        ];
    }

    protected function getData(): array
    {
        $days = match ($this->filter) {
            '7d' => 7,
            '30d' => 30,
            default => 14,
        };

        $from = now()->subDays($days - 1)->startOfDay();

        $rows = Order::query()
            ->selectRaw('DATE(created_at) AS d, SUM(total) AS r, COUNT(*) AS c')
            ->where('payment_status', PaymentStatus::Paid->value)
            ->whereNotIn('status', [OrderStatus::Cancelled->value, OrderStatus::Refunded->value])
            ->where('created_at', '>=', $from)
            ->groupBy('d')
            ->orderBy('d')
            ->get()
            ->keyBy('d');

        $labels = [];
        $revenue = [];
        $orders = [];
        for ($day = $from->copy(); $day->lte(now()); $day->addDay()) {
            $key = $day->toDateString();
            $labels[] = $day->format('M d');
            $revenue[] = (float) ($rows[$key]->r ?? 0);
            $orders[] = (int) ($rows[$key]->c ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Revenue (RM)',
                    'data' => $revenue,
                    'borderColor' => '#f59e0b',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.15)',
                    'fill' => true,
                    'tension' => 0.35,
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'Orders',
                    'data' => $orders,
                    'borderColor' => '#6366f1',
                    'backgroundColor' => 'rgba(99, 102, 241, 0)',
                    'tension' => 0.35,
                    'yAxisID' => 'y1',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'type' => 'linear',
                    'position' => 'left',
                    'title' => ['display' => true, 'text' => 'Revenue (RM)'],
                ],
                'y1' => [
                    'type' => 'linear',
                    'position' => 'right',
                    'grid' => ['drawOnChartArea' => false],
                    'title' => ['display' => true, 'text' => 'Orders'],
                ],
            ],
        ];
    }
}
