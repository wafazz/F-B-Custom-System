<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PointTransaction;
use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AccountController extends Controller
{
    /** PDPA right-to-access: download all personal data as JSON. */
    public function dataExport(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $payload = [
            'user' => $user->only([
                'id', 'name', 'email', 'phone', 'date_of_birth', 'gender',
                'referral_code', 'preferred_branch_id', 'locale',
                'marketing_consent', 'whatsapp_consent', 'push_consent',
                'created_at', 'updated_at',
            ]),
            'orders' => Order::with('items.modifiers')
                ->where('user_id', $user->getKey())
                ->get()
                ->map(fn ($o) => $o->toArray())
                ->all(),
            'point_transactions' => PointTransaction::query()
                ->where('user_id', $user->getKey())
                ->get()
                ->map(fn ($r) => $r->only(['type', 'points', 'balance_after', 'reason', 'created_at']))
                ->all(),
            'push_subscriptions_count' => PushSubscription::query()->where('user_id', $user->getKey())->count(),
            'exported_at' => now()->toIso8601String(),
        ];

        return response()->json($payload, 200, [
            'Content-Disposition' => 'attachment; filename="star-coffee-data-export.json"',
        ]);
    }

    /**
     * PDPA right-to-erasure: anonymise the user, drop all push subscriptions,
     * scrub customer_snapshot on past orders. Soft-delete the user record so
     * historic order rows still link via foreign key.
     */
    public function destroy(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        DB::transaction(function () use ($user) {
            PushSubscription::query()->where('user_id', $user->getKey())->delete();

            Order::query()->where('user_id', $user->getKey())->update([
                'customer_snapshot' => null,
            ]);

            $user->forceFill([
                'name' => 'Deleted User',
                'email' => 'deleted+'.$user->getKey().'@starcoffee.deleted',
                'phone' => null,
                'date_of_birth' => null,
                'photo' => null,
                'preferred_branch_id' => null,
                'marketing_consent' => false,
                'whatsapp_consent' => false,
                'push_consent' => false,
            ])->save();

            $user->delete(); // soft delete
        });

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('success', 'Your account has been deleted.');
    }
}
