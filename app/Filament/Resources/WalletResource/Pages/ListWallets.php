<?php

namespace App\Filament\Resources\WalletResource\Pages;

use App\Filament\Resources\WalletResource;
use App\Filament\Widgets\WalletStatsWidget;
use Filament\Resources\Pages\ListRecords;

class ListWallets extends ListRecords
{
    protected static string $resource = WalletResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            WalletStatsWidget::class,
        ];
    }
}
