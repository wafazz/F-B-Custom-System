<?php

namespace App\Services\Pos;

use App\Models\Order;
use App\Models\PosCashMovement;
use App\Models\PosShift;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PosShiftService
{
    public function currentForBranch(int $branchId): ?PosShift
    {
        return PosShift::query()->open()->where('branch_id', $branchId)->latest('opened_at')->first();
    }

    public function open(int $branchId, int $userId, float $openingFloat): PosShift
    {
        if ($openingFloat < 0) {
            throw new RuntimeException('Opening float cannot be negative.');
        }

        return DB::transaction(function () use ($branchId, $userId, $openingFloat) {
            $existing = PosShift::query()
                ->open()
                ->where('branch_id', $branchId)
                ->lockForUpdate()
                ->first();
            if ($existing) {
                throw new RuntimeException('A shift is already open for this branch.');
            }

            return PosShift::create([
                'branch_id' => $branchId,
                'opened_by_user_id' => $userId,
                'opened_at' => now(),
                'opening_float' => $openingFloat,
            ]);
        });
    }

    public function close(int $shiftId, int $userId, float $countedCash, ?string $notes = null): PosShift
    {
        return DB::transaction(function () use ($shiftId, $userId, $countedCash, $notes) {
            /** @var PosShift $shift */
            $shift = PosShift::query()->lockForUpdate()->findOrFail($shiftId);
            if (! $shift->isOpen()) {
                throw new RuntimeException('Shift is already closed.');
            }

            $summary = $this->summary($shift);
            $expected = $summary['expected_cash'];
            $variance = round($countedCash - $expected, 2);

            $shift->fill([
                'closed_by_user_id' => $userId,
                'closed_at' => now(),
                'expected_cash' => $expected,
                'counted_cash' => $countedCash,
                'variance' => $variance,
                'notes' => $notes,
            ])->save();

            return $shift->fresh() ?? $shift;
        });
    }

    public function recordCashIn(int $shiftId, int $userId, float $amount, string $reason): PosCashMovement
    {
        return $this->recordMovement($shiftId, $userId, 'cash_in', $amount, $reason);
    }

    public function recordCashOut(int $shiftId, int $userId, float $amount, string $reason): PosCashMovement
    {
        return $this->recordMovement($shiftId, $userId, 'cash_out', $amount, $reason);
    }

    protected function recordMovement(int $shiftId, int $userId, string $type, float $amount, string $reason): PosCashMovement
    {
        if ($amount <= 0) {
            throw new RuntimeException('Amount must be greater than zero.');
        }

        return DB::transaction(function () use ($shiftId, $userId, $type, $amount, $reason) {
            /** @var PosShift $shift */
            $shift = PosShift::query()->lockForUpdate()->findOrFail($shiftId);
            if (! $shift->isOpen()) {
                throw new RuntimeException('Shift is closed.');
            }

            return PosCashMovement::create([
                'shift_id' => $shift->id,
                'type' => $type,
                'amount' => $amount,
                'reason' => $reason,
                'recorded_by_user_id' => $userId,
            ]);
        });
    }

    /**
     * @return array{
     *   opening_float: float,
     *   cash_sales: float,
     *   card_sales: float,
     *   duitnow_sales: float,
     *   wallet_sales: float,
     *   other_sales: float,
     *   gross_sales: float,
     *   order_count: int,
     *   cash_in_total: float,
     *   cash_out_total: float,
     *   expected_cash: float,
     *   movements: array<int, array<string, mixed>>,
     * }
     */
    public function summary(PosShift $shift): array
    {
        $orderRows = Order::query()
            ->where('shift_id', $shift->id)
            ->where('payment_status', 'paid')
            ->get(['payment_method', 'total']);

        $cash = $card = $duitnow = $wallet = $other = 0.0;
        foreach ($orderRows as $o) {
            $amount = (float) $o->total;
            switch ($o->payment_method) {
                case 'cash':
                    $cash += $amount;
                    break;
                case 'card':
                    $card += $amount;
                    break;
                case 'duitnow':
                    $duitnow += $amount;
                    break;
                case 'wallet':
                    $wallet += $amount;
                    break;
                default:
                    $other += $amount;
            }
        }

        $movements = $shift->movements()->orderBy('created_at')->get();
        $cashIn = (float) $movements->where('type', 'cash_in')->sum('amount');
        $cashOut = (float) $movements->where('type', 'cash_out')->sum('amount');

        $opening = (float) $shift->opening_float;
        $expected = round($opening + $cash + $cashIn - $cashOut, 2);

        return [
            'opening_float' => round($opening, 2),
            'cash_sales' => round($cash, 2),
            'card_sales' => round($card, 2),
            'duitnow_sales' => round($duitnow, 2),
            'wallet_sales' => round($wallet, 2),
            'other_sales' => round($other, 2),
            'gross_sales' => round($cash + $card + $duitnow + $wallet + $other, 2),
            'order_count' => $orderRows->count(),
            'cash_in_total' => round($cashIn, 2),
            'cash_out_total' => round($cashOut, 2),
            'expected_cash' => $expected,
            'movements' => $movements->map(fn (PosCashMovement $m) => [
                'id' => $m->id,
                'type' => $m->type,
                'amount' => (float) $m->amount,
                'reason' => $m->reason,
                'recorded_at' => $m->created_at?->toIso8601String(),
            ])->values()->all(),
        ];
    }
}
