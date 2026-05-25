<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\BranchReview;
use Illuminate\Http\JsonResponse;

class BranchReviewsController extends Controller
{
    /**
     * Public branch reviews — mirrors the Inertia storefront branch-reviews
     * page so native apps render the same rating breakdown + review list.
     */
    public function __invoke(Branch $branch): JsonResponse
    {
        $reviews = BranchReview::query()
            ->with('user:id,name')
            ->where('branch_id', $branch->id)
            ->where('is_hidden', false)
            ->latest()
            ->limit(200)
            ->get()
            ->map(fn (BranchReview $r) => [
                'id' => $r->id,
                'rating' => $r->rating,
                'comment' => $r->comment,
                'user_name' => $r->user->name,
                'created_at' => $r->created_at?->toIso8601String(),
            ])
            ->values();

        return response()->json([
            'branch' => [
                'id' => $branch->id,
                'name' => $branch->name,
                'avg_rating' => (float) $branch->avg_rating,
                'reviews_count' => (int) $branch->reviews_count,
            ],
            'reviews' => $reviews,
        ]);
    }
}
