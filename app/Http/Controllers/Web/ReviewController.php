<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\BranchReview;
use App\Models\Product;
use App\Models\ProductReview;
use App\Services\Reviews\ReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class ReviewController extends Controller
{
    public function storeProductReview(
        Product $product,
        Request $request,
        ReviewService $service,
    ): JsonResponse {
        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $review = $service->submitProductReview(
                (int) $request->user()->getKey(),
                (int) $product->id,
                (int) $data['rating'],
                $data['comment'] ?? null,
            );
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'id' => $review->id,
            'rating' => $review->rating,
            'comment' => $review->comment,
        ], 201);
    }

    public function storeBranchReview(
        Branch $branch,
        Request $request,
        ReviewService $service,
    ): JsonResponse {
        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $review = $service->submitBranchReview(
                (int) $request->user()->getKey(),
                (int) $branch->id,
                (int) $data['rating'],
                $data['comment'] ?? null,
            );
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'id' => $review->id,
            'rating' => $review->rating,
            'comment' => $review->comment,
        ], 201);
    }

    public function productReviews(Product $product): JsonResponse
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
                'user_name' => $r->user?->name ?? 'Customer',
                'created_at' => $r->created_at?->toIso8601String(),
            ]);

        return response()->json([
            'avg_rating' => (float) $product->avg_rating,
            'reviews_count' => (int) $product->reviews_count,
            'reviews' => $reviews,
        ]);
    }

    public function branchReviews(Branch $branch): JsonResponse
    {
        $reviews = BranchReview::query()
            ->with('user:id,name')
            ->where('branch_id', $branch->id)
            ->where('is_hidden', false)
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn (BranchReview $r) => [
                'id' => $r->id,
                'rating' => $r->rating,
                'comment' => $r->comment,
                'user_name' => $r->user?->name ?? 'Customer',
                'created_at' => $r->created_at?->toIso8601String(),
            ]);

        return response()->json([
            'avg_rating' => (float) $branch->avg_rating,
            'reviews_count' => (int) $branch->reviews_count,
            'reviews' => $reviews,
        ]);
    }
}
