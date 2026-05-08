<?php

namespace App\Http\Controllers\Pos;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use App\Services\Orders\OrderLinePayload;
use App\Services\Orders\OrderPayload;
use App\Services\Orders\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class WalkInController extends Controller
{
    public function index(Request $request): Response
    {
        $branchId = (int) $request->session()->get('pos.branch_id');
        $branch = Branch::findOrFail($branchId);

        $products = Product::availableAtBranch($branchId)
            ->with([
                'category:id,name',
                'modifierGroups.options' => fn ($q) => $q->where('is_available', true),
                'branches' => fn ($q) => $q->where('branches.id', $branchId),
            ])
            ->orderBy('category_id')
            ->orderBy('sort_order')
            ->get();

        $byCategory = [];
        foreach ($products as $p) {
            /** @var Category|null $category */
            $category = $p->category;
            $cat = $category instanceof Category ? $category->name : 'Other';
            $byCategory[$cat][] = [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'price' => $p->priceForBranch($branchId),
                'modifier_groups' => $p->modifierGroups->map(function ($g) {
                    $options = [];
                    foreach ($g->options as $o) {
                        $options[] = [
                            'id' => $o->id,
                            'name' => $o->name,
                            'price_delta' => (float) $o->price_delta,
                            'is_default' => $o->is_default,
                        ];
                    }

                    return [
                        'id' => $g->id,
                        'name' => $g->name,
                        'selection_type' => $g->selection_type,
                        'is_required' => $g->is_required,
                        'min_select' => $g->min_select,
                        'max_select' => $g->max_select,
                        'options' => $options,
                    ];
                })->values()->all(),
            ];
        }

        return Inertia::render('pos/walk-in', [
            'branch' => [
                'id' => $branch->id,
                'code' => $branch->code,
                'name' => $branch->name,
                'sst_rate' => (float) $branch->sst_rate,
                'sst_enabled' => $branch->sst_enabled,
            ],
            'staff' => ['name' => $request->session()->get('pos.user_name')],
            'categories' => collect($byCategory)->map(fn ($items, $name) => [
                'name' => $name,
                'products' => $items,
            ])->values(),
        ]);
    }

    public function store(Request $request, OrderService $service): RedirectResponse
    {
        $branchId = (int) $request->session()->get('pos.branch_id');

        $data = $request->validate([
            'order_type' => ['required', 'in:pickup,dine_in'],
            'dine_in_table' => ['nullable', 'string', 'max:20', 'required_if:order_type,dine_in'],
            'payment_method' => ['required', 'in:cash,card,duitnow'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'lines.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'lines.*.modifier_option_ids' => ['array'],
            'lines.*.modifier_option_ids.*' => ['integer', 'exists:modifier_options,id'],
        ]);

        try {
            $order = $service->place(new OrderPayload(
                branchId: $branchId,
                userId: (int) $request->session()->get('pos.user_id'),
                orderType: OrderType::from($data['order_type']),
                lines: collect($data['lines'])->map(fn ($l) => new OrderLinePayload(
                    productId: (int) $l['product_id'],
                    quantity: (int) $l['quantity'],
                    modifierOptionIds: array_map('intval', $l['modifier_option_ids'] ?? []),
                ))->all(),
                dineInTable: $data['dine_in_table'] ?? null,
                customerSnapshot: ['source' => 'walk-in', 'staff_id' => $request->session()->get('pos.user_id')],
            ));

            // Walk-in is paid in person, mark immediately + advance to Preparing.
            $order->update([
                'payment_status' => PaymentStatus::Paid,
                'payment_method' => $data['payment_method'],
                'payment_reference' => 'WALKIN-'.$order->number,
                'paid_at' => now(),
            ]);
            $service->transition($order->fresh() ?? $order, OrderStatus::Preparing);
        } catch (Throwable $e) {
            return back()->withErrors(['order' => $e->getMessage()]);
        }

        return redirect()->route('pos.queue')->with('success', "Order {$order->number} placed");
    }
}
