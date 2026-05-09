<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WalletTopup;
use App\Models\WalletTransaction;
use App\Services\Payments\BillplzGateway;
use App\Services\Payments\PaymentGateway;
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

    public function topup(Request $request, PaymentGateway $gateway): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:5', 'max:1000'],
        ]);

        if (! $gateway instanceof BillplzGateway) {
            return back()->withErrors(['amount' => 'Top-up requires the Billplz gateway. Switch driver in admin settings.']);
        }

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

        return redirect()->away($bill->url);
    }
}
