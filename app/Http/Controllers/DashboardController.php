<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductResource;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $products = $request->user()->products;

        return Inertia::render('Dashboard', [
            'products' => fn() => ProductResource::collection($products),
        ]);
    }
}
