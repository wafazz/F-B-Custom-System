<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\PosShift;
use App\Services\Pos\PosShiftService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class ShiftController extends Controller
{
    public function __construct(protected PosShiftService $service) {}

    public function index(Request $request): Response
    {
        $branchId = (int) $request->session()->get('pos.branch_id');
        $branch = Branch::findOrFail($branchId);
        $current = $this->service->currentForBranch($branchId);

        $recent = PosShift::query()
            ->where('branch_id', $branchId)
            ->whereNotNull('closed_at')
            ->latest('closed_at')
            ->with(['openedBy:id,name', 'closedBy:id,name'])
            ->limit(5)
            ->get()
            ->map(fn (PosShift $s) => [
                'id' => $s->id,
                'opened_at' => $s->opened_at->toIso8601String(),
                'closed_at' => $s->closed_at?->toIso8601String(),
                'opened_by' => $s->openedBy?->name,
                'closed_by' => $s->closedBy?->name,
                'opening_float' => (float) $s->opening_float,
                'expected_cash' => (float) ($s->expected_cash ?? 0),
                'counted_cash' => (float) ($s->counted_cash ?? 0),
                'variance' => (float) ($s->variance ?? 0),
            ]);

        return Inertia::render('pos/shift/index', [
            'branch' => [
                'id' => $branch->id,
                'code' => $branch->code,
                'name' => $branch->name,
            ],
            'staff' => ['name' => (string) $request->session()->get('pos.user_name')],
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

    public function open(Request $request): RedirectResponse
    {
        $branchId = (int) $request->session()->get('pos.branch_id');
        $userId = (int) $request->session()->get('pos.user_id');

        $data = $request->validate([
            'opening_float' => ['required', 'numeric', 'min:0', 'max:99999.99'],
        ]);

        try {
            $this->service->open($branchId, $userId, (float) $data['opening_float']);
        } catch (Throwable $e) {
            return back()->withErrors(['shift' => $e->getMessage()]);
        }

        return back()->with('success', 'Shift opened.');
    }

    public function close(Request $request, PosShift $shift): RedirectResponse
    {
        $branchId = (int) $request->session()->get('pos.branch_id');
        abort_unless($shift->branch_id === $branchId, 403);

        $data = $request->validate([
            'counted_cash' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $closed = $this->service->close(
                $shift->id,
                (int) $request->session()->get('pos.user_id'),
                (float) $data['counted_cash'],
                $data['notes'] ?? null,
            );
        } catch (Throwable $e) {
            return back()->withErrors(['shift' => $e->getMessage()]);
        }

        return redirect()
            ->route('pos.shift.report', ['shift' => $closed->id])
            ->with('success', 'Shift closed.');
    }

    public function recordMovement(Request $request, PosShift $shift): RedirectResponse
    {
        $branchId = (int) $request->session()->get('pos.branch_id');
        abort_unless($shift->branch_id === $branchId, 403);

        $data = $request->validate([
            'type' => ['required', 'in:cash_in,cash_out'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999.99'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        try {
            $userId = (int) $request->session()->get('pos.user_id');
            $data['type'] === 'cash_in'
                ? $this->service->recordCashIn($shift->id, $userId, (float) $data['amount'], $data['reason'])
                : $this->service->recordCashOut($shift->id, $userId, (float) $data['amount'], $data['reason']);
        } catch (Throwable $e) {
            return back()->withErrors(['movement' => $e->getMessage()]);
        }

        return back()->with('success', 'Cash movement recorded.');
    }

    public function report(Request $request, PosShift $shift): Response
    {
        $branchId = (int) $request->session()->get('pos.branch_id');
        abort_unless($shift->branch_id === $branchId, 403);

        $shift->loadMissing(['branch', 'openedBy:id,name', 'closedBy:id,name']);
        $summary = $this->service->summary($shift);

        return Inertia::render('pos/shift/report', [
            'branch' => [
                'id' => $shift->branch?->id,
                'code' => $shift->branch?->code,
                'name' => $shift->branch?->name,
            ],
            'staff' => ['name' => (string) $request->session()->get('pos.user_name')],
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
            'summary' => $summary,
        ]);
    }
}
