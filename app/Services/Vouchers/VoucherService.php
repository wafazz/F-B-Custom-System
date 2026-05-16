<?php

namespace App\Services\Vouchers;

use App\Models\Order;
use App\Models\Voucher;
use App\Models\VoucherClaim;
use App\Models\VoucherRedemption;
use Illuminate\Support\Facades\DB;
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
}
