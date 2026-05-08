<?php

namespace App\Filament\Resources\BranchDisplayTokenResource\Pages;

use App\Filament\Resources\BranchDisplayTokenResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBranchDisplayTokens extends ListRecords
{
    protected static string $resource = BranchDisplayTokenResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
