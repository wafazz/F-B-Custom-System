<?php

namespace App\Policies;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BranchPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_branch');
    }

    public function view(User $user, Branch $branch): bool
    {
        return $user->can('view_branch') && $this->scopeAllows($user, $branch);
    }

    public function create(User $user): bool
    {
        return $user->can('create_branch');
    }

    public function update(User $user, Branch $branch): bool
    {
        return $user->can('update_branch') && $this->scopeAllows($user, $branch);
    }

    public function delete(User $user, Branch $branch): bool
    {
        return $user->can('delete_branch') && $this->scopeAllows($user, $branch);
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_branch');
    }

    public function forceDelete(User $user, Branch $branch): bool
    {
        return $user->can('force_delete_branch') && $this->scopeAllows($user, $branch);
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_branch');
    }

    public function restore(User $user, Branch $branch): bool
    {
        return $user->can('restore_branch') && $this->scopeAllows($user, $branch);
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_branch');
    }

    public function replicate(User $user, Branch $branch): bool
    {
        return $user->can('replicate_branch') && $this->scopeAllows($user, $branch);
    }

    public function reorder(User $user): bool
    {
        return $user->can('reorder_branch');
    }

    /**
     * Branch managers may only act on branches they're assigned to.
     * HQ-level roles (super_admin handled via gate intercept) and ops/mkt see all.
     */
    protected function scopeAllows(User $user, Branch $branch): bool
    {
        if (! $user->hasRole('branch_manager')) {
            return true;
        }

        return $user->branches()->whereKey($branch->getKey())->exists();
    }
}
