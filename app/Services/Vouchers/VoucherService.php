<?php

namespace App\Services\Vouchers;

use App\Models\CustomerTier;
use App\Models\Order;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherClaim;
use App\Models\VoucherRedemption;
use App\Notifications\VoucherAvailableNotification;
use App\Services\Push\PushService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use RuntimeException;

class VoucherService
{
    /** Resolve and validate a voucher; throws if invalid. */
    public function find(string $code, int $branchId, ?int $userId = null): Voucher
    {
        $voucher = Voucher::active()->where('code', strtoupper($code))->first();
        if (! $voucher) {
            throw new RuntimeException('Voucher not found or expired.');
        }
        if ($voucher->max_uses !== null && $voucher->used_count >= $voucher->max_uses) {
            throw new RuntimeException('Voucher has reached its usage cap.');
        }
        $branchScope = $voucher->branch_ids;
        if (is_array($branchScope) && count($branchScope) > 0 && ! in_array($branchId, $branchScope, true)) {
            throw new RuntimeException('Voucher is not valid for this branch.');
        }
        if ($userId !== null) {
            $userUses = VoucherRedemption::query()
                ->where('voucher_id', $voucher->id)
                ->where('user_id', $userId)
                ->count();
            if ($userUses >= $voucher->max_uses_per_user) {
                throw new RuntimeException('You have already used this voucher.');
            }
        }

        return $voucher;
    }

    public function discountFor(Voucher $voucher, float $subtotal): float
    {
        if ($subtotal < (float) $voucher->min_subtotal) {
            throw new RuntimeException(sprintf('Minimum subtotal RM%.2f required.', (float) $voucher->min_subtotal));
        }

        $raw = $voucher->discount_type === 'percentage'
            ? $subtotal * ((float) $voucher->discount_value / 100)
            : (float) $voucher->discount_value;

        if ($voucher->max_discount !== null) {
            $raw = min($raw, (float) $voucher->max_discount);
        }

        return min(round($raw, 2), $subtotal);
    }

    public function commit(Voucher $voucher, Order $order, float $discount): VoucherRedemption
    {
        return DB::transaction(function () use ($voucher, $order, $discount) {
            $voucher->increment('used_count');

            $redemption = VoucherRedemption::create([
                'voucher_id' => $voucher->id,
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'discount_amount' => $discount,
            ]);

            if ($order->user_id !== null) {
                VoucherClaim::query()
                    ->where('voucher_id', $voucher->id)
                    ->where('user_id', $order->user_id)
                    ->whereNull('used_at')
                    ->update([
                        'used_at' => now(),
                        'order_id' => $order->id,
                    ]);
            }

            return $redemption;
        });
    }

    /** Claim a voucher for a user. Idempotent — re-claiming returns the existing row. */
    public function claim(Voucher $voucher, int $userId): VoucherClaim
    {
        $now = now();
        if ($voucher->status !== 'active') {
            throw new RuntimeException('Voucher is not active.');
        }
        if ($voucher->valid_from !== null && $voucher->valid_from->greaterThan($now)) {
            throw new RuntimeException('Voucher is not yet available.');
        }
        if ($voucher->valid_until !== null && $voucher->valid_until->lessThan($now)) {
            throw new RuntimeException('Voucher has expired.');
        }
        if ($voucher->max_uses !== null && $voucher->used_count >= $voucher->max_uses) {
            throw new RuntimeException('Voucher has reached its usage cap.');
        }

        $user = User::query()->find($userId);
        if ($user === null || ! $voucher->isEligibleFor($user)) {
            throw new RuntimeException('This voucher is not available for your account.');
        }

        return DB::transaction(function () use ($voucher, $userId) {
            $existing = VoucherClaim::query()
                ->where('voucher_id', $voucher->id)
                ->where('user_id', $userId)
                ->first();

            if ($existing instanceof VoucherClaim) {
                if ($existing->used_at !== null) {
                    throw new RuntimeException('You have already used this voucher.');
                }

                return $existing;
            }

            $userUses = VoucherRedemption::query()
                ->where('voucher_id', $voucher->id)
                ->where('user_id', $userId)
                ->count();
            if ($userUses >= $voucher->max_uses_per_user) {
                throw new RuntimeException('You have already used this voucher.');
            }

            return VoucherClaim::create([
                'voucher_id' => $voucher->id,
                'user_id' => $userId,
                'claimed_at' => now(),
            ]);
        });
    }

    /**
     * Broadcast a "new voucher" announcement to every eligible customer:
     * inbox row via VoucherAvailableNotification, plus a Web Push for
     * anyone who's subscribed. Customers with admin roles are skipped so
     * staff inboxes don't fill up with marketing.
     */
    public function notifyEligibleMembers(Voucher $voucher, PushService $push): int
    {
        $query = User::query()
            ->whereNull('deleted_at')
            ->whereDoesntHave('roles', fn ($q) => $q->whereIn('name', [
                'super_admin', 'hq_admin', 'ops_manager', 'mkt_manager',
                'branch_manager', 'cashier', 'barista',
            ]));

        if (! empty($voucher->tier_ids)) {
            $tierUserIds = CustomerTier::query()
                ->whereIn('membership_tier_id', $voucher->tier_ids)
                ->pluck('user_id');
            $query->whereIn('id', $tierUserIds);
        }

        if (! empty($voucher->birthday_months)) {
            $query->whereNotNull('date_of_birth')
                ->whereIn(DB::raw('MONTH(date_of_birth)'), $voucher->birthday_months);
        }

        $users = $query->get();
        if ($users->isEmpty()) {
            return 0;
        }

        // Inbox notification — one DB insert per user, idempotent within
        // Laravel's broker.
        Notification::send($users, new VoucherAvailableNotification($voucher));

        // Web Push — best-effort, dead endpoints get pruned by PushService.
        if ($push->isConfigured()) {
            $discount = $voucher->discount_type === 'percentage'
                ? number_format((float) $voucher->discount_value, 0).'% off'
                : 'RM'.number_format((float) $voucher->discount_value, 2).' off';
            foreach ($users as $u) {
                $push->sendToUser((int) $u->getKey(), [
                    'title' => 'New voucher: '.$voucher->name,
                    'body' => "{$discount} — tap to claim.",
                    'url' => '/vouchers',
                    'tag' => "voucher-{$voucher->id}",
                ]);
            }
        }

        return $users->count();
    }
}
