<?php

namespace App\Filament\Resources\WalletResource\Pages;

use App\Filament\Resources\WalletResource;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewWallet extends ViewRecord
{
    protected static string $resource = WalletResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make('Customer')
                ->schema([
                    TextEntry::make('user.name')->label('Name'),
                    TextEntry::make('user.email')->label('Email')->copyable(),
                    TextEntry::make('user.phone')->label('Phone'),
                ])
                ->columns(3),

            Section::make('Balance')
                ->schema([
                    TextEntry::make('balance')->money('MYR')->weight('bold')->size('lg'),
                    TextEntry::make('lifetime_topup')->label('Lifetime top-up')->money('MYR'),
                    TextEntry::make('lifetime_spent')->label('Lifetime spent')->money('MYR'),
                    TextEntry::make('updated_at')->label('Last activity')->since(),
                ])
                ->columns(4),
        ]);
    }
}
