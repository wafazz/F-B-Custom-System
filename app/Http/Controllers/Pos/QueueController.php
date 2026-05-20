<?php

namespace App\Http\Controllers\Pos;

use App\Enums\OrderStatus;
use App\Events\PrintReceiptRequested;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Order;
use App\Services\Loyalty\LoyaltyService;
use App\Services\Orders\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class QueueController extends Controller
{
    public function index(Request $request): Response
    {
        $branchId = (int) $request->session()->get('pos.branch_id');
        $branch = Branch::findOrFail($branchId);

        $orders = Order::query()
            ->with('items.modifiers')
            ->where('branch_id', $branchId)
            ->whereIn('status', [
                OrderStatus::Pending->value,
                OrderStatus::Preparing->value,
                OrderStatus::Ready->value,
            ])
            ->orderBy('created_at')
            ->get();

        return Inertia::render('pos/queue', [
            'branch' => [
                'id' => $branch->id,
                'code' => $branch->code,
                'name' => $branch->name,
                'auto_print_labels' => (bool) $branch->auto_print_labels,
                'label_copies' => (int) ($branch->label_copies ?? 1),
                'label_size' => (string) ($branch->label_size ?? '58mm'),
            ],
            'staff' => [
                'name' => $request->session()->get('pos.user_name'),
            ],
            'orders' => $orders->map(fn (Order $o) => $this->present($o))->values(),
            'reverb' => [
                'channel' => "branch.{$branchId}.orders",
                'event' => 'order.status.changed',
            ],
        ]);
    }

    /** Fire a broadcast that the branch print runner forwards to the WiFi printer. */
    public function printReceipt(Request $request, Order $order): RedirectResponse
    {
        $branchId = (int) $request->session()->get('pos.branch_id');
        abort_unless($order->branch_id === $branchId, 403);

        PrintReceiptRequested::dispatch($order);

        return back()->with('success', 'Receipt sent to printer');
    }

    /** Receipt payload — same shape as the broadcast, used by the SUNMI bridge in the PWA. */
    public function receiptPayload(Request $request, Order $order): \Illuminate\Http\JsonResponse
    {
        $branchId = (int) $request->session()->get('pos.branch_id');
        abort_unless($order->branch_id === $branchId, 403);

        return response()->json((new PrintReceiptRequested($order))->broadcastWith());
    }

    /**
     * Rich receipt shape consumed by the browser-print fallback in queue.tsx
     * (matches the flash receipt array built by WalkInController::store).
     * Used when no SUNMI bridge is reachable so the cashier still gets a
     * system print dialog for the receipt instead of nothing.
     */
    public function receiptData(Request $request, Order $order, LoyaltyService $loyalty): \Illuminate\Http\JsonResponse
    {
        $branchId = (int) $request->session()->get('pos.branch_id');
        abort_unless($order->branch_id === $branchId, 403);

        $order->loadMissing(['branch', 'items.modifiers', 'user']);
        $branch = $order->branch;

        $pointsEarned = $order->user_id
            ? (int) floor((float) $order->subtotal * $loyalty->multiplierFor((int) $order->user_id))
            : 0;

        return response()->json([
            'number' => $order->number,
            'order_type' => $order->order_type->value,
            'dine_in_table' => $order->dine_in_table,
            'created_at' => $order->created_at?->toIso8601String(),
            'paid_at' => $order->paid_at?->toIso8601String(),
            'payment_method' => $order->payment_method,
            'payment_reference' => $order->payment_reference,
            'subtotal' => (float) $order->subtotal,
            'sst_amount' => (float) $order->sst_amount,
            'service_charge_amount' => (float) ($order->service_charge_amount ?? 0),
            'discount_amount' => (float) ($order->discount_amount ?? 0),
            'total' => (float) $order->total,
            'customer_name' => $order->user?->name,
            'points_earned' => $pointsEarned,
            'items' => $order->items->map(fn ($i) => [
                'name' => $i->product_name,
                'quantity' => (int) $i->quantity,
                'unit_price' => (float) $i->unit_price,
                'line_total' => (float) $i->line_total,
                'modifiers' => $i->modifiers
                    ->map(fn ($m) => ['option_name' => $m->option_name])
                    ->values()
                    ->all(),
            ])->values()->all(),
            'branch' => [
                'name' => $branch->name,
                'address' => $branch->address,
                'receipt_header' => $branch->receipt_header,
                'receipt_footer' => $branch->receipt_footer,
                'sst_rate' => (float) $branch->sst_rate,
                'service_charge_rate' => (float) $branch->service_charge_rate,
                'label_size' => (string) ($branch->label_size ?? '58mm'),
            ],
        ]);
    }

    public function transition(Request $request, Order $order, OrderService $service): RedirectResponse
    {
        $branchId = (int) $request->session()->get('pos.branch_id');
        abort_unless($order->branch_id === $branchId, 403);

        $next = OrderStatus::from((string) $request->validate([
            'status' => ['required', 'string'],
        ])['status']);

        try {
            $service->transition($order, $next);
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "→ {$next->label()}");
    }

    /** @return array<string, mixed> */
    protected function present(Order $order): array
    {
        $items = [];
        foreach ($order->items as $item) {
            $modifiers = [];
            foreach ($item->modifiers as $m) {
                $modifiers[] = ['group_name' => $m->group_name, 'option_name' => $m->option_name];
            }
            $items[] = [
                'id' => $item->id,
                'name' => $item->product_name,
                'quantity' => $item->quantity,
                'modifiers' => $modifiers,
                'notes' => $item->notes,
            ];
        }

        return [
            'id' => $order->id,
            'number' => $order->number,
            'status' => $order->status->value,
            'order_type' => $order->order_type->value,
            'dine_in_table' => $order->dine_in_table,
            'pickup_at' => $order->pickup_at?->toIso8601String(),
            'created_at' => $order->created_at?->toIso8601String(),
            'total' => (float) $order->total,
            'notes' => $order->notes,
            'items' => $items,
        ];
    }
}
