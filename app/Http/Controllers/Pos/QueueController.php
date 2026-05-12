<?php

namespace App\Http\Controllers\Pos;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Order;
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
