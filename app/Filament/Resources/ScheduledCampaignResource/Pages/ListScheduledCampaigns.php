<?php

namespace App\Filament\Resources\ScheduledCampaignResource\Pages;

use App\Filament\Resources\ScheduledCampaignResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListScheduledCampaigns extends ListRecords
{
    protected static string $resource = ScheduledCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
