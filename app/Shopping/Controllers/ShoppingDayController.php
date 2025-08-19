<?php

namespace App\Shopping\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Http\Resources\UserResource;
use App\Products\Product;
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
use Illuminate\Support\Collection;
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

    public function index(Request $request, User $owner): JsonResponse|Response
    {
        $shoppingDaysPaginator = $owner->shoppingDays()->with('items')
            ->paginate(15)
            ->through(
                fn($shoppingDay) => ShoppingDayResource::make($shoppingDay)
            );

        return $request->wantsJson()
            ? response()->json($shoppingDaysPaginator)
            : Inertia::render('shopping/ShoppingDayIndex', [
                'owner' => UserResource::make($owner),
                'shoppingDaysPaginator' => $shoppingDaysPaginator
            ]);
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

        $products = fn() => $shoppingDay->owner->products()->with([
            'shoppingDayItems.shoppingDay'
        ])->orderBy('search_index')->get();


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
                'shoppingDay' => $shoppingDayResource,
                'products' => $products
            ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ShoppingDay $shoppingDay): JsonResponse|Response
    {
        $shoppingDay->load(['items']);

        $products = $shoppingDay->owner->products()->with([
            'shoppingDayItems.shoppingDay'
        ])->orderBy('search_index')->get();

        $products->each(function (Product $product) use ($shoppingDay) {
            $product->lastPrice = $product
                ->getLastPrice($shoppingDay->date);
        });

        return Inertia::render('shopping/ShoppingDayEdit', [
            'shoppingDay' => ShoppingDayResource::make($shoppingDay),
            'products' => ProductResource::collection($products),
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
            if (Arr::has($attrs, 'products')) {
                $requestProducts = collect(Arr::get($attrs, 'products', []));
                $this->updateProductsList($shoppingDay, $requestProducts);
            }

            if (Arr::has($attrs, 'items')) {
                $requestItems = collect(Arr::get($attrs, 'items', []));

                $requestItems->each(function ($attrs) use ($shoppingDay) {
                    $item = $shoppingDay->items->find($attrs['id']);

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
                });
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

    /**
     * Add products and delete items from a shopping day
     *
     * @param Collection<int, Product> $productIds
     */
    private function updateProductsList(
        ShoppingDay $shoppingDay,
        Collection $productIds
    ): void {
        $currentItemsProduct = $shoppingDay->items
            ->pluck('product.id', 'id');

        /**
         * First delete non existent items, in order to not delete created
         * products
         */
        $productIdsToDelete = $currentItemsProduct->diff($productIds);

        if ($productIdsToDelete->count()) {
            ShoppingDayItem::destroy($productIdsToDelete->keys());
        }

        /**
         * Then we can create the new items, without touching the
         * existent ones
         */
        $productIdsToCreate = $productIds->diff($currentItemsProduct);

        /** @var Collection<int, Product> */
        $dbProducts = Product::query()->find($productIdsToCreate);

        $itemsToCreate = $dbProducts->map(
            fn($product) => [
                'product_id' => $product->id,
                'index' => $product->shopping_index ?? 0,
                'name' => $product->name,
                'quantity' => str($product->name)
                    ->after('-')->toInteger() ?: 1,
            ]
        );

        $shoppingDay->items()->createMany($itemsToCreate);
    }
}
