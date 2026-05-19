<?php

namespace App\Filament\Resources\SpinWheelSegmentResource\Pages;

use App\Filament\Resources\SpinWheelSegmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSpinWheelSegments extends ListRecords
{
    protected static string $resource = SpinWheelSegmentResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
