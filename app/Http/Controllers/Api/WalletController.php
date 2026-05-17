<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTopup;
use App\Models\WalletTransaction;
use App\Services\Payments\BillplzGateway;
use App\Services\Wallet\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class WalletController extends Controller
{
    public function show(Request $request, WalletService $wallet): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $userId = (int) $user->getKey();
        $row = Wallet::query()->where('user_id', $userId)->first();

        return response()->json([
            'balance' => $wallet->balance($userId),
            'lifetime_topup' => $row ? (float) $row->lifetime_topup : 0.0,
            'lifetime_spent' => $row ? (float) $row->lifetime_spent : 0.0,
            'topup_amounts' => [10, 20, 50, 100, 200],
        ]);
    }

    public function transactions(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $rows = WalletTransaction::query()
            ->where('user_id', $user->getKey())
            ->latest('id')
            ->limit(50)
            ->get();

        return response()->json([
            'transactions' => $rows->map(fn (WalletTransaction $t) => [
                'id' => $t->id,
                'type' => $t->type,
                'amount' => (float) $t->amount,
                'balance_after' => (float) $t->balance_after,
                'description' => $t->description,
                'created_at' => $t->created_at?->toIso8601String(),
            ])->values(),
        ]);
    }

    public function topup(Request $request, BillplzGateway $gateway): JsonResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:5', 'max:1000'],
        ]);

        /** @var User $user */
        $user = $request->user();

        $topup = WalletTopup::create([
            'user_id' => $user->getKey(),
            'amount' => $data['amount'],
            'status' => 'pending',
        ]);

        try {
            $bill = $gateway->createTopupBill($topup, $user);
        } catch (RuntimeException $e) {
            $topup->update(['status' => 'failed']);

            return response()->json(['message' => $e->getMessage()], 422);
        }

        $topup->update(['billplz_reference' => $bill->reference]);

        return response()->json([
            'topup' => [
                'id' => $topup->id,
                'amount' => (float) $topup->amount,
                'status' => $topup->status,
                'billplz_reference' => $bill->reference,
            ],
            'payment' => [
                'reference' => $bill->reference,
                'url' => $bill->url,
                'method' => $bill->method,
            ],
        ], 201);
    }
}
