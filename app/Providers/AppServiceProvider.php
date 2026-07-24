<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use Illuminate\Database\Eloquent\Relations\Relation;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            \App\Services\PaymentGatewayService::class,
            \App\Services\MidtransGatewayService::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Relation::morphMap([
            'customer' => \App\Models\Customer::class,
            'jastiper' => \App\Models\Jastiper::class,
            'admin' => \App\Models\Admin::class,
        ]);
    }
}