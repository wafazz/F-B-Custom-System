<?php

namespace App\Services\Vouchers;

use App\Models\Order;
use App\Models\Voucher;
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

            return VoucherRedemption::create([
                'voucher_id' => $voucher->id,
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'discount_amount' => $discount,
            ]);
        });
    }
}
