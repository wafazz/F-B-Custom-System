<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OrderPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_order');
    }

    public function view(?User $user, Order $order): bool
    {
        // Guest who placed this order in their session can see it.
        if ($order->user_id === null && in_array($order->id, (array) session('placed_order_ids', []), true)) {
            return true;
        }

        if (! $user) {
            return false;
        }

        // Customers can always view their own orders.
        if ($order->user_id === $user->getKey()) {
            return true;
        }

        return $user->can('view_order') && $this->scopeAllows($user, $order);
    }

    public function create(User $user): bool
    {
        return $user->can('create_order');
    }

    public function update(User $user, Order $order): bool
    {
        return $user->can('update_order') && $this->scopeAllows($user, $order);
    }

    public function delete(User $user, Order $order): bool
    {
        return $user->can('delete_order') && $this->scopeAllows($user, $order);
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_order');
    }

    public function forceDelete(User $user, Order $order): bool
    {
        return $user->can('force_delete_order') && $this->scopeAllows($user, $order);
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_order');
    }

    public function restore(User $user, Order $order): bool
    {
        return $user->can('restore_order') && $this->scopeAllows($user, $order);
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_order');
    }

    public function replicate(User $user, Order $order): bool
    {
        return $user->can('replicate_order') && $this->scopeAllows($user, $order);
    }

    public function reorder(User $user): bool
    {
        return $user->can('reorder_order');
    }

    protected function scopeAllows(User $user, Order $order): bool
    {
        if (! $user->hasRole('branch_manager')) {
            return true;
        }

        return $user->branches()->whereKey($order->branch_id)->exists();
    }
}
