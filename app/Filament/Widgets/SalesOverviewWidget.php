<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SalesOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $today = Order::query()
            ->whereDate('created_at', today())
            ->where('payment_status', PaymentStatus::Paid->value)
            ->whereNotIn('status', [OrderStatus::Cancelled->value, OrderStatus::Refunded->value]);

        $week = Order::query()
            ->where('created_at', '>=', now()->startOfWeek())
            ->where('payment_status', PaymentStatus::Paid->value)
            ->whereNotIn('status', [OrderStatus::Cancelled->value, OrderStatus::Refunded->value]);

        $month = Order::query()
            ->where('created_at', '>=', now()->startOfMonth())
            ->where('payment_status', PaymentStatus::Paid->value)
            ->whereNotIn('status', [OrderStatus::Cancelled->value, OrderStatus::Refunded->value]);

        return [
            Stat::make("Today's Revenue", 'RM '.number_format((float) $today->sum('total'), 2))
                ->description($today->count().' orders')
                ->color('success'),
            Stat::make('This Week', 'RM '.number_format((float) $week->sum('total'), 2))
                ->description($week->count().' orders')
                ->color('info'),
            Stat::make('This Month', 'RM '.number_format((float) $month->sum('total'), 2))
                ->description($month->count().' orders')
                ->color('warning'),
        ];
    }
}
