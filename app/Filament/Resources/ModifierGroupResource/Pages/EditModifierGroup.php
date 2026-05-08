<?php

namespace App\Filament\Resources\ModifierGroupResource\Pages;

use App\Filament\Resources\ModifierGroupResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditModifierGroup extends EditRecord
{
    protected static string $resource = ModifierGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
