<?php

namespace App\Filament\Resources\PointRewardClaimResource\Pages;

use App\Filament\Resources\PointRewardClaimResource;
use Filament\Resources\Pages\ListRecords;

class ListPointRewardClaims extends ListRecords
{
    protected static string $resource = PointRewardClaimResource::class;

    public function getTitle(): string
    {
        return 'Reward pickups';
    }
}
