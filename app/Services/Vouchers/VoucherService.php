<?php

namespace App\Services\Vouchers;

use App\Models\CustomerTier;
use App\Models\Order;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherClaim;
use App\Models\VoucherRedemption;
use App\Notifications\VoucherAvailableNotification;
use App\Services\Loyalty\LoyaltyService;
use App\Services\Push\PushService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use RuntimeException;

class VoucherService
{
    public function __construct(protected LoyaltyService $loyalty)
    {
    }

    /** Resolve and validate a voucher; throws if invalid. */
    public function find(string $code, int $branchId, ?int $userId = null): Voucher
    {
        $voucher = Voucher::active()->where('code', strtoupper($code))->first();
        if (! $voucher) {
            throw new RuntimeException('Voucher not found or expired.');
        }
        if ($voucher->max_uses !== null && $voucher->used_count >= $voucher->max_uses) {
            throw new RuntimeException('Voucher has reached its usage cap.');
        }
        $branchScope = $voucher->branch_ids;
        if (is_array($branchScope) && count($branchScope) > 0 && ! in_array($branchId, $branchScope, true)) {
            throw new RuntimeException('Voucher is not valid for this branch.');
        }
        if ($userId !== null) {
            $userUses = VoucherRedemption::query()
                ->where('voucher_id', $voucher->id)
                ->where('user_id', $userId)
                ->count();
            if ($userUses >= $voucher->max_uses_per_user) {
                throw new RuntimeException('You have already used this voucher.');
            }
        }

        return $voucher;
    }

    /**
     * Compute the discount amount this voucher gives against a cart.
     *
     * @param  list<array{product_id: int|null, combo_id: int|null, line_total: float, quantity?: int, unit_price?: float}>|null  $items
     *         When set + voucher has product_ids or combo_ids, the discount
     *         applies only to the subtotal of matching lines. quantity +
     *         unit_price are required for buy_x_get_y vouchers.
     */
    public function discountFor(Voucher $voucher, float $subtotal, ?array $items = null): float
    {
        if ($subtotal < (float) $voucher->min_subtotal) {
            throw new RuntimeException(sprintf('Minimum subtotal RM%.2f required.', (float) $voucher->min_subtotal));
        }

        if ($voucher->discount_type === 'buy_x_get_y') {
            return $this->bxgyDiscount($voucher, $subtotal, $items);
        }

        $eligibleSubtotal = $subtotal;
        // Filament multi-selects serialise picked ids as strings; normalise.
        $productScope = array_map(static fn ($v): int => (int) $v, $voucher->product_ids ?? []);
        $comboScope = array_map(static fn ($v): int => (int) $v, $voucher->combo_ids ?? []);
        $hasScope = ! empty($productScope) || ! empty($comboScope);

        if ($hasScope) {
            if ($items === null) {
                throw new RuntimeException('This voucher only applies to specific items.');
            }
            $eligibleSubtotal = 0.0;
            foreach ($items as $row) {
                $productHit = ! empty($productScope)
                    && $row['product_id'] !== null
                    && in_array((int) $row['product_id'], $productScope, true);
                $comboHit = ! empty($comboScope)
                    && $row['combo_id'] !== null
                    && in_array((int) $row['combo_id'], $comboScope, true);
                if ($productHit || $comboHit) {
                    $eligibleSubtotal += (float) $row['line_total'];
                }
            }
            if ($eligibleSubtotal <= 0) {
                throw new RuntimeException('This voucher needs at least one of its eligible items in your cart.');
            }
        }

        $raw = $voucher->discount_type === 'percentage'
            ? $eligibleSubtotal * ((float) $voucher->discount_value / 100)
            : (float) $voucher->discount_value;

        if ($voucher->max_discount !== null) {
            $raw = min($raw, (float) $voucher->max_discount);
        }

        // Cap by eligible subtotal so a fixed-amount voucher can't exceed it,
        // and by the overall subtotal so it can't exceed the order total.
        return min(round($raw, 2), $eligibleSubtotal, $subtotal);
    }

