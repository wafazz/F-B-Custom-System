<?php

namespace App\Filament\Widgets;

use App\Models\Wallet;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class WalletStatsWidget extends BaseWidget
{
    protected static ?int $sort = 7;

    protected function getStats(): array
    {
        $totalBalance = (float) Wallet::query()->sum('balance');
        $totalTopup = (float) Wallet::query()->sum('lifetime_topup');
        $totalSpent = (float) Wallet::query()->sum('lifetime_spent');
        $walletsWithBalance = Wallet::query()->where('balance', '>', 0)->count();

        return [
            Stat::make('Total balance outstanding', 'RM '.number_format($totalBalance, 2))
                ->description('Sum of every wallet currently holding funds')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color($totalBalance > 0 ? 'success' : 'gray'),

            Stat::make('Wallets with balance', $walletsWithBalance)
                ->description('Customers with usable wallet funds')
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),

            Stat::make('Lifetime top-ups', 'RM '.number_format($totalTopup, 2))
                ->description('All-time money loaded into wallets')
                ->descriptionIcon('heroicon-m-arrow-down-circle')
                ->color('primary'),

            Stat::make('Lifetime spent', 'RM '.number_format($totalSpent, 2))
                ->description('All-time wallet spend on orders')
                ->descriptionIcon('heroicon-m-arrow-up-circle')
                ->color('warning'),
        ];
    }

    public static function canView(): bool
    {
        return auth()->user()?->hasRole(['super_admin', 'hq_admin']) ?? false;
    }
}
