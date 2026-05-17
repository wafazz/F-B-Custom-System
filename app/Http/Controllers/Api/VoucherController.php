<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherClaim;
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
            ->whereNotIn('id', $claimedIds)
            ->orderBy('valid_until')
            ->get();

        return response()->json([
            'available' => $available->map(fn (Voucher $v) => $this->present($v))->values(),
            'claimed' => $claims->map(fn (VoucherClaim $c) => [
                'id' => $c->id,
                'used' => $c->used_at !== null,
                'used_at' => $c->used_at?->toIso8601String(),
                'claimed_at' => $c->claimed_at->toIso8601String(),
                'voucher' => $c->voucher ? $this->present($c->voucher) : null,
            ])->values(),
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
            'discount_type' => $v->discount_type,
            'discount_value' => (float) $v->discount_value,
            'min_subtotal' => (float) $v->min_subtotal,
            'max_discount' => $v->max_discount !== null ? (float) $v->max_discount : null,
            'valid_until' => $v->valid_until?->toIso8601String(),
        ];
    }
}
