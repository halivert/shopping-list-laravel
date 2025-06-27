<?php

namespace App\Shopping\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\User;
use App\Shopping\Middleware\SidebarShoppingDaysMiddleware;
use App\Shopping\Requests\StoreShoppingDayRequest;
use App\Shopping\Requests\UpdateShoppingDayRequest;
use App\Shopping\Resources\ShoppingDayResource;
use App\Shopping\ShoppingDay;
use App\Shopping\ShoppingDayItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ShoppingDayController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource([ShoppingDay::class, 'owner'], 'shoppingDay');

        $this->middleware(SidebarShoppingDaysMiddleware::class);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(
        StoreShoppingDayRequest $request,
        User $owner
    ): JsonResponse|RedirectResponse {
        $shoppingDay = $owner->shoppingDays()->create([
            'date' => now()
        ]);

        return $request->wantsJson()
            ? response()->json(ShoppingDayResource::make($shoppingDay))
            : to_route('shopping-days.edit', ['shoppingDay' => $shoppingDay]);
    }

    /**
     * Display the specified resource.
     */
    public function show(
        Request $request,
        ShoppingDay $shoppingDay
    ): JsonResponse|Response {
        $shoppingDay->load(['items']);

        $shoppingDayResource = ShoppingDayResource::make($shoppingDay);

        return $request->wantsJson()
            ? response()->json($shoppingDayResource)
            : Inertia::render('shopping/ShoppingDayShow', [
                'shoppingDay' => $shoppingDayResource
            ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ShoppingDay $shoppingDay): JsonResponse|Response
    {
        $shoppingDay->load(['items']);

        $products = $shoppingDay->owner->products->except(
            $shoppingDay->items->pluck('product.id')->toArray()
        );

        return Inertia::render('shopping/ShoppingDayEdit', [
            'shoppingDay' => ShoppingDayResource::make($shoppingDay),
            'otherProducts' => ProductResource::collection($products),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        UpdateShoppingDayRequest $request,
        ShoppingDay $shoppingDay
    ): JsonResponse|RedirectResponse {
        $attrs = $request->validated();

        DB::transaction(function () use ($attrs, $shoppingDay) {
            $requestProducts = Arr::get($attrs, 'products', []);
            $requestItems = Arr::get($attrs, 'items', []);

            $productNames = Product::query()
                ->find(Arr::pluck($requestProducts, 'id'))
                ->pluck('name', 'id');

            $itemsToDelete = $shoppingDay->items->except(
                array_map(fn($item) => $item['id'], $requestItems)
            );

            // Delete items
            $itemsToDelete->map(fn(ShoppingDayItem $item) => $item->delete());

            // Create new products
            $shoppingDay->items()->createMany(
                collect($requestProducts)->map(fn($product) => [
                    'product_id' => $product['id'],
                    'index' => $product['index'],
                    'name' => $productNames[$product['id']],
                    'quantity' => str($productNames[$product['id']])
                        ->after('-')->toInteger() ?: 1,
                ])
            );

            // Update current items data
            collect($requestItems)->each(
                function ($attrs) use ($shoppingDay) {
                    $item = $shoppingDay->items->find($attrs['id']);

                    $item->index = Arr::get($attrs, 'index', $item->index);

                    $item->unit_price = Arr::get(
                        $attrs,
                        'unitPrice',
                        $item->unit_price
                    );

                    $item->quantity = Arr::get(
                        $attrs,
                        'quantity',
                        $item->quantity
                    );

                    if ($item->isDirty()) {
                        $item->save();
                    }
                }
            );

            if ($attrs['touch'] ?? false) {
                $shoppingDay->touch();
            }
        });

        return $request->wantsJson()
            ? response()->json(ShoppingDayResource::make($shoppingDay))
            : back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ShoppingDay $shoppingDay): void
    {
        //
    }
}
