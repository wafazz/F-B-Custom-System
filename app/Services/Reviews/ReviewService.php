<?php

namespace App\Services\Reviews;

use App\Enums\OrderStatus;
use App\Models\Branch;
use App\Models\BranchReview;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductReview;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ReviewService
{
    /**
     * Submit (or update) a product review. Verifies the customer actually
     * ordered the product in a completed order. Recomputes parent aggregate.
     */
    public function submitProductReview(
        int $userId,
        int $productId,
        int $rating,
        ?string $comment,
    ): ProductReview {
        if ($rating < 1 || $rating > 5) {
            throw new RuntimeException('Rating must be between 1 and 5.');
        }

        return DB::transaction(function () use ($userId, $productId, $rating, $comment) {
            $order = Order::query()
                ->where('user_id', $userId)
                ->where('status', OrderStatus::Completed)
                ->whereHas('items', fn ($q) => $q->where('product_id', $productId))
                ->latest()
                ->first();
            if ($order === null) {
                throw new RuntimeException('You can only review items from a completed order.');
            }

            $review = ProductReview::query()->updateOrCreate(
                ['user_id' => $userId, 'product_id' => $productId],
                [
                    'order_id' => $order->id,
                    'rating' => $rating,
                    'comment' => $comment,
                    'is_hidden' => false,
                ],
            );

            $this->recomputeProductAggregate($productId);

            return $review;
        });
    }

    public function submitBranchReview(
        int $userId,
        int $branchId,
        int $rating,
        ?string $comment,
    ): BranchReview {
        if ($rating < 1 || $rating > 5) {
            throw new RuntimeException('Rating must be between 1 and 5.');
        }

        return DB::transaction(function () use ($userId, $branchId, $rating, $comment) {
            $order = Order::query()
                ->where('user_id', $userId)
                ->where('branch_id', $branchId)
                ->where('status', OrderStatus::Completed)
                ->latest()
                ->first();
            if ($order === null) {
                throw new RuntimeException('You can only review a branch after a completed order there.');
            }

            $review = BranchReview::query()->updateOrCreate(
                ['user_id' => $userId, 'branch_id' => $branchId],
                [
                    'order_id' => $order->id,
                    'rating' => $rating,
                    'comment' => $comment,
                    'is_hidden' => false,
                ],
            );

            $this->recomputeBranchAggregate($branchId);

            return $review;
        });
    }

    public function recomputeProductAggregate(int $productId): void
    {
        $row = ProductReview::query()
            ->where('product_id', $productId)
            ->where('is_hidden', false)
            ->selectRaw('AVG(rating) as avg_rating, COUNT(*) as reviews_count')
            ->first();

        Product::query()->whereKey($productId)->update([
            'avg_rating' => round((float) ($row->avg_rating ?? 0), 2),
            'reviews_count' => (int) ($row->reviews_count ?? 0),
        ]);
    }

    public function recomputeBranchAggregate(int $branchId): void
    {
        $row = BranchReview::query()
            ->where('branch_id', $branchId)
            ->where('is_hidden', false)
            ->selectRaw('AVG(rating) as avg_rating, COUNT(*) as reviews_count')
            ->first();

        Branch::query()->whereKey($branchId)->update([
            'avg_rating' => round((float) ($row->avg_rating ?? 0), 2),
            'reviews_count' => (int) ($row->reviews_count ?? 0),
        ]);
    }
}
