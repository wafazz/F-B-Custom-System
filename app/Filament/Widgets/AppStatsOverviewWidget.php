<?php

namespace App\Filament\Widgets;

use App\Models\PwaInstall;
use App\Models\User;
use App\Models\UserPresence;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AppStatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $online = UserPresence::onlineCount(5);
        $activeToday = UserPresence::query()
            ->whereDate('last_seen_at', today())
            ->count();

        $installs = PwaInstall::query()->count();
        $installsThisWeek = PwaInstall::query()
            ->where('installed_at', '>=', now()->startOfWeek())
            ->count();
        $installsActive = PwaInstall::query()
            ->where('last_active_at', '>=', now()->subDays(7))
            ->count();

        $totalCustomers = User::query()
            ->whereHas('roles', fn ($q) => $q->where('name', 'customer'))
            ->count();
        $newCustomersToday = User::query()
            ->whereHas('roles', fn ($q) => $q->where('name', 'customer'))
            ->whereDate('created_at', today())
            ->count();

        return [
            Stat::make('Online Now', (string) $online)
                ->description($activeToday.' active today')
                ->descriptionIcon('heroicon-m-signal')
                ->color('success'),

            Stat::make('PWA Installs', (string) $installs)
                ->description($installsActive.' active · +'.$installsThisWeek.' this week')
                ->descriptionIcon('heroicon-m-device-phone-mobile')
                ->color('info'),

            Stat::make('Total Customers', (string) $totalCustomers)
                ->description($newCustomersToday.' new today')
                ->descriptionIcon('heroicon-m-users')
                ->color('warning'),
        ];
    }
}
