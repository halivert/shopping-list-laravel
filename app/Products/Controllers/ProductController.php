<?php

namespace App\Products\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Access;
use App\Products\Events\ProductCreated;
use App\Products\Events\ProductUpdated;
use App\Products\Product;
use App\Models\User;
use App\Products\Requests\StoreProductRequest;
use App\Products\Requests\UpdateProductRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Inertia\Inertia;
use Inertia\Response;
use App\Http\Resources\ProductResource;

class ProductController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Product::class);
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product): Response
    {
        $product->load('shoppingDayItems.shoppingDay');
        $product->lastPrice = $product->getLastPrice();

        return Inertia::render('products/ProductShow', [
            'product'   => ProductResource::make($product),
            'purchases' => $product->getPurchaseHistory(),
            'stats'     => [
                'timesBought'       => $product->getTimesBought(),
                'averagePrice'      => $product->getAverageUnitPrice(),
                'minPrice'          => $product->getMinUnitPrice(),
                'maxPrice'          => $product->getMaxUnitPrice(),
                'averageQuantity'   => $product->getAverageQuantity(),
                'avgDaysBetween'    => $product->getAverageDaysBetweenPurchases(),
                'daysPerUnit'       => $product->getDaysPerUnit(),
                'estimatedDuration' => $product->getEstimatedDuration(),
                'totalSpent'        => $product->getTotalSpent(),
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(
        StoreProductRequest $request
    ): JsonResponse|RedirectResponse {
        $user = $request->user();
        $attrs = $request->validated();

        if (Arr::has($attrs, 'user_id')) {
            $access = Access::query()
                ->where('user_id', $user->id)
                ->whereMorphedTo(
                    'accessible',
                    User::query()->find($attrs['user_id'])
                )->first();

            if (!$access) {
                abort(404);
            }

            $targetUser = $access->accessible;

            if (!($targetUser instanceof User)) {
                abort(404);
            }
        } else {
            $targetUser = $user;
        }

        if (Arr::has($attrs, 'name')) {
            $product = $targetUser->products()->create($attrs);
            ProductCreated::dispatch($product);
        } else if (Arr::has($attrs, 'products')) {
            $products = $targetUser->products()->createMany(
                array_map(fn($item) => ['name' => $item], $attrs['products'])
            );
            $products->each(fn ($product) => ProductCreated::dispatch($product));
        }

        return $request->wantsJson()
            ? response()->json(204, null)
            : back();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        UpdateProductRequest $request,
        Product $product
    ): JsonResponse|RedirectResponse {
        $attrs = $request->validated();

        $product->update(
            collect($attrs)->only(['name', 'is_required', 'required_quantity'])->all()
        );

        ProductUpdated::dispatch($product);

        return $request->wantsJson()
            ? response()->json(204, null)
            : back();
    }

    /**
     * Remove the specified resource from storage (soft delete).
     */
    public function destroy(
        Request $request,
        Product $product
    ): JsonResponse|RedirectResponse {
        $ownerId = $product->owner_id;
        $productId = $product->id;
        $productName = $product->name;

        $product->delete();

        return $request->wantsJson()
            ? response()->json(204, null)
            : redirect()
                ->route('users.products.index', ['owner' => $ownerId])
                ->with('deletedProduct', ['id' => $productId, 'name' => $productName]);
    }

    /**
     * Restore a soft-deleted product (undo delete).
     */
    public function restore(
        Request $request,
        string $product
    ): JsonResponse|RedirectResponse {
        $model = Product::withTrashed()->findOrFail($product);

        $this->authorize('delete', $model);

        $model->restore();

        return $request->wantsJson()
            ? response()->json(204, null)
            : back();
    }
}
