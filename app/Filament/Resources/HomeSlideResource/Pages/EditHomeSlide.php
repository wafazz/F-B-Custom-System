<?php

namespace App\Filament\Resources\HomeSlideResource\Pages;

use App\Filament\Resources\HomeSlideResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditHomeSlide extends EditRecord
{
    protected static string $resource = HomeSlideResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
