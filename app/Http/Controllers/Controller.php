<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Product;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherClaim;
use App\Services\Settings\SettingsRepository;

abstract class Controller
{
    /**
     * HQ-configured upsell products and claimable vouchers offered before checkout.
     *
     * @return array{enabled: bool, title: string, products: array<int, array<string, mixed>>, vouchers: array<int, array<string, mixed>>}
     */
    protected function upsellPayload(Branch $branch, SettingsRepository $settings): array
    {
        $enabled = $settings->get('upsell.enabled', '0') === '1';
        $title = $settings->get('upsell.title', 'Add something extra?');

        $products = [];
        if ($enabled) {
            $ids = json_decode($settings->get('upsell.product_ids', '[]') ?? '[]', true);
            $prices = json_decode($settings->get('upsell.prices', '{}') ?? '{}', true);
            $prices = is_array($prices) ? $prices : [];
            if (is_array($ids) && count($ids) > 0) {
                $products = Product::active()
                    ->availableAtBranch($branch->id)
                    ->whereIn('id', $ids)
                    ->get(['id', 'name', 'image', 'base_price', 'tumbler_discount'])
                    ->map(function (Product $p) use ($branch, $prices) {
                        $normal = (float) $p->priceForBranch($branch->id);
                        $upsell = $prices[(string) $p->id] ?? null;

                        return [
                            'id' => $p->id,
                            'name' => $p->name,
                            'image' => $p->image,
                            'price' => $upsell !== null ? (float) $upsell : $normal,
                            'normal_price' => $normal,
                            'tumbler_discount' => (float) $p->tumbler_discount,
                        ];
                    })
                    ->values()
                    ->all();
            }
        }

        return [
            'enabled' => $enabled,
            'title' => $title,
            'products' => $products,
            'vouchers' => $enabled ? $this->upsellVouchers($branch, $settings) : [],
        ];
    }

    /**
     * HQ-picked vouchers the signed-in customer can still claim at this branch.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function upsellVouchers(Branch $branch, SettingsRepository $settings): array
    {
        $user = auth()->user();
        if (! $user instanceof User) {
            return [];
        }

        $ids = json_decode($settings->get('upsell.voucher_ids', '[]') ?? '[]', true);
        if (! is_array($ids) || count($ids) === 0) {
            return [];
        }

        $claimedIds = VoucherClaim::query()
            ->where('user_id', $user->getKey())
            ->whereIn('voucher_id', $ids)
            ->pluck('voucher_id')
            ->all();

        return Voucher::active()
            ->whereIn('id', $ids)
            ->whereNotIn('id', $claimedIds)
            ->orderBy('valid_until')
            ->get()
            ->filter(function (Voucher $v) use ($branch, $user) {
                if ($v->max_uses !== null && $v->used_count >= $v->max_uses) {
                    return false;
                }
                $scope = $v->branch_ids;
                if (is_array($scope) && count($scope) > 0
                    && ! in_array($branch->id, array_map('intval', $scope), true)) {
                    return false;
                }

                return $v->isEligibleFor($user);
            })
            ->map(fn (Voucher $v) => [
                'id' => $v->id,
                'code' => $v->code,
                'name' => $v->name,
                'discount_type' => $v->discount_type,
                'discount_value' => (float) $v->discount_value,
                'min_subtotal' => (float) $v->min_subtotal,
                'max_discount' => $v->max_discount !== null ? (float) $v->max_discount : null,
                'points_cost' => $v->points_cost,
                'valid_until' => $v->valid_until?->toIso8601String(),
            ])
            ->values()
            ->all();
    }
}
