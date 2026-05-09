<?php

namespace App\Services\Wallet;

use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\WalletTopup;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class WalletService
{
    /** Balance for the given user — 0.0 if no wallet row yet. */
    public function balance(int $userId): float
    {
        $wallet = Wallet::query()->where('user_id', $userId)->first();

        return $wallet ? (float) $wallet->balance : 0.0;
    }

    /** Credit funds to a user's wallet (e.g. successful top-up, refund). */
    public function credit(int $userId, float $amount, string $type = 'topup', ?Model $reference = null, ?string $description = null): WalletTransaction
    {
        if ($amount <= 0) {
            throw new RuntimeException('Credit amount must be positive.');
        }

        return DB::transaction(function () use ($userId, $amount, $type, $reference, $description) {
            $wallet = Wallet::query()->lockForUpdate()->firstOrCreate(['user_id' => $userId]);
            $newBalance = (float) $wallet->balance + $amount;

            $wallet->forceFill([
                'balance' => $newBalance,
                'lifetime_topup' => $type === 'topup'
                    ? (float) $wallet->lifetime_topup + $amount
                    : $wallet->lifetime_topup,
            ])->save();

            return $this->record($userId, $type, $amount, $newBalance, $reference, $description);
        });
    }

    /** Debit funds. Throws on insufficient balance — caller wraps in same outer transaction. */
    public function debit(int $userId, float $amount, string $type = 'spend', ?Model $reference = null, ?string $description = null): WalletTransaction
    {
        if ($amount <= 0) {
            throw new RuntimeException('Debit amount must be positive.');
        }

        return DB::transaction(function () use ($userId, $amount, $type, $reference, $description) {
            $wallet = Wallet::query()->lockForUpdate()->firstOrCreate(['user_id' => $userId]);
            $current = (float) $wallet->balance;

            if ($current < $amount) {
                throw new RuntimeException(sprintf(
                    'Insufficient wallet balance — RM%.2f required, RM%.2f available.',
                    $amount,
                    $current,
                ));
            }

            $newBalance = $current - $amount;
            $wallet->forceFill([
                'balance' => $newBalance,
                'lifetime_spent' => $type === 'spend'
                    ? (float) $wallet->lifetime_spent + $amount
                    : $wallet->lifetime_spent,
            ])->save();

            return $this->record($userId, $type, -$amount, $newBalance, $reference, $description);
        });
    }

    /**
     * Apply a paid top-up. Idempotent — replays for the same WalletTopup are no-ops.
     */
    public function applyTopupPaid(WalletTopup $topup): void
    {
        if ($topup->status === 'paid') {
            return;
        }

        DB::transaction(function () use ($topup) {
            $topup->forceFill(['status' => 'paid', 'paid_at' => now()])->save();
            $this->credit(
                $topup->user_id,
                (float) $topup->amount,
                type: 'topup',
                reference: $topup,
                description: "Top-up #{$topup->id}",
            );
        });
    }

    protected function record(int $userId, string $type, float $signedAmount, float $balanceAfter, ?Model $reference, ?string $description): WalletTransaction
    {
        return WalletTransaction::create([
            'user_id' => $userId,
            'type' => $type,
            'amount' => $signedAmount,
            'balance_after' => $balanceAfter,
            'reference_type' => $reference?->getMorphClass(),
            'reference_id' => $reference?->getKey(),
            'description' => $description,
        ]);
    }
}
