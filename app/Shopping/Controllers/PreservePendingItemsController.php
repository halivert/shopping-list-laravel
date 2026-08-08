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
                    'required_quantity' => (int) ($item->quantity ?: 1),
                ]);
            });
        });

        return $request->wantsJson()
            ? response()->json(status: 204)
            : back();
    }
}
