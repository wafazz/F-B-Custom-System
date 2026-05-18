<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use App\Models\VoucherClaim;
use App\Services\Loyalty\LoyaltyService;
use App\Services\Vouchers\VoucherService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class VoucherClaimController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $userId = (int) $user->getKey();

        $claims = VoucherClaim::query()
            ->where('user_id', $userId)
            ->with('voucher')
            ->latest('claimed_at')
            ->get();

        $claimedIds = $claims->pluck('voucher_id')->all();

        $available = Voucher::active()
            ->whereNotIn('id', $claimedIds)
            ->orderBy('valid_until')
            ->get()
            ->filter(fn (Voucher $v) => $v->isEligibleFor($user));

        return Inertia::render('storefront/vouchers', [
            'available' => $available->map(fn (Voucher $v) => $this->presentVoucher($v))->values(),
            'claimed' => $claims->map(function (VoucherClaim $c) {
                $voucher = $c->voucher;

                return [
                    'id' => $c->id,
                    'used' => $c->used_at !== null,
                    'used_at' => $c->used_at?->toIso8601String(),
                    'claimed_at' => $c->claimed_at->toIso8601String(),
                    'voucher' => $voucher ? $this->presentVoucher($voucher) : null,
                ];
            })->values(),
            'points_balance' => (int) app(LoyaltyService::class)->balance($userId),
        ]);
    }

    public function store(Voucher $voucher, Request $request, VoucherService $service): RedirectResponse
    {
        try {
            $service->claim($voucher, (int) $request->user()->getKey());
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Voucher {$voucher->code} added to your wallet.");
    }

    /** @return array<string, mixed> */
    protected function presentVoucher(Voucher $voucher): array
    {
        return [
            'id' => $voucher->id,
            'code' => $voucher->code,
            'name' => $voucher->name,
            'description' => $voucher->description,
            'banner_image' => $voucher->banner_image,
            'discount_type' => $voucher->discount_type,
            'discount_value' => (float) $voucher->discount_value,
            'min_subtotal' => (float) $voucher->min_subtotal,
            'max_discount' => $voucher->max_discount !== null ? (float) $voucher->max_discount : null,
            'valid_from' => $voucher->valid_from?->toIso8601String(),
            'valid_until' => $voucher->valid_until?->toIso8601String(),
            'max_uses_per_user' => $voucher->max_uses_per_user,
            'tier_names' => $this->tierNames($voucher),
            'birthday_months' => $voucher->birthday_months,
            'product_names' => $this->productNames($voucher),
            'combo_names' => $this->comboNames($voucher),
            'new_users_only' => (bool) $voucher->new_users_only,
            'points_cost' => $voucher->points_cost,
        ];
    }

    /** @return list<string> */
    protected function tierNames(Voucher $voucher): array
    {
        if (empty($voucher->tier_ids)) {
            return [];
        }

        return \App\Models\MembershipTier::query()
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

        return \App\Models\Product::query()
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

        return \App\Models\Combo::query()
            ->whereIn('id', $voucher->combo_ids)
            ->orderBy('name')
            ->pluck('name')
            ->all();
    }
}
