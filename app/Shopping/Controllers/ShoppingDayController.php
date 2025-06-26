<?php

namespace App\Shopping\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\User;
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

        return $request->wantsJson()
            ? response()->json(ShoppingDayResource::make($shoppingDay))
            : Inertia::render('shopping/ShoppingDayShow', [
                'shoppingDay' => $shoppingDay
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
            'shoppingDay' => $shoppingDay,
            'otherProducts' => $products,
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
            $productNames = Product::query()
                ->find(Arr::pluck($attrs['products'], 'id'))
                ->pluck('name', 'id');

            $itemsToDelete = $shoppingDay->items->except(
                array_map(fn($item) => $item['id'], $attrs['items'])
            );

            // Delete items
            $itemsToDelete->map(fn(ShoppingDayItem $item) => $item->delete());

            // Create new products
            $shoppingDay->items()->createMany(
                collect($attrs['products'])->map(fn($product) => [
                    'product_id' => $product['id'],
                    'index' => $product['index'],
                    'name' => $productNames[$product['id']],
                    'quantity' => str($productNames[$product['id']])
                        ->after('-')->toInteger() ?: 1,
                ])
            );

            // Update current items index
            collect($attrs['items'])->each(function ($item) use ($shoppingDay) {
                $shoppingDay->items->find($item['id'])->update([
                    'index' => $item['index']
                ]);
            });
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
