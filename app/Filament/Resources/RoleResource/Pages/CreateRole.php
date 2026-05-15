<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use Filament\Resources\Pages\CreateRecord;
use Spatie\Permission\PermissionRegistrar;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    protected function afterCreate(): void
    {
        $this->syncPermissionsFromForm();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /** Collect every `perm_group_{resource}` CheckboxList back into a single sync. */
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
