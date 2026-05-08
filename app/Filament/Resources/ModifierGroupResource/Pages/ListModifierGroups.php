<?php

namespace App\Filament\Resources\ModifierGroupResource\Pages;

use App\Filament\Resources\ModifierGroupResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListModifierGroups extends ListRecords
{
    protected static string $resource = ModifierGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
