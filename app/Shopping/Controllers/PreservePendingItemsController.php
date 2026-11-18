<?php

namespace App\Shopping\Controllers;

use App\Http\Controllers\Controller;
use App\Shopping\ShoppingDay;
use App\Shopping\ShoppingDayItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PreservePendingItemsController extends Controller
{
    public function __invoke(
        Request $request,
        ShoppingDay $shoppingDay
    ): JsonResponse|RedirectResponse {
        $this->authorize('update', $shoppingDay);

        DB::transaction(function () use ($shoppingDay) {
            $pendingItems = $shoppingDay->items()
                ->whereNull('unit_price')
                ->get();

            $pendingItems->each(function (ShoppingDayItem $item) {
                $item->product->update([
                    'is_required' => true,
                    // Products are required in whole units: 0.5 kg → 1
                    'required_quantity' => max(1, (int) ceil($item->quantity ?? 1)),
                ]);
            });
        });

        return $request->wantsJson()
            ? response()->json(status: 204)
            : back();
    }
}
