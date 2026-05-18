<?php

namespace App\Filament\Resources\PointRewardResource\Pages;

use App\Filament\Resources\PointRewardResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPointReward extends EditRecord
{
    protected static string $resource = PointRewardResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
