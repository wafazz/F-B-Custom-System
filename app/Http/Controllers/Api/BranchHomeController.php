<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Category;
use App\Models\HomeSlide;
use App\Models\Product;
use App\Support\RequestChannel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BranchHomeController extends Controller
{
    /**
     * Public branch-home content — managed slides, categories and featured
     * products. Mirrors the Inertia storefront branch-home so native apps
     * render the same merchandising. Per-customer stats live on /loyalty + /wallet.
     */
    public function __invoke(Request $request, Branch $branch): JsonResponse
    {
        $channel = RequestChannel::detect($request);
        $channelColumn = RequestChannel::availableColumn($channel);

        $categories = Category::active()
            ->visibleOn($channel)
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
            ->where($channelColumn, true)
            ->limit(6)
            ->get(['id', 'name', 'slug', 'image', 'base_price', 'description'])
            ->map(fn (Product $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'slug' => $p->slug,
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
            ->get()
            ->groupBy('placement');

        $hero = [];
        foreach (($managed['hero'] ?? collect()) as $row) {
            $hero[] = [
                'type' => 'managed',
                'image' => $row->image,
                'title' => $row->title,
                'subtitle' => $row->subtitle,
                'cta_label' => $row->cta_label,
                'cta_url' => $row->cta_url,
            ];
        }

        $rewards = [];
        foreach (($managed['rewards'] ?? collect()) as $row) {
            $rewards[] = [
                'type' => 'managed',
                'image' => $row->image,
                'title' => $row->title,
                'subtitle' => $row->subtitle,
                'cta_label' => $row->cta_label,
                'cta_url' => $row->cta_url,
            ];
        }

        $popup = [];
        foreach (($managed['popup'] ?? collect()) as $row) {
            $popup[] = [
                'type' => 'managed',
                'image' => $row->image,
                'title' => $row->title,
                'subtitle' => $row->subtitle,
                'cta_label' => $row->cta_label,
                'cta_url' => $row->cta_url,
            ];
        }

        return response()->json([
            'branch' => [
                'id' => $branch->id,
                'code' => $branch->code,
                'name' => $branch->name,
                'logo' => $branch->logo,
                'cover_image' => $branch->cover_image,
                'is_open_now' => $branch->isOpenNow(),
                'accepts_orders' => (bool) $branch->accepts_orders,
                'avg_rating' => (float) $branch->avg_rating,
                'reviews_count' => (int) $branch->reviews_count,
            ],
            'slides' => $hero,
            'rewards_slides' => $rewards,
            'popup_slides' => $popup,
            'categories' => $categories,
            'featured' => $featured,
        ]);
    }
}
