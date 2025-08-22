<?php

namespace App\Shopping\Controllers;

use App\Http\Controllers\Controller;
use App\Products\Product;
use App\Shopping\Events\ShoppingDayItemUpdated;
use App\Shopping\Requests\StoreShoppingDayItemRequest;
use App\Shopping\Requests\UpdateShoppingDayItemRequest;
use App\Shopping\Resources\ShoppingDayResource;
use App\Shopping\ShoppingDay;
use App\Shopping\ShoppingDayItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ShoppingDayItemController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(
            [ShoppingDayItem::class, 'shoppingDay'],
            'shoppingDayItem'
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(
        StoreShoppingDayItemRequest $request,
        ShoppingDay $shoppingDay
    ): JsonResponse|RedirectResponse {
        $attrs = $request->validated();

        DB::transaction(function () use ($attrs, $shoppingDay) {
            $product = Product::query()->find($attrs['product_id']);

            $newItem = $shoppingDay->items()->create([
                'product_id' => $product->id,
                'index' => $attrs['index'],
                'quantity' => str($product->name)->after('-')->toInteger() ?: 1,
            ]);

            $originalItems = $shoppingDay->items;
            $items = collect($attrs['items']);
            $items->splice($newItem->index, 0, $newItem->id);

            $items->each(
                function ($itemId, $index) use ($originalItems) {
                    $item = $originalItems->find($itemId);

                    if ($item and $item->index !== $index) {
                        $item->update(['index' => $index]);
                    }
                }
            );
        });

        return $request->wantsJson()
            ? response()->json(ShoppingDayResource::make($shoppingDay))
            : back();
    }

    public function update(
        UpdateShoppingDayItemRequest $request,
        ShoppingDay $shoppingDay,
        ShoppingDayItem $shoppingDayItem
    ): JsonResponse|RedirectResponse {
        $attrs = $request->validated();

        $shoppingDayItem->update([
            'unit_price' => Arr::get(
                $attrs,
                'unitPrice',
                $shoppingDayItem->unit_price
            ),
            'quantity' => Arr::get(
                $attrs,
                'quantity',
                $shoppingDayItem->quantity
            ),
        ]);

        ShoppingDayItemUpdated::dispatch($shoppingDayItem->fresh());

        $shoppingDay->touch();

        return $request->wantsJson()
            ? response()->json(null, 204)
            : back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(
        ShoppingDay $shoppingDay,
        ShoppingDayItem $shoppingDayItem,
        Request $request,
    ): JsonResponse|RedirectResponse {
        $shoppingDayItem->delete();

        return $request->wantsJson()
            ? response()->json(204, null)
            : back();
    }
}