    /**
     * Buy N Free M: discount equals the unit_price sum of the M cheapest
     * items inside the free pool (one unit per slot). Standard cafe rule
     * — customers can't game it by gifting their most expensive line.
     *
     * @param  list<array{product_id: int|null, combo_id: int|null, line_total: float, quantity?: int, unit_price?: float}>|null  $items
     */
    private function bxgyDiscount(Voucher $voucher, float $subtotal, ?array $items): float
    {
        $buyQty = (int) ($voucher->bxgy_buy_qty ?? 0);
        $freeQty = (int) ($voucher->bxgy_free_qty ?? 0);
        if ($buyQty <= 0 || $freeQty <= 0 || $items === null) {
            throw new RuntimeException('This voucher is not configured correctly.');
        }

        $productScope = array_map(static fn ($v): int => (int) $v, $voucher->product_ids ?? []);
        $comboScope = array_map(static fn ($v): int => (int) $v, $voucher->combo_ids ?? []);
        $hasQualifyingScope = ! empty($productScope) || ! empty($comboScope);

        $isQualifying = function (array $row) use ($productScope, $comboScope, $hasQualifyingScope): bool {
            if (! $hasQualifyingScope) {
                return true; // No scope set → every cart line qualifies.
            }
            $productHit = ! empty($productScope)
                && $row['product_id'] !== null
                && in_array((int) $row['product_id'], $productScope, true);
            $comboHit = ! empty($comboScope)
                && $row['combo_id'] !== null
                && in_array((int) $row['combo_id'], $comboScope, true);
            return $productHit || $comboHit;
        };

        $qualifyingQty = 0;
        foreach ($items as $row) {
            if ($isQualifying($row)) {
                $qualifyingQty += (int) ($row['quantity'] ?? 0);
            }
        }

        $groups = intdiv($qualifyingQty, $buyQty);
        if ($groups <= 0) {
            throw new RuntimeException(
                sprintf('Add %d more qualifying item(s) to unlock this voucher.', $buyQty - $qualifyingQty),
            );
        }
        $freeCount = $groups * $freeQty;

        // Build free pool:
        //   bxgy_free_product_ids null AND bxgy_free_combo_ids null
        //     → free pool = qualifying lines (same scope)
        //   bxgy_free_product_ids = [] AND bxgy_free_combo_ids = []
        //     → free pool = every cart line (any item)
        //   otherwise → use the bxgy_free_* lists
        $freeProductScope = $voucher->bxgy_free_product_ids;
        $freeComboScope = $voucher->bxgy_free_combo_ids;
        $freePoolMode = match (true) {
            $freeProductScope === null && $freeComboScope === null => 'same',
            (is_array($freeProductScope) && $freeProductScope === [])
                && (is_array($freeComboScope) && $freeComboScope === []) => 'any',
            default => 'cross',
        };

        $crossProducts = array_map(static fn ($v): int => (int) $v, $freeProductScope ?? []);
        $crossCombos = array_map(static fn ($v): int => (int) $v, $freeComboScope ?? []);
        $inFreePool = function (array $row) use ($freePoolMode, $isQualifying, $crossProducts, $crossCombos): bool {
            return match ($freePoolMode) {
                'any' => true,
                'same' => $isQualifying($row),
                'cross' => (! empty($crossProducts)
                        && $row['product_id'] !== null
                        && in_array((int) $row['product_id'], $crossProducts, true))
                    || (! empty($crossCombos)
                        && $row['combo_id'] !== null
                        && in_array((int) $row['combo_id'], $crossCombos, true)),
            };
        };

        // Expand each pool line to one entry per unit so we can rank by unit
        // price across qty boundaries.
        $units = [];
        foreach ($items as $row) {
            if (! $inFreePool($row)) {
                continue;
            }
            $qty = (int) ($row['quantity'] ?? 0);
            $unitPrice = (float) ($row['unit_price'] ?? 0);
            for ($i = 0; $i < $qty; $i++) {
                $units[] = $unitPrice;
            }
        }
        if (empty($units)) {
            throw new RuntimeException('No items in your cart qualify for the free reward.');
        }
        sort($units);

        $take = min($freeCount, count($units));
        $discount = 0.0;
        for ($i = 0; $i < $take; $i++) {
            $discount += $units[$i];
        }

        return min(round($discount, 2), $subtotal);
    }

    public function commit(Voucher $voucher, Order $order, float $discount): VoucherRedemption
    {
        return DB::transaction(function () use ($voucher, $order, $discount) {
            $voucher->increment('used_count');

            $redemption = VoucherRedemption::create([
                'voucher_id' => $voucher->id,
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'discount_amount' => $discount,
            ]);

            if ($order->user_id !== null) {
                VoucherClaim::query()
                    ->where('voucher_id', $voucher->id)
                    ->where('user_id', $order->user_id)
                    ->whereNull('used_at')
                    ->update([
                        'used_at' => now(),
                        'order_id' => $order->id,
                    ]);
            }

            return $redemption;
        });
    }

