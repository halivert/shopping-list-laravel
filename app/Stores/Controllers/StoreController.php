<?php

namespace App\Stores\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Stores\Requests\StoreRequest;
use App\Stores\Resources\StoreResource;
use App\Stores\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    public function store(StoreRequest $request, User $owner): JsonResponse|RedirectResponse
    {
        $store = $owner->stores()->create($request->validated());

        return $request->wantsJson()
            ? response()->json(StoreResource::make($store), 201)
            : back();
    }

    public function update(StoreRequest $request, Store $store): JsonResponse|RedirectResponse
    {
        $store->update($request->validated());

        return $request->wantsJson()
            ? response()->json(status: 204)
            : back();
    }

    public function destroy(Request $request, Store $store): JsonResponse|RedirectResponse
    {
        $this->authorize('delete', $store);

        $store->delete();

        return $request->wantsJson()
            ? response()->json(status: 204)
            : back();
    }
}
