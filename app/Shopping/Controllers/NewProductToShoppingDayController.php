<?php

namespace App\Shopping\Controllers;

use App\Http\Controllers\Controller;
use App\Products\Product;
use App\Shopping\Resources\ShoppingDayItemResource;
use App\Shopping\ShoppingDay;
use App\Shopping\ShoppingDayItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NewProductToShoppingDayController extends Controller
{
    public function __invoke(
        Request $request,
        ShoppingDay $shoppingDay
    ): JsonResponse|RedirectResponse {
        $this->authorize('update', $shoppingDay);

        $attrs = $request->validate([
            'name' => 'string|required',
        ]);

        $shoppingDayItem = DB::transaction(
            function () use ($attrs, $shoppingDay) {
                $lastItem = ShoppingDayItem::query()
                    ->where('shopping_day_id', $shoppingDay->id)
                    ->orderByDesc('index')
                    ->first();

                $product = Product::query()->firstOrCreate(
                    ['name' => $attrs['name']],
                    ['owner_id' => $shoppingDay->owner_id]
                );

                return $shoppingDay->items()->firstOrCreate(
                    ['product_id' => $product->id],
                    [
                        'index' => $lastItem?->index ? $lastItem->index + 1 : 0,
                        'quantity' => str($product->name)->after('-')
                            ->toInteger() ?: 1,
                    ]
                );
            }
        );

        return $request->wantsJson()
            ? response()->json(
                201,
                ShoppingDayItemResource::make($shoppingDayItem)
            ) : back();
    }
}
