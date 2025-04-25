<?php

namespace App\Products\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\AccessResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\UserResource;
use App\Models\Access;
use App\Models\User;
use App\Products\Requests\StoreProductsShareRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductsShareController extends Controller
{
    public function create(Request $request): Response
    {
        $user = $request->user();

        $sharedWith = $user->sharedWith
            ->mapInto(AccessResource::class);

        $sharedBy = $user->sharedBy
            ->mapInto(AccessResource::class);

        $requests = Access::query()
            ->where('user_email', $user->email)
            ->whereNull('approved_at')
            ->get()
            ->mapInto(AccessResource::class);

        return Inertia::render('products/ShareProducts', [
            'sharedWith' => $sharedWith,
            'sharedBy' => $sharedBy,
            'requests' => $requests,
        ]);
    }

    public function show(Request $request, Access $access): Response
    {
        $user = $request->user();
        $accessible = $access->accessible;

        if (!($accessible instanceof User)) {
            abort(404);
        }

        if ($user->isNot($access->user)) {
            abort(404);
        }

        $products = $accessible->products;

        return Inertia::render('products/SharedProducts', [
            'products' => fn() => ProductResource::collection($products),
            'user' => UserResource::make($accessible),
        ]);
    }

    public function store(
        StoreProductsShareRequest $request
    ): JsonResponse|RedirectResponse {
        $attrs = $request->validated();

        $access = new Access([
            'user_email' => $attrs['email']
        ]);

        $access->accessible()->associate($request->user());
        $access->save();

        return $request->wantsJson()
            ? response()->json(204, null)
            : back();
    }

    public function update(
        Request $request,
        Access $access
    ): JsonResponse|RedirectResponse {
        if ($request->user()->email !== $access->user_email) {
            abort(404);
        }

        $access->user_id = $request->user()->id;
        $access->approved_at = now();
        $access->save();

        return $request->wantsJson()
            ? response()->json(204, null)
            : back();
    }

    public function destroy(
        Request $request,
        Access $access
    ): JsonResponse|RedirectResponse {
        $user = $request->user();

        if (!$user->is($access->user) and !$user->is($access->accessible)) {
            abort(404);
        }

        $access->delete();

        return $request->wantsJson()
            ? response()->json(204, null)
            : back();
    }
}