    /** Claim a voucher for a user. Idempotent — re-claiming returns the existing row. */
    public function claim(Voucher $voucher, int $userId): VoucherClaim
    {
        $now = now();
        if ($voucher->status !== 'active') {
            throw new RuntimeException('Voucher is not active.');
        }
        if ($voucher->valid_from !== null && $voucher->valid_from->greaterThan($now)) {
            throw new RuntimeException('Voucher is not yet available.');
        }
        if ($voucher->valid_until !== null && $voucher->valid_until->lessThan($now)) {
            throw new RuntimeException('Voucher has expired.');
        }
        if ($voucher->max_uses !== null && $voucher->used_count >= $voucher->max_uses) {
            throw new RuntimeException('Voucher has reached its usage cap.');
        }

        $user = User::query()->find($userId);
        if ($user === null || ! $voucher->isEligibleFor($user)) {
            throw new RuntimeException('This voucher is not available for your account.');
        }

        // Points-cost vouchers behave like a rewards catalogue: customer
        // pays in loyalty points instead of getting it for free.
        if ($voucher->points_cost !== null && $voucher->points_cost > 0) {
            $balance = $this->loyalty->balance($userId);
            if ($balance < $voucher->points_cost) {
                throw new RuntimeException("You need {$voucher->points_cost} pts to redeem this reward. (Current balance: {$balance})");
            }
        }

        return DB::transaction(function () use ($voucher, $userId) {
            $existing = VoucherClaim::query()
                ->where('voucher_id', $voucher->id)
                ->where('user_id', $userId)
                ->first();

            if ($existing instanceof VoucherClaim) {
                if ($existing->used_at !== null) {
                    throw new RuntimeException('You have already used this voucher.');
                }

                return $existing;
            }

            $userUses = VoucherRedemption::query()
                ->where('voucher_id', $voucher->id)
                ->where('user_id', $userId)
                ->count();
            if ($userUses >= $voucher->max_uses_per_user) {
                throw new RuntimeException('You have already used this voucher.');
            }

            // Burn the loyalty points for a rewards-catalogue voucher. We
            // re-check the balance inside the transaction so two simultaneous
            // taps can't drain the same balance twice.
            if ($voucher->points_cost !== null && $voucher->points_cost > 0) {
                $balanceNow = $this->loyalty->balance($userId);
                if ($balanceNow < $voucher->points_cost) {
                    throw new RuntimeException('Insufficient points to redeem this reward.');
                }
                $this->loyalty->redeem(
                    $userId,
                    (int) $voucher->points_cost,
                    null,
                    "redeem reward {$voucher->code}",
                );
            }

            return VoucherClaim::create([
                'voucher_id' => $voucher->id,
                'user_id' => $userId,
                'claimed_at' => now(),
            ]);
        });
    }

    /**
     * Auto-claim every active voucher flagged "new_users_only" that this
     * user qualifies for. Fired from RegisterController so welcome offers
     * land in /vouchers immediately. Returns the number issued.
     */
    public function autoIssueWelcomeVouchers(User $user): int
    {
        $candidates = Voucher::active()
            ->where('new_users_only', true)
            ->get()
            ->filter(fn (Voucher $v) => $v->isEligibleFor($user));

        if ($candidates->isEmpty()) {
            return 0;
        }

        $issued = 0;
        foreach ($candidates as $voucher) {
            try {
                $this->claim($voucher, (int) $user->getKey());
                $issued++;
                $user->notify(new VoucherAvailableNotification($voucher));
            } catch (\Throwable) {
                // Per-user / per-voucher max_uses hits — silently skip so a
                // single misconfigured voucher doesn't abort the signup.
                continue;
            }
        }

        return $issued;
    }

    /**
     * Broadcast a "new voucher" announcement to every eligible customer:
     * inbox row via VoucherAvailableNotification, plus a Web Push for
     * anyone who's subscribed. Customers with admin roles are skipped so
     * staff inboxes don't fill up with marketing.
     */
    public function notifyEligibleMembers(Voucher $voucher, PushService $push): int
    {
        $query = User::query()
            ->whereNull('deleted_at')
            ->whereDoesntHave('roles', fn ($q) => $q->whereIn('name', [
                'super_admin', 'hq_admin', 'ops_manager', 'mkt_manager',
                'branch_manager', 'cashier', 'barista',
            ]));

        if (! empty($voucher->tier_ids)) {
            $tierUserIds = CustomerTier::query()
                ->whereIn('membership_tier_id', $voucher->tier_ids)
                ->pluck('user_id');
            $query->whereIn('id', $tierUserIds);
        }

        if (! empty($voucher->birthday_months)) {
            $query->whereNotNull('date_of_birth')
                ->whereIn(DB::raw('MONTH(date_of_birth)'), $voucher->birthday_months);
        }

        if ($voucher->new_users_only) {
            $usersWithOrders = Order::query()
                ->whereNotNull('user_id')
                ->distinct()
                ->pluck('user_id');
            $query->whereNotIn('id', $usersWithOrders);
        }

        $users = $query->get();
        if ($users->isEmpty()) {
            return 0;
        }

        // Inbox notification — one DB insert per user, idempotent within
        // Laravel's broker.
        Notification::send($users, new VoucherAvailableNotification($voucher));

        // Web Push — best-effort, dead endpoints get pruned by PushService.
        if ($push->isConfigured()) {
            $discount = $voucher->discount_type === 'percentage'
                ? number_format((float) $voucher->discount_value, 0).'% off'
                : 'RM'.number_format((float) $voucher->discount_value, 2).' off';
            foreach ($users as $u) {
                $push->sendToUser((int) $u->getKey(), [
                    'title' => 'New voucher: '.$voucher->name,
                    'body' => "{$discount} — tap to claim.",
                    'url' => '/vouchers',
                    'tag' => "voucher-{$voucher->id}",
                ]);
            }
        }

        return $users->count();
    }
}
