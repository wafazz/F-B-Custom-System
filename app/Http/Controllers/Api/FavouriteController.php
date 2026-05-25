<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FavouriteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $products = $user->favouriteProducts()
            ->get()
            ->map(fn (Product $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'slug' => $p->slug,
                'image' => $p->image,
                'price' => (float) $p->base_price,
            ])
            ->values();

        return response()->json(['products' => $products]);
    }

    public function toggle(Request $request, Product $product): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $relation = $user->favouriteProducts();

        if ($relation->where('products.id', $product->getKey())->exists()) {
            $relation->detach($product->getKey());

            return response()->json(['favourited' => false]);
        }

        $relation->attach($product->getKey());

        return response()->json(['favourited' => true]);
    }
}
