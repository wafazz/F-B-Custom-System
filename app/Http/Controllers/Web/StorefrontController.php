<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Category;
use App\Models\HomeSlide;
use App\Models\Product;
use Inertia\Inertia;
use Inertia\Response;

class StorefrontController extends Controller
{
    public function splash(): Response
    {
        return Inertia::render('storefront/splash', [
            'hasBranches' => Branch::active()->exists(),
        ]);
    }

    public function selectBranch(): Response
    {
        $branches = Branch::active()
            ->orderBy('sort_order')
            ->get(['id', 'code', 'name', 'address', 'city', 'state', 'phone', 'latitude', 'longitude', 'operating_hours', 'logo'])
            ->map(fn (Branch $b) => [
                'id' => $b->id,
                'code' => $b->code,
                'name' => $b->name,
                'address' => $b->address,
                'city' => $b->city,
                'state' => $b->state,
                'phone' => $b->phone,
                'latitude' => $b->latitude !== null ? (float) $b->latitude : null,
                'longitude' => $b->longitude !== null ? (float) $b->longitude : null,
                'operating_hours' => $b->operating_hours,
                'logo' => $b->logo,
                'is_open_now' => $b->isOpenNow(),
            ])
            ->values();

        return Inertia::render('storefront/branch-select', [
            'branches' => $branches,
            'server_time' => [
                'now' => now()->toDateTimeString(),
                'timezone' => config('app.timezone'),
                'php_tz' => date_default_timezone_get(),
                'iso' => now()->toIso8601String(),
            ],
        ]);
    }

    public function branchHome(Branch $branch): Response
    {
        $categories = Category::active()
            ->root()
            ->orderBy('sort_order')
            ->get(['id', 'slug', 'name', 'image', 'icon'])
            ->map(fn (Category $c) => [
                'id' => $c->id,
                'slug' => $c->slug,
                'name' => $c->name,
                'image' => $c->image,
                'icon' => $c->icon,
            ])
            ->values();

        $featured = Product::active()
            ->featured()
            ->availableAtBranch($branch->id)
            ->limit(3)
            ->get(['id', 'name', 'slug', 'image', 'base_price', 'description'])
            ->map(fn (Product $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'image' => $p->image,
                'price' => (float) $p->priceForBranch($branch->id),
                'description' => $p->description,
            ])
            ->values();

        $managed = HomeSlide::query()
            ->active()
            ->forBranch($branch->id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $slides = [];
        foreach ($managed as $row) {
            $slides[] = [
                'type' => 'managed',
                'image' => $row->image,
                'title' => $row->title,
                'subtitle' => $row->subtitle,
                'cta_label' => $row->cta_label,
                'cta_url' => $row->cta_url,
            ];
        }

        if (count($slides) === 0) {
            if ($branch->cover_image) {
                $slides[] = [
                    'type' => 'cover',
                    'image' => $branch->cover_image,
                    'title' => "Welcome to {$branch->name}",
                    'subtitle' => 'Freshly brewed, made just for you.',
                    'cta_label' => null,
                    'cta_url' => null,
                ];
            }
            foreach ($featured as $p) {
                $slides[] = [
                    'type' => 'product',
                    'image' => $p['image'],
                    'title' => $p['name'],
                    'subtitle' => 'RM '.number_format($p['price'], 2),
                    'cta_label' => null,
                    'cta_url' => null,
                ];
            }
            if (count($slides) === 0) {
                $slides[] = [
                    'type' => 'cover',
                    'image' => null,
                    'title' => $branch->name,
                    'subtitle' => 'Order ahead, skip the queue.',
                    'cta_label' => null,
                    'cta_url' => null,
                ];
            }
        }

        return Inertia::render('storefront/branch-home', [
            'branch' => [
                'id' => $branch->id,
                'code' => $branch->code,
                'name' => $branch->name,
                'logo' => $branch->logo,
                'is_open_now' => $branch->isOpenNow(),
                'accepts_orders' => $branch->accepts_orders,
            ],
            'slides' => $slides,
            'categories' => $categories,
        ]);
    }

    public function menu(Branch $branch): Response
    {
        return Inertia::render('storefront/menu', [
            'branch' => [
                'id' => $branch->id,
                'code' => $branch->code,
                'name' => $branch->name,
                'logo' => $branch->logo,
                'cover_image' => $branch->cover_image,
                'sst_rate' => (float) $branch->sst_rate,
                'sst_enabled' => (bool) $branch->sst_enabled,
                'service_charge_rate' => (float) $branch->service_charge_rate,
                'service_charge_enabled' => (bool) $branch->service_charge_enabled,
                'status' => $branch->status,
                'accepts_orders' => $branch->accepts_orders,
                'is_open_now' => $branch->isOpenNow(),
            ],
            'reverb' => [
                'channel' => "branch.{$branch->id}.stock",
                'event' => 'stock.changed',
            ],
        ]);
    }
}
