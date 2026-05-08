<?php

namespace App\Filament\Resources\BranchDisplayTokenResource\Pages;

use App\Filament\Resources\BranchDisplayTokenResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBranchDisplayToken extends EditRecord
{
    protected static string $resource = BranchDisplayTokenResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
