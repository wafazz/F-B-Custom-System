<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Product;
use App\Services\Settings\SettingsRepository;

abstract class Controller
{
    /**
     * HQ-configured upsell products offered before checkout.
     *
     * @return array{enabled: bool, title: string, products: array<int, array<string, mixed>>}
     */
    protected function upsellPayload(Branch $branch, SettingsRepository $settings): array
    {
        $enabled = $settings->get('upsell.enabled', '0') === '1';
        $title = $settings->get('upsell.title', 'Add something extra?');

        $products = [];
        if ($enabled) {
            $ids = json_decode($settings->get('upsell.product_ids', '[]') ?? '[]', true);
            if (is_array($ids) && count($ids) > 0) {
                $products = Product::active()
                    ->availableAtBranch($branch->id)
                    ->whereIn('id', $ids)
                    ->get(['id', 'name', 'image', 'base_price', 'tumbler_discount'])
                    ->map(fn (Product $p) => [
                        'id' => $p->id,
                        'name' => $p->name,
                        'image' => $p->image,
                        'price' => (float) $p->priceForBranch($branch->id),
                        'tumbler_discount' => (float) $p->tumbler_discount,
                    ])
                    ->values()
                    ->all();
            }
        }

        return [
            'enabled' => $enabled,
            'title' => $title,
            'products' => $products,
        ];
    }
}
