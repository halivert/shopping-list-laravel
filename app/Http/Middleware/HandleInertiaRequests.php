<?php

namespace App\Http\Middleware;

use App\Shopping\Resources\ShoppingDayResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $lastFiveShoppingDays = $request->user()?->getLastNShoppingDays(5);

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'lang' => str(App::currentLocale())->replace('_', '-'),
            'auth' => [
                'user' => $request->user(),
            ],
            'sidebarShoppingDays' => $lastFiveShoppingDays
                ? fn() => ShoppingDayResource::collection($lastFiveShoppingDays)
                : null
        ];
    }
}
