<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Spatie\Permission\PermissionRegistrar;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn () => ! in_array($this->record->name, ['super_admin', 'hq_admin', 'customer'], true)),
        ];
    }

    protected function afterSave(): void
    {
        $this->syncPermissionsFromForm();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    protected function syncPermissionsFromForm(): void
    {
        $ids = [];
        foreach ($this->form->getRawState() as $key => $value) {
            if (str_starts_with($key, 'perm_group_') && is_array($value)) {
                foreach ($value as $id) {
                    $ids[] = (int) $id;
                }
            }
        }

        $this->record->permissions()->sync(array_unique($ids));
    }
}
