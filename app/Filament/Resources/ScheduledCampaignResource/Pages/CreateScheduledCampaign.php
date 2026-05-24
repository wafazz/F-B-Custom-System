<?php

namespace App\Filament\Resources\ScheduledCampaignResource\Pages;

use App\Filament\Resources\ScheduledCampaignResource;
use Filament\Resources\Pages\CreateRecord;

class CreateScheduledCampaign extends CreateRecord
{
    protected static string $resource = ScheduledCampaignResource::class;

    /** @param array<string, mixed> $data @return array<string, mixed> */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return ScheduledCampaignResource::normalizeSchedule($data);
    }
}
