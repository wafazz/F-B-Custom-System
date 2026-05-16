<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use App\Models\VoucherClaim;
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
        $userId = (int) $request->user()->getKey();

        $claims = VoucherClaim::query()
            ->where('user_id', $userId)
            ->with('voucher')
            ->latest('claimed_at')
            ->get();

        $claimedIds = $claims->pluck('voucher_id')->all();

        $available = Voucher::active()
            ->whereNotIn('id', $claimedIds)
            ->orderBy('valid_until')
            ->get();

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
            'discount_type' => $voucher->discount_type,
            'discount_value' => (float) $voucher->discount_value,
            'min_subtotal' => (float) $voucher->min_subtotal,
            'max_discount' => $voucher->max_discount !== null ? (float) $voucher->max_discount : null,
            'valid_until' => $voucher->valid_until?->toIso8601String(),
        ];
    }
}
