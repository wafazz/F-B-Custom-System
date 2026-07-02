<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\MenuDisplay;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class MenuDisplayController extends Controller
{
    public function show(string $token): Response
    {
        $display = MenuDisplay::query()
            ->active()
            ->where('token', $token)
            ->with(['branch:id,code,name,logo', 'categories'])
            ->firstOrFail();

        $display->forceFill(['last_seen_at' => now()])->save();

        $slides = $display->categories->map(function ($category) use ($display) {
            $items = Product::query()
                ->where('status', 'active')
                ->where('available_web', true)
                ->where('category_id', $category->id)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name', 'description', 'base_price', 'image', 'badge_label'])
                ->map(fn (Product $p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'description' => $p->description,
                    'price' => $display->show_price ? number_format((float) $p->base_price, 2) : null,
                    'image' => $p->image,
                    'badge' => $p->badge_label,
                ])
                ->values();

            return [
                'id' => $category->id,
                'title' => $category->name,
                'image' => $category->image,
                'items' => $items,
            ];
        })->filter(fn ($slide) => $slide['items']->isNotEmpty())->values();

        return Inertia::render('display/menu', [
            'display' => [
                'name' => $display->name,
                'heading' => $display->heading,
                'layout' => $display->layout,
                'seconds' => $display->seconds_per_slide,
                'showPrice' => $display->show_price,
            ],
            'branch' => $display->branch ? [
                'name' => $display->branch->name,
                'logo' => $display->branch->logo,
            ] : null,
            'token' => $token,
            'slides' => $slides,
            'posters' => collect($display->posters ?? [])->values(),
            'videos' => collect($display->videos ?? [])
                ->map(fn ($row) => is_array($row) ? ($row['url'] ?? '') : (string) $row)
                ->map(fn ($url) => $this->youtubeId((string) $url))
                ->filter()
                ->values(),
        ]);
    }

    public function heartbeat(string $token): JsonResponse
    {
        $display = MenuDisplay::query()
            ->active()
            ->where('token', $token)
            ->first();
        abort_if($display === null, 403);

        $display->forceFill(['last_seen_at' => now()])->save();

        return response()->json(['ok' => true, 'at' => now()->toIso8601String()]);
    }

    private function youtubeId(string $url): ?string
    {
        $url = trim($url);
        if ($url === '') {
            return null;
        }

        if (preg_match('~(?:youtu\.be/|youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/|v/))([\w-]{11})~', $url, $m)) {
            return $m[1];
        }

        if (preg_match('~^[\w-]{11}$~', $url)) {
            return $url;
        }

        return null;
    }
}
