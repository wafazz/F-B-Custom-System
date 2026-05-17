<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Category;
use App\Models\HomeSlide;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class StorefrontController extends Controller
{
    public function splash(): Response|RedirectResponse
    {
        // Returning customers skip the splash and go straight to picking a branch.
        if (Auth::check()) {
            return redirect()->route('branches.select');
        }

        return Inertia::render('storefront/splash', [
            'hasBranches' => Branch::active()->exists(),
        ]);
    }

    public function selectBranch(): Response
    {
        $branches = Branch::active()
            ->orderBy('sort_order')
            // status + accepts_orders are required by Branch::isOpenNow(); without
            // them the method short-circuits to false and every branch reads "Closed".
            ->get(['id', 'code', 'name', 'address', 'city', 'state', 'phone', 'latitude', 'longitude', 'operating_hours', 'logo', 'cover_image', 'status', 'accepts_orders'])
            ->map(function (Branch $b) {
                $day = strtolower(now()->englishDayOfWeek);
                $hours = is_array($b->operating_hours) ? ($b->operating_hours[$day] ?? null) : null;

                return [
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
                    'cover_image' => $b->cover_image,
                    'is_open_now' => $b->isOpenNow(),
                    'closed_reason' => $b->closedReason(),
                    'debug_status' => $b->status,
                    'debug_accepts_orders' => (bool) $b->accepts_orders,
                    'debug_today' => $day,
                    'debug_today_hours' => $hours,
                ];
            })
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

    public function branchHome(\Illuminate\Http\Request $request, Branch $branch): Response
    {
        $channel = \App\Support\RequestChannel::detect($request);
        $channelColumn = \App\Support\RequestChannel::availableColumn($channel);

        $categories = Category::active()
            ->visibleOn($channel)
            ->root()
            ->orderBy('sort_order')
            ->with([
                'children' => fn ($q) => $q->where('status', 'active')
                    ->orderBy('sort_order'),
            ])
            ->get(['id', 'slug', 'name', 'image', 'icon'])
            ->map(function (Category $c) {
                // Prefer the category's own admin-uploaded image. When the
                // parent has none, borrow the first child's image so the
                // circle never falls back to the generic Coffee icon
                // unnecessarily.
                $image = $c->image;
                if (! $image) {
                    foreach ($c->children as $child) {
                        if ($child->image) {
                            $image = $child->image;
                            break;
                        }
                    }
                }

                return [
                    'id' => $c->id,
                    'slug' => $c->slug,
                    'name' => $c->name,
                    'image' => $image,
                    'icon' => $c->icon,
                ];
            })
            ->values();

        $featured = Product::active()
            ->featured()
            ->availableAtBranch($branch->id)
            ->where($channelColumn, true)
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

        if (count($hero) === 0) {
            if ($branch->cover_image) {
                $hero[] = [
                    'type' => 'cover',
                    'image' => $branch->cover_image,
                    'title' => "Welcome to {$branch->name}",
                    'subtitle' => 'Freshly brewed, made just for you.',
                    'cta_label' => null,
                    'cta_url' => null,
                ];
            }
            foreach ($featured as $p) {
                $hero[] = [
                    'type' => 'product',
                    'image' => $p['image'],
                    'title' => $p['name'],
                    'subtitle' => 'RM '.number_format($p['price'], 2),
                    'cta_label' => null,
                    'cta_url' => null,
                ];
            }
            if (count($hero) === 0) {
                $hero[] = [
                    'type' => 'cover',
                    'image' => null,
                    'title' => $branch->name,
                    'subtitle' => 'Order ahead, skip the queue.',
                    'cta_label' => null,
                    'cta_url' => null,
                ];
            }
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

        if (count($rewards) === 0) {
            $rewards[] = [
                'type' => 'cover',
                'image' => null,
                'title' => 'Brew More, Earn More',
                'subtitle' => 'Collect points and enjoy exclusive rewards.',
                'cta_label' => 'View rewards',
                'cta_url' => '/loyalty',
            ];
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
            'slides' => $hero,
            'rewards_slides' => $rewards,
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
