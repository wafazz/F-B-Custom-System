<?php

namespace App\Filament\Resources\MenuDisplayResource\Pages;

use App\Filament\Resources\MenuDisplayResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMenuDisplay extends EditRecord
{
    protected static string $resource = MenuDisplayResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
