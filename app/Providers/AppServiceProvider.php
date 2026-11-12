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
        if (
            (isset($_SERVER['HTTP_CF_VISITOR']) && str_contains($_SERVER['HTTP_CF_VISITOR'], 'https')) ||
            (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
            (isset($_SERVER['HTTP_HOST']) && !in_array($_SERVER['HTTP_HOST'], ['localhost:8000', '127.0.0.1:8000', 'localhost', '127.0.0.1']))
        ) {
            $_SERVER['HTTPS'] = 'on';
            $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        Relation::morphMap([
            'customer' => \App\Models\Customer::class,
            'jastiper' => \App\Models\Jastiper::class,
            'admin' => \App\Models\Admin::class,
        ]);

        $this->bootMidtransSettings();
    }

    /**
     * Memuat konfigurasi Midtrans dinamis dari database (portal admin) ke config runtime.
     */
    protected function bootMidtransSettings(): void
    {
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('settings')) {
                $serverKey = \App\Models\Setting::get('midtrans_server_key');
                if ($serverKey !== null && $serverKey !== '') {
                    config(['services.midtrans.server_key' => $serverKey]);
                }

                $clientKey = \App\Models\Setting::get('midtrans_client_key');
                if ($clientKey !== null && $clientKey !== '') {
                    config(['services.midtrans.client_key' => $clientKey]);
                }

                $merchantId = \App\Models\Setting::get('midtrans_merchant_id');
                if ($merchantId !== null && $merchantId !== '') {
                    config(['services.midtrans.merchant_id' => $merchantId]);
                }

                $isProduction = \App\Models\Setting::get('midtrans_is_production');
                if ($isProduction !== null) {
                    config(['services.midtrans.is_production' => $isProduction === '1' || $isProduction === true]);
                }

                $isActive = \App\Models\Setting::get('midtrans_is_active');
                if ($isActive !== null) {
                    config(['services.midtrans.is_active' => $isActive === '1' || $isActive === true]);
                }
            }
        } catch (\Throwable $e) {
            // Silently fallback to config/services.php (.env)
        }
    }
}