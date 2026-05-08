<?php

namespace App\Http\Controllers\Web;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\BranchDisplayToken;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DisplayController extends Controller
{
    public function show(Branch $branch, Request $request): Response
    {
        $token = (string) $request->query('token', '');
        $row = BranchDisplayToken::query()
            ->where('branch_id', $branch->id)
            ->where('token', $token)
            ->where('is_active', true)
            ->first();
        abort_if($row === null, 403, 'Invalid or revoked display token.');

        $row->forceFill(['last_seen_at' => now()])->save();

        $orders = Order::query()
            ->where('branch_id', $branch->id)
            ->where('order_type', OrderType::DineIn->value)
            ->whereIn('status', [OrderStatus::Preparing->value, OrderStatus::Ready->value])
            ->orderBy('updated_at')
            ->get(['id', 'number', 'dine_in_table', 'status', 'updated_at']);

        $preparing = [];
        $ready = [];
        foreach ($orders as $o) {
            $row = [
                'id' => $o->id,
                'number' => $o->number,
                'table' => $o->dine_in_table,
            ];
            if ($o->status === OrderStatus::Ready) {
                $ready[] = $row;
            } else {
                $preparing[] = $row;
            }
        }

        return Inertia::render('display/board', [
            'branch' => [
                'id' => $branch->id,
                'code' => $branch->code,
                'name' => $branch->name,
                'logo' => $branch->logo,
            ],
            'token' => $token,
            'preparing' => $preparing,
            'ready' => $ready,
            'reverb' => [
                'channel' => "branch.{$branch->id}.display",
                'queued_event' => 'dine-in.queued',
                'ready_event' => 'dine-in.ready',
            ],
            'settings' => $row->settings ?? [],
        ]);
    }

    public function heartbeat(Branch $branch, Request $request): JsonResponse
    {
        $token = (string) $request->query('token', '');
        $row = BranchDisplayToken::query()
            ->where('branch_id', $branch->id)
            ->where('token', $token)
            ->where('is_active', true)
            ->first();
        abort_if($row === null, 403);

        $row->forceFill(['last_seen_at' => now()])->save();

        return response()->json(['ok' => true, 'at' => now()->toIso8601String()]);
    }
}
