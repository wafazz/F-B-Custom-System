<?php

namespace App\Http\Controllers\Api\Pos;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\PosShift;
use App\Services\Pos\PosShiftService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class ShiftController extends Controller
{
    public function __construct(protected PosShiftService $service) {}

    /** Current open shift (with live summary) + the 5 most recent closed shifts. */
    public function current(Branch $branch): JsonResponse
    {
        $current = $this->service->currentForBranch($branch->id);

        $recent = PosShift::query()
            ->where('branch_id', $branch->id)
            ->whereNotNull('closed_at')
            ->latest('closed_at')
            ->with(['openedBy:id,name', 'closedBy:id,name'])
            ->limit(5)
            ->get()
            ->map(fn (PosShift $s) => $this->presentClosed($s))
            ->values();

        return response()->json([
            'current' => $current ? [
                'id' => $current->id,
                'opened_at' => $current->opened_at->toIso8601String(),
                'opened_by' => $current->openedBy?->name,
                'opening_float' => (float) $current->opening_float,
                'summary' => $this->service->summary($current),
            ] : null,
            'recent' => $recent,
        ]);
    }

    public function open(Request $request, Branch $branch): JsonResponse
    {
        $data = $request->validate([
            'opening_float' => ['required', 'numeric', 'min:0', 'max:99999.99'],
        ]);

        try {
            $shift = $this->service->open($branch->id, (int) $request->user()->id, (float) $data['opening_float']);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'shift' => [
                'id' => $shift->id,
                'opened_at' => $shift->opened_at->toIso8601String(),
                'opening_float' => (float) $shift->opening_float,
            ],
        ], 201);
    }

    public function close(Request $request, PosShift $shift): JsonResponse
    {
        $this->authorizeShiftBranch($request, $shift);

        $data = $request->validate([
            'counted_cash' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $closed = $this->service->close(
                $shift->id,
                (int) $request->user()->id,
                (float) $data['counted_cash'],
                $data['notes'] ?? null,
            );
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $closed->loadMissing(['openedBy:id,name', 'closedBy:id,name']);

        return response()->json([
            'shift' => $this->presentClosed($closed) + ['summary' => $this->service->summary($closed)],
        ]);
    }

    public function recordMovement(Request $request, PosShift $shift): JsonResponse
    {
        $this->authorizeShiftBranch($request, $shift);

        $data = $request->validate([
            'type' => ['required', 'in:cash_in,cash_out'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999.99'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        try {
            $userId = (int) $request->user()->id;
            $data['type'] === 'cash_in'
                ? $this->service->recordCashIn($shift->id, $userId, (float) $data['amount'], $data['reason'])
                : $this->service->recordCashOut($shift->id, $userId, (float) $data['amount'], $data['reason']);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['ok' => true, 'summary' => $this->service->summary($shift->fresh() ?? $shift)]);
    }

    /** Full report for a single (open or closed) shift. */
    public function report(Request $request, PosShift $shift): JsonResponse
    {
        $this->authorizeShiftBranch($request, $shift);
        $shift->loadMissing(['openedBy:id,name', 'closedBy:id,name']);

        return response()->json([
            'shift' => [
                'id' => $shift->id,
                'opened_at' => $shift->opened_at->toIso8601String(),
                'closed_at' => $shift->closed_at?->toIso8601String(),
                'opened_by' => $shift->openedBy?->name,
                'closed_by' => $shift->closedBy?->name,
                'opening_float' => (float) $shift->opening_float,
                'expected_cash' => (float) ($shift->expected_cash ?? 0),
                'counted_cash' => (float) ($shift->counted_cash ?? 0),
                'variance' => (float) ($shift->variance ?? 0),
                'notes' => $shift->notes,
            ],
            'summary' => $this->service->summary($shift),
        ]);
    }

    protected function authorizeShiftBranch(Request $request, PosShift $shift): void
    {
        $code = (string) $request->attributes->get('pos_branch_code');
        $branch = Branch::query()->where('code', $code)->first();
        abort_unless($branch && $shift->branch_id === $branch->id, 403, 'Shift belongs to another branch.');
    }

    /** @return array<string, mixed> */
    protected function presentClosed(PosShift $s): array
    {
        return [
            'id' => $s->id,
            'opened_at' => $s->opened_at->toIso8601String(),
            'closed_at' => $s->closed_at?->toIso8601String(),
            'opened_by' => $s->openedBy?->name,
            'closed_by' => $s->closedBy?->name,
            'opening_float' => (float) $s->opening_float,
            'expected_cash' => (float) ($s->expected_cash ?? 0),
            'counted_cash' => (float) ($s->counted_cash ?? 0),
            'variance' => (float) ($s->variance ?? 0),
        ];
    }
}
