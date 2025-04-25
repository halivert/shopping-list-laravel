<?php

namespace App\Products\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Products\Requests\StoreProductRequest;
use App\Products\Requests\UpdateProductRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class ProductController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Product::class);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): void
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): void
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductRequest $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        $attrs = $request->validated();

        if (Arr::has($attrs, 'name')) {
            $user->products()->create($attrs);
        } else if (Arr::has($attrs, 'products')) {
            $user->products()->createMany(
                array_map(fn($item) => ['name' => $item], $attrs['products'])
            );
        }

        return $request->wantsJson()
            ? response()->json(204, null)
            : back();
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product): void
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product): void
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductRequest $request, Product $product): JsonResponse|RedirectResponse
    {
        $attrs = $request->validated();

        $product->update([
            'name' => $attrs['name'],
        ]);

        return $request->wantsJson()
            ? response()->json(204, null)
            : back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(
        Request $request,
        Product $product
    ): JsonResponse|RedirectResponse {
        $product->delete();

        return $request->wantsJson()
            ? response()->json(204, null)
            : back();
    }
}
