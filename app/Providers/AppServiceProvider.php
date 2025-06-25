<?php

namespace App\Providers;

use App\Shopping\Policies\ShoppingDayPolicy;
use App\Shopping\ShoppingDay;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(function (SocialiteWasCalled $event) {
            $event->extendSocialite(
                'google',
                \SocialiteProviders\Google\Provider::class
            );
        });

        JsonResource::withoutWrapping();
        Model::preventLazyLoading(App::isLocal());

        /**
         *
         * Register policies
         */
        Gate::policy(ShoppingDay::class, ShoppingDayPolicy::class);
    }
}
