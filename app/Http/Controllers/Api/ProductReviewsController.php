<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductReview;
use Illuminate\Http\JsonResponse;

class ProductReviewsController extends Controller
{
    /**
     * Public product reviews — mirrors the Inertia storefront product-reviews
     * JSON so the native modifier sheet can render the same slider.
     */
    public function __invoke(Product $product): JsonResponse
    {
        $reviews = ProductReview::query()
            ->with('user:id,name')
            ->where('product_id', $product->id)
            ->where('is_hidden', false)
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn (ProductReview $r) => [
                'id' => $r->id,
                'rating' => $r->rating,
                'comment' => $r->comment,
                'user_name' => $r->user->name,
                'created_at' => $r->created_at?->toIso8601String(),
            ])
            ->values();

        return response()->json([
            'avg_rating' => (float) $product->avg_rating,
            'reviews_count' => (int) $product->reviews_count,
            'reviews' => $reviews,
        ]);
    }
}
