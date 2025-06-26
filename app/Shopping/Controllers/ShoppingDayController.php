<?php

namespace App\Shopping\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Shopping\Requests\StoreShoppingDayRequest;
use App\Shopping\Requests\UpdateShoppingDayRequest;
use App\Shopping\Resources\ShoppingDayResource;
use App\Shopping\ShoppingDay;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            $originalItems = $shoppingDay->items;

            collect($attrs['items'])->each(
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

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ShoppingDay $shoppingDay): void
    {
        //
    }
}
