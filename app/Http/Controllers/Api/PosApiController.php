<?php

namespace App\Http\Controllers\Api;

use App\Enums\OrderStatus;
use App\Events\PrintReceiptRequested;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\BranchStaff;
use App\Models\Order;
use App\Models\User;
use App\Services\Loyalty\LoyaltyService;
use App\Services\Orders\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class PosApiController extends Controller
{
    /** Issue a Sanctum token to a staff PIN paired with a branch. */
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'branch_code' => ['required', 'string'],
            'pin' => ['required', 'string'],
        ]);

        $branch = Branch::query()->where('code', $data['branch_code'])->first();
        if (! $branch) {
            throw ValidationException::withMessages(['branch_code' => 'Unknown branch.']);
        }

        $candidates = BranchStaff::query()
            ->where('branch_id', $branch->id)
            ->where('is_active', true)
            ->get();
        $match = $candidates->first(fn (BranchStaff $bs) => Hash::check($data['pin'], (string) $bs->pin));
        if (! $match) {
            throw ValidationException::withMessages(['pin' => 'Invalid PIN.']);
        }

        /** @var User $user */
        $user = User::query()->findOrFail($match->user_id);
        $token = $user->createToken("pos:{$branch->code}", ['pos'])->plainTextToken;

        return response()->json([
            'token' => $token,
            // Full branch shape (incl. tax config) so the walk-in screen can
            // compute SST/service immediately after login — must match me().
            'branch' => [
                'id' => $branch->id,
                'code' => $branch->code,
                'name' => $branch->name,
                'sst_rate' => (float) $branch->sst_rate,
                'sst_enabled' => (bool) $branch->sst_enabled,
                'service_charge_rate' => (float) $branch->service_charge_rate,
                'service_charge_enabled' => (bool) $branch->service_charge_enabled,
            ],
            'staff' => ['id' => $user->id, 'name' => $user->name],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json(['ok' => true]);
    }

    /** Currently authenticated POS staff + the branch their token is scoped to. */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $code = (string) $request->attributes->get('pos_branch_code');
        $branch = Branch::query()->where('code', $code)->firstOrFail();

        return response()->json([
            'staff' => ['id' => $user->id, 'name' => $user->name],
            'branch' => [
                'id' => $branch->id,
                'code' => $branch->code,
                'name' => $branch->name,
                'sst_rate' => (float) $branch->sst_rate,
                'sst_enabled' => (bool) $branch->sst_enabled,
                'service_charge_rate' => (float) $branch->service_charge_rate,
                'service_charge_enabled' => (bool) $branch->service_charge_enabled,
            ],
        ]);
    }

    /** Live queue list — pending + preparing + ready, scoped to a branch. */
    public function queue(Branch $branch): JsonResponse
    {
        $orders = Order::query()
            ->where('branch_id', $branch->id)
            ->whereIn('status', [OrderStatus::Pending, OrderStatus::Preparing, OrderStatus::Ready])
            ->with(['items.modifiers'])
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn (Order $o) => $this->presentOrder($o))
            ->values();

        return response()->json(['orders' => $orders]);
    }

    /**
     * Recent orders for the branch — preparing, ready, completed, cancelled.
     * Optional ?status=<one> narrows to a single bucket; ?limit caps the
     * page size (default 50, max 100). Sorted newest first.
     */
    public function recent(Request $request, Branch $branch): JsonResponse
    {
        $statuses = [
            OrderStatus::Preparing,
            OrderStatus::Ready,
            OrderStatus::Completed,
            OrderStatus::Cancelled,
        ];

        $data = $request->validate([
            'status' => ['nullable', 'string', 'in:preparing,ready,completed,cancelled'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $filterStatuses = isset($data['status'])
            ? [OrderStatus::from($data['status'])]
            : $statuses;
        $limit = (int) ($data['limit'] ?? 50);

        $orders = Order::query()
            ->where('branch_id', $branch->id)
            ->whereIn('status', $filterStatuses)
            ->with(['items.modifiers'])
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (Order $o) => $this->presentOrder($o))
            ->values();

        return response()->json(['orders' => $orders]);
    }

    /** Fire a broadcast that the branch print runner picks up and forwards to the WiFi printer. */
    public function print(Request $request, Order $order): JsonResponse
    {
        $this->authorizeOrderBranch($request, $order);
        PrintReceiptRequested::dispatch($order);

        return response()->json(['ok' => true]);
    }

    /**
     * Rich receipt payload the mobile app feeds to its native printer
     * library (or renders for an on-screen PDF). Same shape as the web
     * `/pos/orders/{order}/receipt-data` endpoint so the two clients can
     * share serialisers.
     */
    public function receipt(Request $request, Order $order, LoyaltyService $loyalty): JsonResponse
    {
        $this->authorizeOrderBranch($request, $order);
        $order->loadMissing(['branch', 'items.modifiers', 'user', 'redemptions.voucher:id,code,name']);
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
            'tumbler_discount_amount' => (float) ($order->tumbler_discount_amount ?? 0),
            'total' => (float) $order->total,
            'notes' => $order->notes,
            'customer_name' => $order->user?->name,
            'points_earned' => $pointsEarned,
            'vouchers' => $order->redemptions->map(fn ($r) => [
                'code' => $r->voucher?->code,
                'name' => $r->voucher?->name,
                'discount_amount' => (float) $r->discount_amount,
            ])->values()->all(),
            'items' => $order->items->map(fn ($i) => [
                'name' => $i->product_name,
                'quantity' => (int) $i->quantity,
                'unit_price' => (float) $i->unit_price,
                'line_total' => (float) $i->line_total,
                'voucher_code' => $i->voucher_code,
                'voucher_role' => $i->voucher_role ?? null,
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

    public function transition(Request $request, Order $order, OrderService $service): JsonResponse
    {
        $this->authorizeOrderBranch($request, $order);
        $data = $request->validate([
            'status' => ['required', 'string', 'in:preparing,ready,completed,cancelled'],
        ]);
        $next = OrderStatus::from($data['status']);
        $service->transition($order, $next);

        /** @var Order $fresh */
        $fresh = $order->fresh(['items.modifiers']);

        return response()->json(['order' => $this->presentOrder($fresh)]);
    }

    /**
     * Update the customer's order-level remark from the POS app. Useful when
     * the customer rings in or amends their request after placing the order
     * ("please pack separately"). Branch-scoped via the POS token.
     */
    public function updateNotes(Request $request, Order $order): JsonResponse
    {
        $this->authorizeOrderBranch($request, $order);
        $data = $request->validate([
            'notes' => ['nullable', 'string', 'max:500'],
        ]);
        $order->update(['notes' => $data['notes'] ?? null]);

        /** @var Order $fresh */
        $fresh = $order->fresh(['items.modifiers']);

        return response()->json(['order' => $this->presentOrder($fresh)]);
    }

    /**
     * Update a single line-item remark (e.g. "extra cold for this latte
     * only"). Guards that the item belongs to the order and the order to
     * the token's branch.
     */
    public function updateItemNotes(Request $request, Order $order, \App\Models\OrderItem $item): JsonResponse
    {
        $this->authorizeOrderBranch($request, $order);
        abort_unless($item->order_id === $order->id, 404, 'Item does not belong to this order.');

        $data = $request->validate([
            'notes' => ['nullable', 'string', 'max:200'],
        ]);
        $item->update(['notes' => $data['notes'] ?? null]);

        /** @var Order $fresh */
        $fresh = $order->fresh(['items.modifiers']);

        return response()->json(['order' => $this->presentOrder($fresh)]);
    }

    /** Guard cross-branch order access via the token's branch scope. */
    protected function authorizeOrderBranch(Request $request, Order $order): void
    {
        $code = (string) $request->attributes->get('pos_branch_code');
        $branch = Branch::query()->where('code', $code)->first();
        abort_unless($branch && $order->branch_id === $branch->id, 403, 'Order belongs to another branch.');
    }

    /** @return array<string, mixed> */
    protected function presentOrder(Order $o): array
    {
        $o->loadMissing(['redemptions.voucher:id,code,name']);
        $vouchers = $o->redemptions->map(fn ($r) => [
            'code' => $r->voucher?->code,
            'name' => $r->voucher?->name,
            'discount_amount' => (float) $r->discount_amount,
        ])->values()->all();

        return [
            'id' => $o->id,
            'number' => $o->number,
            'status' => $o->status->value,
            'order_type' => $o->order_type->value,
            'dine_in_table' => $o->dine_in_table,
            'customer_snapshot' => $o->customer_snapshot,
            'subtotal' => (float) $o->subtotal,
            'sst_amount' => (float) $o->sst_amount,
            'service_charge_amount' => (float) ($o->service_charge_amount ?? 0),
            'discount_amount' => (float) ($o->discount_amount ?? 0),
            'tumbler_discount_amount' => (float) ($o->tumbler_discount_amount ?? 0),
            'total' => (float) $o->total,
            'notes' => $o->notes,
            'created_at' => $o->created_at?->toIso8601String(),
            'vouchers' => $vouchers,
            'items' => $o->items->map(fn ($i) => [
                'name' => $i->product_name,
                'quantity' => (int) $i->quantity,
                'modifiers' => $i->modifiers->map(fn ($m) => $m->option_name)->all(),
                'notes' => $i->notes,
                'voucher_code' => $i->voucher_code,
                'voucher_role' => $i->voucher_role ?? null,
            ])->values(),
        ];
    }
}
