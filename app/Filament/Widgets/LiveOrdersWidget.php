<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class LiveOrdersWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected static ?string $pollingInterval = '10s';

    protected function getStats(): array
    {
        $pending = Order::query()->where('status', OrderStatus::Pending->value)->count();
        $preparing = Order::query()->where('status', OrderStatus::Preparing->value)->count();
        $ready = Order::query()->where('status', OrderStatus::Ready->value)->count();

        return [
            Stat::make('Pending', $pending)
                ->description('Waiting on payment or staff confirm')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pending > 0 ? 'warning' : 'gray'),
            Stat::make('Preparing', $preparing)
                ->description('On the bar right now')
                ->descriptionIcon('heroicon-m-fire')
                ->color($preparing > 0 ? 'info' : 'gray'),
            Stat::make('Ready', $ready)
                ->description('Awaiting pickup / handoff')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color($ready > 0 ? 'success' : 'gray'),
        ];
    }
}
