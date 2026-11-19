<?php

namespace App\Stores\Controllers;

use App\Http\Controllers\Controller;
use App\Stores\Requests\SortStoreProductsRequest;
use App\Stores\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class StoreProductsController extends Controller
{
    /**
     * Saves the store order: the given product ids, in order.
     */
    public function store(SortStoreProductsRequest $request, Store $store): JsonResponse|RedirectResponse
    {
        DB::transaction(
            fn () => $store->sortProducts($request->validated('products'))
        );

        return $request->wantsJson()
            ? response()->json(status: 204)
            : back();
    }
}
