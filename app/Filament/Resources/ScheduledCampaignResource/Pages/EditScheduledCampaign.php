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

    /** @param array<string, mixed> $data @return array<string, mixed> */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return ScheduledCampaignResource::normalizeSchedule($data);
    }
}
