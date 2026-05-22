<?php

namespace App\Http\Controllers\Api\Pos;

use App\Events\BranchStockChanged;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\JsonResponse;

class StockController extends Controller
{
    /** Per-branch availability list (the screen cashiers use to 86 items). */
    public function index(Branch $branch): JsonResponse
    {
        $rows = Product::query()
            ->whereHas('branches', fn ($q) => $q->where('branches.id', $branch->id)->where('branch_product.is_available', true))
            ->whereHas('category', fn ($q) => $q->where('available_pos', true))
            ->with([
                'category:id,name',
                'stocks' => fn ($q) => $q->where('branch_id', $branch->id),
            ])
            ->orderBy('category_id')
            ->orderBy('sort_order')
            ->get();

        $products = [];
        foreach ($rows as $p) {
            /** @var BranchStock|null $stock */
            $stock = $p->stocks->first();
            /** @var Category|null $category */
            $category = $p->category;
            $products[] = [
                'product_id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'category' => $category?->name,
                'is_available' => $stock instanceof BranchStock ? $stock->is_available : true,
                'track_quantity' => $stock instanceof BranchStock ? $stock->track_quantity : false,
                'quantity' => $stock instanceof BranchStock ? $stock->quantity : 0,
                'low_threshold' => $stock instanceof BranchStock ? $stock->low_threshold : 5,
            ];
        }

        return response()->json(['products' => $products]);
    }

    /** Flip a product's branch availability. Broadcasts so other clients refresh. */
    public function toggle(Branch $branch, Product $product): JsonResponse
    {
        $stock = BranchStock::query()->firstOrCreate(
            ['branch_id' => $branch->id, 'product_id' => $product->id],
            ['quantity' => 0, 'low_threshold' => 5, 'is_available' => true, 'track_quantity' => false],
        );

        $stock->update(['is_available' => ! $stock->is_available]);

        $available = $stock->is_available && (! $stock->track_quantity || $stock->quantity > 0);
        event(new BranchStockChanged($branch->id, $product->id, $available, $stock->quantity));

        return response()->json([
            'product_id' => $product->id,
            'is_available' => $stock->is_available,
            'quantity' => $stock->quantity,
        ]);
    }
}
