<?php

namespace App\Products\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Products\Product;
use App\Products\Requests\UpdateUserProductsRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class UserProductsController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource([Product::class, 'owner']);
    }

    public function index(User $owner): Response
    {
        return Inertia::render('products/ProductsIndex', [
            'owner' => UserResource::make($owner),
            'products' => fn() => ProductResource::collection($owner->products),
        ]);
    }

    public function store(
        UpdateUserProductsRequest $request,
        User $owner
    ): JsonResponse|RedirectResponse {
        $attrs = $request->validated();

        $products = $owner->products;

        $list = $attrs['list'];
        $requestProducts = collect($attrs['products'] ?? []);

        $requestProducts->each(
            function ($productId, $index) use ($products, $list) {
                $product = $products->find($productId);

                $product->$list = $index;
                if ($product->isDirty()) {
                    $product->save();
                }
            }
        );

        return $request->wantsJson()
            ? response()->json(204, null)
            : back();
    }
}
