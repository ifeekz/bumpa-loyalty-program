<?php

namespace App\Providers;

use App\Domain\Loyalty\Services\Payment\PaymentProviderInterface;
use App\Domain\Loyalty\Services\Payment\PaystackService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind the payment interface to the Paystack concrete implementation.
        // To swap providers (e.g. Flutterwave), change only this line.
        $this->app->bind(PaymentProviderInterface::class, PaystackService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);
    }
}
