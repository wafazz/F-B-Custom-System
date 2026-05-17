<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Combo;
use App\Models\Product;
use App\Support\RequestChannel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BranchMenuController extends Controller
{
    /** Public read-only menu — branch-scoped, only available + in-stock items. */
    public function __invoke(Request $request, Branch $branch): JsonResponse
    {
        if ($branch->status !== 'active' || ! $branch->accepts_orders) {
            return response()->json([
                'branch' => ['id' => $branch->id, 'code' => $branch->code, 'name' => $branch->name, 'status' => $branch->status],
                'categories' => [],
                'message' => 'Branch is not accepting orders right now.',
            ]);
        }

        $channel = RequestChannel::detect($request);
        $channelColumn = RequestChannel::availableColumn($channel);

        $products = Product::availableAtBranch($branch->id)
            ->where($channelColumn, true)
            ->whereHas('category', fn ($q) => $q->where(Category::channelColumn($channel), true))
            ->with([
                'category.parent',
                'modifierGroups.options' => fn ($q) => $q->where('is_available', true)->orderBy('sort_order')->orderBy('id'),
                'branches' => fn ($q) => $q->where('branches.id', $branch->id),
                'stocks' => fn ($q) => $q->where('branch_id', $branch->id),
            ])
            ->orderBy('category_id')
            ->orderBy('sort_order')
            ->get();

        /** @var array<int, array<string, mixed>> $byCategory */
        $byCategory = [];
        foreach ($products as $product) {
            /** @var Category $category */
            $category = $product->category;
            $catId = $category->getKey();
            if (! isset($byCategory[$catId])) {
                $parent = $category->parent;
                $byCategory[$catId] = [
                    'id' => $catId,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'icon' => $category->icon,
                    'sort_order' => $category->sort_order,
                    'parent_id' => $parent?->id,
                    'parent_name' => $parent?->name,
                    'parent_slug' => $parent?->slug,
                    'parent_sort_order' => $parent ? (int) $parent->sort_order : null,
                    'products' => [],
                ];
            }
            $byCategory[$catId]['products'][] = $this->presentProduct($product, $branch->id);
        }

        $categories = array_values($byCategory);
        usort($categories, fn ($a, $b) => $a['sort_order'] <=> $b['sort_order']);

        $comboModels = Combo::active()
            ->forBranch($branch->id)
            ->with(['products:id,name,image'])
            ->orderBy('sort_order')
            ->get();

        $combos = [];
        foreach ($comboModels as $combo) {
            $items = [];
            foreach ($combo->products as $p) {
                $items[] = [
                    'product_id' => $p->id,
                    'name' => $p->name,
                    'image' => $p->image,
                    'quantity' => (int) $p->getRelationValue('pivot')->quantity,
                ];
            }
            $combos[] = [
                'id' => $combo->id,
                'name' => $combo->name,
                'slug' => $combo->slug,
                'description' => $combo->description,
                'image' => $combo->image,
                'price' => (float) $combo->price,
                'items' => $items,
            ];
        }

        return response()->json([
            'branch' => [
                'id' => $branch->id,
                'code' => $branch->code,
                'name' => $branch->name,
                'sst_rate' => (float) $branch->sst_rate,
                'sst_enabled' => (bool) $branch->sst_enabled,
                'service_charge_rate' => (float) $branch->service_charge_rate,
                'service_charge_enabled' => (bool) $branch->service_charge_enabled,
                'status' => $branch->status,
            ],
            'categories' => $categories,
            'combos' => $combos,
        ]);
    }

    /** @return array<string, mixed> */
    protected function presentProduct(Product $product, int $branchId): array
    {
        $modifierGroups = [];
        foreach ($product->modifierGroups as $group) {
            $options = [];
            foreach ($group->options as $option) {
                $options[] = [
                    'id' => $option->getKey(),
                    'name' => $option->name,
                    'price_delta' => (float) $option->price_delta,
                    'is_default' => $option->is_default,
                ];
            }
            $modifierGroups[] = [
                'id' => $group->getKey(),
                'name' => $group->name,
                'selection_type' => $group->selection_type,
                'is_required' => $group->is_required,
                'min_select' => $group->min_select,
                'max_select' => $group->max_select,
                'options' => $options,
            ];
        }

        return [
            'id' => $product->getKey(),
            'sku' => $product->sku,
            'name' => $product->name,
            'slug' => $product->slug,
            'description' => $product->description,
            'price' => $product->priceForBranch($branchId),
            'base_price' => (float) $product->base_price,
            'tumbler_discount' => (float) $product->tumbler_discount,
            'image' => $product->image,
            'gallery' => $product->gallery,
            'calories' => $product->calories,
            'prep_time_minutes' => $product->prep_time_minutes,
            'is_featured' => $product->is_featured,
            'sst_applicable' => $product->sst_applicable,
            'modifier_groups' => $modifierGroups,
        ];
    }
}
