<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Voucher;
use Illuminate\Http\JsonResponse;

class PromoPickerController extends Controller
{
    /**
     * Buy-X-get-Y picker data for native apps — mirrors the Inertia
     * PromoPickerController so the mobile picker shows the same paid + free
     * product pools. URL: GET /api/branches/{branch}/promos/{voucher:code}
     */
    public function show(Branch $branch, Voucher $voucher): JsonResponse
    {
        abort_unless($voucher->discount_type === 'buy_x_get_y', 404);
        abort_unless($voucher->status === 'active', 404);
        if (is_array($voucher->branch_ids) && count($voucher->branch_ids) > 0
            && ! in_array($branch->id, $voucher->branch_ids, true)) {
            abort(404);
        }

        $paidIds = array_map(static fn ($id): int => (int) $id, $voucher->product_ids ?? []);
        $freeIds = self::resolveFreePool($voucher);

        return response()->json([
            'branch' => [
                'id' => $branch->id,
                'code' => $branch->code,
                'name' => $branch->name,
            ],
            'voucher' => [
                'code' => $voucher->code,
                'name' => $voucher->name,
                'description' => $voucher->description,
                'banner_image' => $voucher->banner_image,
                'bxgy_buy_qty' => (int) $voucher->bxgy_buy_qty,
                'bxgy_free_qty' => (int) $voucher->bxgy_free_qty,
                'free_scope_mode' => match (true) {
                    $voucher->bxgy_free_product_ids === null && $voucher->bxgy_free_combo_ids === null => 'same',
                    $voucher->bxgy_free_product_ids === [] && $voucher->bxgy_free_combo_ids === [] => 'any',
                    default => 'cross',
                },
            ],
            'paid_products' => self::loadProducts($branch, $paidIds),
            'free_products' => self::loadProducts($branch, $freeIds),
        ]);
    }

    /** @return list<int>|null  null = "all products" sentinel */
    private static function resolveFreePool(Voucher $voucher): ?array
    {
        if ($voucher->bxgy_free_product_ids === null && $voucher->bxgy_free_combo_ids === null) {
            return array_map(static fn ($id): int => (int) $id, $voucher->product_ids ?? []);
        }
        if ($voucher->bxgy_free_product_ids === [] && $voucher->bxgy_free_combo_ids === []) {
            return null;
        }

        return array_map(static fn ($id): int => (int) $id, $voucher->bxgy_free_product_ids ?? []);
    }

    /**
     * @param  list<int>|null  $ids  null = unrestricted (any active product at branch)
     * @return array<int, array{id:int,name:string,image:string|null,price:float,sku:string,modifier_groups:array<int, mixed>}>
     */
    private static function loadProducts(Branch $branch, ?array $ids): array
    {
        $query = Product::active()
            ->availableAtBranch($branch->id)
            ->with(['modifierGroups.options' => fn ($q) => $q->where('is_available', true)->orderBy('sort_order')->orderBy('id')]);
        if (is_array($ids)) {
            if (empty($ids)) {
                return [];
            }
            $query->whereIn('products.id', $ids);
        }

        return $query
            ->get(['products.id', 'products.name', 'products.image', 'products.base_price', 'products.sku'])
            ->map(static fn (Product $p) => [
                'id' => (int) $p->id,
                'name' => $p->name,
                'image' => $p->image,
                'price' => (float) $p->priceForBranch($branch->id),
                'sku' => $p->sku,
                'modifier_groups' => $p->modifierGroups->map(static fn ($g) => [
                    'id' => (int) $g->getKey(),
                    'name' => $g->name,
                    'selection_type' => $g->selection_type,
                    'is_required' => $g->is_required,
                    'min_select' => $g->min_select,
                    'max_select' => $g->max_select,
                    'options' => $g->options->map(static fn ($o) => [
                        'id' => (int) $o->getKey(),
                        'name' => $o->name,
                        'price_delta' => (float) $o->price_delta,
                        'is_default' => $o->is_default,
                    ])->values()->all(),
                ])->values()->all(),
            ])
            ->values()
            ->all();
    }
}
