<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Combo;
use App\Models\MembershipTier;
use App\Models\Product;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherClaim;
use App\Services\Loyalty\LoyaltyService;
use App\Services\Vouchers\VoucherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class VoucherController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $userId = (int) $user->getKey();

        $claims = VoucherClaim::query()
            ->where('user_id', $userId)
            ->with('voucher')
            ->latest('claimed_at')
            ->get();

        $claimedIds = $claims->pluck('voucher_id')->all();

        $available = Voucher::active()
            ->where('is_spin_only', false)
            ->where('is_check_in_only', false)
            ->whereNotIn('id', $claimedIds)
            ->orderBy('valid_until')
            ->get()
            ->filter(fn (Voucher $v) => $v->isEligibleFor($user));

        return response()->json([
            'available' => $available->map(fn (Voucher $v) => $this->present($v))->values(),
            'claimed' => $claims->map(fn (VoucherClaim $c) => [
                'id' => $c->id,
                'used' => $c->used_at !== null,
                'used_at' => $c->used_at?->toIso8601String(),
                'claimed_at' => $c->claimed_at->toIso8601String(),
                'voucher' => $c->voucher ? $this->present($c->voucher) : null,
            ])->values(),
            'points_balance' => (int) app(LoyaltyService::class)->balance($userId),
        ]);
    }

    public function claim(Voucher $voucher, Request $request, VoucherService $service): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        try {
            $claim = $service->claim($voucher, (int) $user->getKey());
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'claim' => [
                'id' => $claim->id,
                'voucher' => $this->present($voucher),
                'claimed_at' => $claim->claimed_at->toIso8601String(),
            ],
        ], 201);
    }

    /** @return array<string, mixed> */
    protected function present(Voucher $v): array
    {
        return [
            'id' => $v->id,
            'code' => $v->code,
            'name' => $v->name,
            'description' => $v->description,
            'banner_image' => $v->banner_image,
            'discount_type' => $v->discount_type,
            'discount_value' => (float) $v->discount_value,
            'min_subtotal' => (float) $v->min_subtotal,
            'max_discount' => $v->max_discount !== null ? (float) $v->max_discount : null,
            'valid_from' => $v->valid_from?->toIso8601String(),
            'valid_until' => $v->valid_until?->toIso8601String(),
            'max_uses_per_user' => $v->max_uses_per_user,
            'tier_names' => $this->tierNames($v),
            'birthday_months' => $v->birthday_months,
            'product_names' => $this->productNames($v),
            'combo_names' => $this->comboNames($v),
            'new_users_only' => (bool) $v->new_users_only,
            'points_cost' => $v->points_cost,
        ];
    }

    /** @return list<string> */
    protected function tierNames(Voucher $voucher): array
    {
        if (empty($voucher->tier_ids)) {
            return [];
        }

        return MembershipTier::query()
            ->whereIn('id', $voucher->tier_ids)
            ->orderBy('min_lifetime_spend')
            ->pluck('name')
            ->all();
    }

    /** @return list<string> */
    protected function productNames(Voucher $voucher): array
    {
        if (empty($voucher->product_ids)) {
            return [];
        }

        return Product::query()
            ->whereIn('id', $voucher->product_ids)
            ->orderBy('name')
            ->pluck('name')
            ->all();
    }

    /** @return list<string> */
    protected function comboNames(Voucher $voucher): array
    {
        if (empty($voucher->combo_ids)) {
            return [];
        }

        return Combo::query()
            ->whereIn('id', $voucher->combo_ids)
            ->orderBy('name')
            ->pluck('name')
            ->all();
    }
}
