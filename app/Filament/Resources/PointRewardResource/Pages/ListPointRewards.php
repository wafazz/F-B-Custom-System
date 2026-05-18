<?php

namespace App\Filament\Resources\PointRewardResource\Pages;

use App\Filament\Resources\PointRewardResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPointRewards extends ListRecords
{
    protected static string $resource = PointRewardResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
