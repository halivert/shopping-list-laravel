<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductResource;
use App\Products\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $products = $request->user()->products()->with([
            'shoppingDayItems.shoppingDay'
        ])->orderBy('name')->get();

        $products->each(function (Product $product) {
            $product->lastPrice = $product->getLastPrice();
        });

        return Inertia::render('Dashboard', [
            'products' => fn() => ProductResource::collection($products),
        ]);
    }
}
