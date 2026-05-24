<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SendAbandonedCartReminder;
use App\Models\CustomerCart;
use App\Models\ScheduledCampaign;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartSyncController extends Controller
{
    /**
     * Mirror a logged-in customer's browser cart server-side so an abandoned
     * cart can be detected. Guests are ignored (we can't push to them).
     */
    public function sync(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            return response()->json(['ok' => false], 401);
        }

        $data = $request->validate([
            'branch_id' => ['nullable', 'integer'],
            'item_count' => ['required', 'integer', 'min:0', 'max:999'],
            'subtotal' => ['nullable', 'numeric', 'min:0'],
            'items' => ['nullable', 'array', 'max:100'],
        ]);

        // Empty cart → drop the row (cleared / checked out elsewhere).
        if ((int) $data['item_count'] < 1) {
            CustomerCart::query()->where('user_id', $user->getKey())->delete();

            return response()->json(['ok' => true, 'cleared' => true]);
        }

        // firstOrNew (not updateOrCreate) so we never clobber notified_at —
        // re-syncing an unchanged-but-edited cart keeps "already reminded".
        $cart = CustomerCart::query()->firstOrNew(['user_id' => $user->getKey()]);
        $cart->branch_id = $data['branch_id'] ?? null;
        $cart->item_count = (int) $data['item_count'];
        $cart->subtotal = (float) ($data['subtotal'] ?? 0);
        $cart->items = $data['items'] ?? [];
        $cart->save();

        // Only schedule a reminder if the admin has the abandoned-cart
        // campaign switched on; its delay drives the timer.
        $campaign = ScheduledCampaign::activeAbandonedCart();
        if ($campaign !== null) {
            $delay = $campaign->delay_minutes ?: (int) config('services.abandoned_cart.delay_minutes', 15);
            SendAbandonedCartReminder::dispatch((int) $user->getKey())
                ->delay(now()->addMinutes($delay));
        }

        return response()->json(['ok' => true]);
    }
}
