<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FavouriteController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        if ($user === null) {
            abort(403);
        }

        $products = $user->favouriteProducts()
            ->with(['stocks' => fn ($q) => $q])
            ->get()
            ->map(fn (Product $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'slug' => $p->slug,
                'image' => $p->image,
                'price' => (float) $p->base_price,
            ])
            ->values();

        return Inertia::render('storefront/favourites', [
            'products' => $products,
        ]);
    }

    public function toggle(Request $request, Product $product): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $relation = $user->favouriteProducts();
        if ($relation->where('products.id', $product->getKey())->exists()) {
            $relation->detach($product->getKey());

            return response()->json(['favourited' => false]);
        }

        $relation->attach($product->getKey());

        return response()->json(['favourited' => true]);
    }
}
