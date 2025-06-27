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
use Illuminate\Support\Carbon;
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
        $attrs = $request->validated();

        $shoppingDay = $owner->shoppingDays()->create([
            'date' => Carbon::parse($attrs['date']),
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
        $shoppingDay->load([
            'items.shoppingDay',
            'items.product.shoppingDayItems.shoppingDay'
        ]);

        $shoppingDay->items->each(function (ShoppingDayItem $item) {
            /** @var Product */
            $product = $item->product;

            $item->product->lastPrice = $product
                ->getLastPrice($item->shoppingDay->date);
        });

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
            $requestItems = Arr::get($attrs, 'items', []);

            /**
             * First delete non existent items, in order to not delete created
             * products
             */
            if (Arr::has($attrs, 'items')) {
                $requestItems = Arr::get($attrs, 'items', []);

                $itemsToDelete = $shoppingDay->items->except(
                    array_map(fn($item) => $item['id'], $requestItems)
                );

                // Delete items
                $itemsToDelete->map(
                    fn(ShoppingDayItem $item) => $item->delete()
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
            }

            /**
             * Then create new products (those are not going to be in
             * requestItems array)
             */
            if (Arr::has($attrs, 'products')) {
                $requestProducts = Arr::get($attrs, 'products', []);
                $productNames = Product::query()
                    ->find(Arr::pluck($requestProducts, 'id'))
                    ->pluck('name', 'id');

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
            }


            // Update shopping day date
            if (Arr::has($attrs, 'date')) {
                $shoppingDay->update(['date' => Carbon::parse($attrs['date'])]);
            }

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
    public function destroy(
        Request $request,
        ShoppingDay $shoppingDay
    ): JsonResponse|RedirectResponse {
        $shoppingDay->delete();

        return $request->wantsJson()
            ? response()->json(204, null)
            : to_route('home');
    }
}
