<?php

namespace App\Filament\Resources\MenuDisplayResource\Pages;

use App\Filament\Resources\MenuDisplayResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMenuDisplays extends ListRecords
{
    protected static string $resource = MenuDisplayResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
