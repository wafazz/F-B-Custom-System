<?php

namespace App\Http\Controllers\Api;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\BranchStaff;
use App\Models\Order;
use App\Models\User;
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
            'branch' => ['id' => $branch->id, 'code' => $branch->code, 'name' => $branch->name],
            'staff' => ['id' => $user->id, 'name' => $user->name],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json(['ok' => true]);
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

    public function transition(Request $request, Order $order, OrderService $service): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'string', 'in:preparing,ready,completed,cancelled'],
        ]);
        $next = OrderStatus::from($data['status']);
        $service->transition($order, $next);

        /** @var Order $fresh */
        $fresh = $order->fresh(['items.modifiers']);

        return response()->json(['order' => $this->presentOrder($fresh)]);
    }

    /** @return array<string, mixed> */
    protected function presentOrder(Order $o): array
    {
        return [
            'id' => $o->id,
            'number' => $o->number,
            'status' => $o->status->value,
            'order_type' => $o->order_type->value,
            'dine_in_table' => $o->dine_in_table,
            'customer_snapshot' => $o->customer_snapshot,
            'subtotal' => (float) $o->subtotal,
            'total' => (float) $o->total,
            'created_at' => $o->created_at?->toIso8601String(),
            'items' => $o->items->map(fn ($i) => [
                'name' => $i->product_name,
                'quantity' => (int) $i->quantity,
                'modifiers' => $i->modifiers->map(fn ($m) => $m->option_name)->all(),
                'notes' => $i->notes,
            ])->values(),
        ];
    }
}
