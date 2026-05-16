<?php

namespace App\Http\Controllers\Pos;

use App\Events\BranchStockChanged;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StockController extends Controller
{
    public function index(Request $request): Response
    {
        $branchId = (int) $request->session()->get('pos.branch_id');
        $branch = Branch::findOrFail($branchId);

        $rows = Product::query()
            ->whereHas('branches', fn ($q) => $q->where('branches.id', $branchId)->where('branch_product.is_available', true))
            ->whereHas('category', fn ($q) => $q->where('available_pos', true))
            ->with([
                'category:id,name',
                'stocks' => fn ($q) => $q->where('branch_id', $branchId),
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

        return Inertia::render('pos/stock', [
            'branch' => [
                'id' => $branch->id,
                'code' => $branch->code,
                'name' => $branch->name,
            ],
            'staff' => [
                'name' => $request->session()->get('pos.user_name'),
            ],
            'products' => $products,
        ]);
    }

    public function toggle(Request $request, Product $product): RedirectResponse
    {
        $branchId = (int) $request->session()->get('pos.branch_id');
        $stock = BranchStock::query()->firstOrCreate(
            ['branch_id' => $branchId, 'product_id' => $product->id],
            ['quantity' => 0, 'low_threshold' => 5, 'is_available' => true, 'track_quantity' => false],
        );

        $stock->update(['is_available' => ! $stock->is_available]);

        $available = $stock->is_available && (! $stock->track_quantity || $stock->quantity > 0);
        event(new BranchStockChanged($branchId, $product->id, $available, $stock->quantity));

        return back()->with('success', $stock->is_available ? "{$product->name} marked available" : "{$product->name} marked out-of-stock");
    }
}
