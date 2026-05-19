<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\SpinWheelSegment;
use App\Services\Spin\SpinService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class SpinController extends Controller
{
    public function index(Request $request, SpinService $service): Response
    {
        $userId = (int) $request->user()->getKey();

        $segments = SpinWheelSegment::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (SpinWheelSegment $s) => [
                'id' => $s->id,
                'label' => $s->label,
                'color' => $s->color,
            ])
            ->values();

        return Inertia::render('storefront/spin', [
            'segments' => $segments,
            'can_spin' => $service->canSpin($userId),
        ]);
    }

    public function spin(Request $request, SpinService $service): JsonResponse
    {
        $userId = (int) $request->user()->getKey();

        try {
            $attempt = $service->spin($userId);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $segment = $attempt->segment;

        return response()->json([
            'segment_id' => $segment->id,
            'label' => $segment->label,
            'awarded_points' => $attempt->awarded_points,
            'voucher_claimed' => $attempt->voucher_claim_id !== null,
            'message' => $this->buildMessage($attempt),
        ]);
    }

    protected function buildMessage(\App\Models\SpinAttempt $attempt): string
    {
        if ($attempt->awarded_points > 0) {
            return "+{$attempt->awarded_points} pts credited!";
        }
        if ($attempt->voucher_claim_id !== null) {
            return 'Voucher added to your wallet!';
        }

        return 'Better luck next time — see you tomorrow!';
    }
}
