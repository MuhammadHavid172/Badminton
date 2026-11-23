<?php

namespace App\Providers;

use App\Contracts\PaymentGateway;
use App\Services\Payment\SimulatedGateway;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PaymentGateway::class, function () {
            $driver = config('gor.payment_driver', 'simulated');

            return match ($driver) {
                default => app(SimulatedGateway::class),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Peringatan jika di lingkungan production tetapi driver pembayaran masih simulated
        if (app()->environment('production') && config('gor.payment_driver') === 'simulated') {
            Log::warning('PERINGATAN: Gateway pembayaran berjalan dalam mode simulasi (simulated) di lingkungan produksi!');
        }
    }
}
