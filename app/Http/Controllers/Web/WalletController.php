<?php

namespace App\Http\Controllers\Web;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WalletTopup;
use App\Models\WalletTransaction;
use App\Services\Payments\BillplzGateway;
use App\Services\Wallet\WalletService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class WalletController extends Controller
{
    public function show(Request $request, WalletService $wallet): Response
    {
        /** @var User $user */
        $user = $request->user();

        $history = WalletTransaction::query()
            ->where('user_id', $user->getKey())
            ->latest('id')
            ->limit(50)
            ->get();

        $rows = [];
        foreach ($history as $row) {
            $rows[] = [
                'id' => $row->id,
                'type' => $row->type,
                'amount' => (float) $row->amount,
                'balance_after' => (float) $row->balance_after,
                'description' => $row->description,
                'created_at' => $row->created_at?->toIso8601String(),
            ];
        }

        return Inertia::render('storefront/wallet', [
            'balance' => $wallet->balance($user->getKey()),
            'history' => $rows,
            'topup_amounts' => [10, 20, 50, 100, 200],
            'pending_topups' => WalletTopup::query()
                ->where('user_id', $user->getKey())
                ->where('status', 'pending')
                ->count(),
        ]);
    }

    public function topup(Request $request, BillplzGateway $gateway): \Symfony\Component\HttpFoundation\Response
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:5', 'max:1000'],
        ]);

        /** @var User $user */
        $user = $request->user();

        $topup = WalletTopup::create([
            'user_id' => $user->getKey(),
            'amount' => $data['amount'],
            'status' => 'pending',
        ]);

        try {
            $bill = $gateway->createTopupBill($topup, $user);
        } catch (RuntimeException $e) {
            $topup->update(['status' => 'failed']);

            return back()->withErrors(['amount' => $e->getMessage()]);
        }

        $topup->update(['billplz_reference' => $bill->reference]);

        // Inertia intercepts `redirect()->away()` and never navigates to the
        // external URL. Inertia::location() returns a 409 with X-Inertia-Location
        // so the client triggers a real browser navigation to the Billplz page.
        return Inertia::location($bill->url);
    }

    /**
     * Billplz redirects the customer's browser here after payment. The signed
     * query params let us reflect paid status immediately, without waiting
     * for the server-to-server webhook to land (useful for local dev where
     * Billplz can't reach the callback URL).
     */
    public function topupReturn(Request $request, WalletTopup $topup, BillplzGateway $gateway, WalletService $wallet): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ((int) $topup->user_id !== (int) $user->getKey()) {
            abort(403);
        }

        $payload = $request->query();
        $update = $gateway->verifyWebhook($payload, (string) $request->query('x_signature'));

        if ($update !== null && $update->reference === $topup->billplz_reference) {
            if ($update->status === PaymentStatus::Paid && $topup->status === 'pending') {
                $wallet->applyTopupPaid($topup);
            } elseif ($update->status === PaymentStatus::Failed && $topup->status === 'pending') {
                $topup->forceFill(['status' => 'failed'])->save();
            }
        }

        return redirect()->route('wallet');
    }
}
