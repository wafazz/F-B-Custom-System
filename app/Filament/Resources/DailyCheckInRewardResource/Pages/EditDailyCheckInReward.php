<?php

namespace App\Filament\Resources\DailyCheckInRewardResource\Pages;

use App\Filament\Resources\DailyCheckInRewardResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDailyCheckInReward extends EditRecord
{
    protected static string $resource = DailyCheckInRewardResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
