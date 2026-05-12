<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class SalesOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected static ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        return [
            $this->buildStat(
                label: "Today's Revenue",
                from: today(),
                to: today()->endOfDay(),
                previousFrom: today()->subDay(),
                previousTo: today()->subDay()->endOfDay(),
                trend: $this->revenueTrend(now()->subDays(6)->startOfDay(), now()),
                color: 'success',
            ),
            $this->buildStat(
                label: 'This Week',
                from: now()->startOfWeek(),
                to: now(),
                previousFrom: now()->subWeek()->startOfWeek(),
                previousTo: now()->subWeek()->endOfWeek(),
                trend: $this->revenueTrend(now()->subWeeks(7)->startOfWeek(), now()),
                groupBy: 'week',
                color: 'info',
            ),
            $this->buildStat(
                label: 'This Month',
                from: now()->startOfMonth(),
                to: now(),
                previousFrom: now()->subMonth()->startOfMonth(),
                previousTo: now()->subMonth()->endOfMonth(),
                trend: $this->revenueTrend(now()->subMonths(5)->startOfMonth(), now()),
                groupBy: 'month',
                color: 'warning',
            ),
        ];
    }

    /** @param  list<int|float>  $trend */
    protected function buildStat(
        string $label,
        \Carbon\Carbon $from,
        \Carbon\Carbon $to,
        \Carbon\Carbon $previousFrom,
        \Carbon\Carbon $previousTo,
        array $trend,
        string $groupBy = 'day',
        string $color = 'primary',
    ): Stat {
        $current = $this->paidOrders()->whereBetween('created_at', [$from, $to]);
        $previous = $this->paidOrders()->whereBetween('created_at', [$previousFrom, $previousTo]);

        $currentRevenue = (float) $current->sum('total');
        $previousRevenue = (float) $previous->sum('total');
        $delta = $previousRevenue > 0 ? (($currentRevenue - $previousRevenue) / $previousRevenue) * 100 : null;

        $description = $current->count().' orders';
        $icon = 'heroicon-m-arrows-right-left';
        if ($delta !== null) {
            $sign = $delta >= 0 ? '+' : '';
            $description .= ' · '.$sign.number_format($delta, 1).'% vs last '.$groupBy;
            $icon = $delta >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down';
        }

        return Stat::make($label, 'RM '.number_format($currentRevenue, 2))
            ->description($description)
            ->descriptionIcon($icon)
            ->chart($trend)
            ->color($color);
    }

    /** @return Builder<Order> */
    protected function paidOrders(): Builder
    {
        return Order::query()
            ->where('payment_status', PaymentStatus::Paid->value)
            ->whereNotIn('status', [OrderStatus::Cancelled->value, OrderStatus::Refunded->value]);
    }

    /** @return list<int|float> */
    protected function revenueTrend(\Carbon\Carbon $from, \Carbon\Carbon $to): array
    {
        $rows = $this->paidOrders()
            ->selectRaw('DATE(created_at) AS d, SUM(total) AS r')
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('d')
            ->orderBy('d')
            ->pluck('r', 'd');

        $out = [];
        for ($day = $from->copy()->startOfDay(); $day->lte($to); $day->addDay()) {
            $out[] = (float) ($rows[$day->toDateString()] ?? 0);
        }

        return $out;
    }
}
