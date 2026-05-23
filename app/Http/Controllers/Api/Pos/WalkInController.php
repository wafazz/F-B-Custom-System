<?php

namespace App\Http\Controllers\Api\Pos;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Category;
use App\Models\CustomerTier;
use App\Models\Product;
use App\Models\User;
use App\Services\Loyalty\LoyaltyService;
use App\Services\Orders\OrderLinePayload;
use App\Services\Orders\OrderPayload;
use App\Services\Orders\OrderService;
use App\Services\Pos\PosShiftService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class WalkInController extends Controller
{
    /** Full POS menu tree (parent category → child category → products with modifiers). */
    public function menu(Branch $branch): JsonResponse
    {
        $products = Product::availableAtBranch($branch->id)
            ->whereHas('category', fn ($q) => $q->where('available_pos', true))
            ->with([
                'category:id,name,parent_id,available_pos',
                'category.parent:id,name',
                'modifierGroups.options' => fn ($q) => $q->where('is_available', true)->orderBy('sort_order')->orderBy('id'),
                'branches' => fn ($q) => $q->where('branches.id', $branch->id),
            ])
            ->orderBy('category_id')
            ->orderBy('sort_order')
            ->get();

        /** @var array<string, array<string, array<int, array<string, mixed>>>> $tree */
        $tree = [];
        foreach ($products as $p) {
            /** @var Category|null $category */
            $category = $p->category;
            $child = $category instanceof Category ? $category->name : 'Other';
            $parent = $category?->parent instanceof Category ? $category->parent->name : $child;
            $tree[$parent][$child][] = [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'price' => $p->priceForBranch($branch->id),
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
                        'allow_quantity' => (bool) $g->allow_quantity,
                        'options' => $options,
                    ];
                })->values()->all(),
            ];
        }

        $parents = [];
        foreach ($tree as $parentName => $children) {
            $childList = [];
            foreach ($children as $childName => $items) {
                $childList[] = ['name' => $childName, 'products' => $items];
            }
            $parents[] = ['name' => $parentName, 'children' => $childList];
        }

        return response()->json([
            'branch' => [
                'id' => $branch->id,
                'code' => $branch->code,
                'name' => $branch->name,
                'sst_rate' => (float) $branch->sst_rate,
                'sst_enabled' => (bool) $branch->sst_enabled,
                'service_charge_rate' => (float) $branch->service_charge_rate,
                'service_charge_enabled' => (bool) $branch->service_charge_enabled,
            ],
            'parents' => $parents,
        ]);
    }

    /** Customer lookup for linking a walk-in to a member account. */
    public function searchCustomers(Request $request, LoyaltyService $loyalty): JsonResponse
    {
        $term = trim((string) $request->query('q', ''));
        if (mb_strlen($term) < 2) {
            return response()->json(['results' => []]);
        }

        $needle = '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%';
        $staffRoles = ['super_admin', 'hq_admin', 'ops_manager', 'mkt_manager', 'branch_manager', 'cashier', 'barista'];
        $users = User::query()
            ->whereDoesntHave('roles', fn ($q) => $q->whereIn('name', $staffRoles))
            ->where(function ($q) use ($needle, $term) {
                $q->where('name', 'like', $needle)
                    ->orWhere('phone', 'like', $needle)
                    ->orWhere('email', 'like', $needle)
                    ->orWhere('referral_code', '=', $term);
            })
            ->limit(8)
            ->get(['id', 'name', 'phone', 'email', 'referral_code']);

        $tiers = CustomerTier::query()
            ->whereIn('user_id', $users->pluck('id'))
            ->with('tier:id,name')
            ->get()
            ->keyBy('user_id');

        $results = $users->map(fn (User $u) => [
            'id' => $u->id,
            'name' => $u->name,
            'phone' => $u->phone,
            'email' => $u->email,
            'referral_code' => $u->referral_code,
            'points' => $loyalty->balance($u->id),
            'tier' => $tiers->get($u->id)?->tier?->name,
        ])->values();

        return response()->json(['results' => $results]);
    }

    /** Take a walk-in order; charges immediately and pushes it to Preparing. */
    public function store(
        Request $request,
        Branch $branch,
        OrderService $service,
        LoyaltyService $loyalty,
        PosShiftService $shifts,
    ): JsonResponse {
        $shift = $shifts->currentForBranch($branch->id);
        if (! $shift) {
            return response()->json([
                'message' => 'No open shift. Open a shift before taking orders.',
            ], 422);
        }

        $data = $request->validate([
            'order_type' => ['required', 'in:pickup,dine_in'],
            'dine_in_table' => ['nullable', 'string', 'max:20', 'required_if:order_type,dine_in'],
            'payment_method' => ['required', 'in:cash,card,duitnow'],
            'customer_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:500'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'lines.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'lines.*.modifier_option_ids' => ['array'],
            'lines.*.modifier_option_ids.*' => ['integer', 'exists:modifier_options,id'],
            'lines.*.notes' => ['nullable', 'string', 'max:200'],
        ]);

        $cashierId = (int) $request->user()->id;
        $customerId = isset($data['customer_user_id']) ? (int) $data['customer_user_id'] : null;

        try {
            $order = $service->place(new OrderPayload(
                branchId: $branch->id,
                userId: $customerId,
                orderType: OrderType::from($data['order_type']),
                lines: collect($data['lines'])->map(fn ($l) => new OrderLinePayload(
                    productId: (int) $l['product_id'],
                    quantity: (int) $l['quantity'],
                    modifierOptionIds: array_map('intval', $l['modifier_option_ids'] ?? []),
                    notes: isset($l['notes']) ? (string) $l['notes'] : null,
                ))->all(),
                dineInTable: $data['dine_in_table'] ?? null,
                notes: $data['notes'] ?? null,
                customerSnapshot: array_filter([
                    'source' => 'walk-in',
                    'staff_id' => $cashierId,
                    'customer_user_id' => $customerId,
                ]),
                shiftId: $shift->id,
            ));

            $order->update([
                'payment_status' => PaymentStatus::Paid,
                'payment_method' => $data['payment_method'],
                'payment_reference' => 'WALKIN-'.$order->number,
                'paid_at' => now(),
            ]);
            $service->transition($order->fresh() ?? $order, OrderStatus::Preparing);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $fresh = $order->fresh(['items.modifiers', 'user', 'branch']) ?? $order;
        $pointsEarned = $customerId
            ? (int) floor((float) $fresh->subtotal * $loyalty->multiplierFor($customerId))
            : 0;

        return response()->json([
            'order' => [
                'id' => (int) $fresh->id,
                'number' => $fresh->number,
                'status' => $fresh->status->value,
                'order_type' => $fresh->order_type->value,
                'dine_in_table' => $fresh->dine_in_table,
                'created_at' => $fresh->created_at?->toIso8601String(),
                'paid_at' => $fresh->paid_at?->toIso8601String(),
                'payment_method' => $fresh->payment_method,
                'payment_reference' => $fresh->payment_reference,
                'subtotal' => (float) $fresh->subtotal,
                'sst_amount' => (float) $fresh->sst_amount,
                'service_charge_amount' => (float) ($fresh->service_charge_amount ?? 0),
                'discount_amount' => (float) ($fresh->discount_amount ?? 0),
                'total' => (float) $fresh->total,
                'customer_name' => $fresh->user?->name,
                'points_earned' => $pointsEarned,
                'items' => $fresh->items->map(fn ($i) => [
                    'name' => $i->product_name,
                    'quantity' => (int) $i->quantity,
                    'unit_price' => (float) $i->unit_price,
                    'line_total' => (float) $i->line_total,
                    'modifiers' => $i->modifiers->map(fn ($m) => ['option_name' => $m->option_name])->values(),
                ])->values(),
            ],
        ], 201);
    }
}
