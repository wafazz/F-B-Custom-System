<?php

namespace App\Policies;

use App\Models\BranchStaff;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BranchStaffPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_branch::staff');
    }

    public function view(User $user, BranchStaff $assignment): bool
    {
        return $user->can('view_branch::staff') && $this->scopeAllows($user, $assignment);
    }

    public function create(User $user): bool
    {
        return $user->can('create_branch::staff');
    }

    public function update(User $user, BranchStaff $assignment): bool
    {
        return $user->can('update_branch::staff') && $this->scopeAllows($user, $assignment);
    }

    public function delete(User $user, BranchStaff $assignment): bool
    {
        return $user->can('delete_branch::staff') && $this->scopeAllows($user, $assignment);
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_branch::staff');
    }

    public function forceDelete(User $user, BranchStaff $assignment): bool
    {
        return $user->can('force_delete_branch::staff') && $this->scopeAllows($user, $assignment);
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_branch::staff');
    }

    public function restore(User $user, BranchStaff $assignment): bool
    {
        return $user->can('restore_branch::staff') && $this->scopeAllows($user, $assignment);
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_branch::staff');
    }

    public function replicate(User $user, BranchStaff $assignment): bool
    {
        return $user->can('replicate_branch::staff') && $this->scopeAllows($user, $assignment);
    }

    public function reorder(User $user): bool
    {
        return $user->can('reorder_branch::staff');
    }

    protected function scopeAllows(User $user, BranchStaff $assignment): bool
    {
        if (! $user->hasRole('branch_manager')) {
            return true;
        }

        return $user->branches()->whereKey($assignment->branch_id)->exists();
    }
}
