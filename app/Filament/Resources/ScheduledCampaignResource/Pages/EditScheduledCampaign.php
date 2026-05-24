<?php

namespace App\Filament\Resources\ScheduledCampaignResource\Pages;

use App\Filament\Resources\ScheduledCampaignResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditScheduledCampaign extends EditRecord
{
    protected static string $resource = ScheduledCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
