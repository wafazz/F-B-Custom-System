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
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;
use Throwable;

class WalletController extends Controller
{
    public function show(Request $request, WalletService $wallet, BillplzGateway $gateway): Response
    {
        /** @var User $user */
        $user = $request->user();

        // Self-heal: reconcile pending top-ups against Billplz on every page
        // load so the wallet recovers even if the webhook never arrived or
        // failed signature verification (wrong X-Signature key, etc.).
        $this->reconcilePendingTopups($user, $gateway, $wallet);

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
        } elseif ($topup->status === 'pending' && $topup->billplz_reference) {
            // Redirect didn't carry a valid signature (e.g. X-Signature key
            // misconfigured). Fall back to a direct bill lookup so the user
            // doesn't get stuck with a paid-but-unreflected top-up.
            $this->reconcilePendingTopups($user, $gateway, $wallet);
        }

        return redirect()->route('wallet');
    }

    /**
     * Re-query Billplz for any pending top-ups belonging to the user and
     * apply paid/cancelled status. Idempotent — `applyTopupPaid` no-ops if
     * already paid. Failures are logged but never thrown so the wallet page
     * still renders if Billplz is unreachable.
     */
    protected function reconcilePendingTopups(User $user, BillplzGateway $gateway, WalletService $wallet): void
    {
        $pending = WalletTopup::query()
            ->where('user_id', $user->getKey())
            ->where('status', 'pending')
            ->whereNotNull('billplz_reference')
            ->get();

        foreach ($pending as $topup) {
            try {
                $bill = $gateway->fetchBill((string) $topup->billplz_reference);
                $paid = (bool) ($bill['paid'] ?? false);
                $state = (string) ($bill['state'] ?? '');

                if ($paid) {
                    $wallet->applyTopupPaid($topup);
                } elseif ($state === 'deleted') {
                    $topup->forceFill(['status' => 'cancelled'])->save();
                }
            } catch (Throwable $e) {
                Log::warning('Wallet top-up reconcile failed', [
                    'topup_id' => $topup->id,
                    'reference' => $topup->billplz_reference,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
