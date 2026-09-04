<?php

namespace App\Providers;

use App\Models\InventoryMovementItem;
use App\Observers\InventoryMovementItemObserver;
use Illuminate\Support\ServiceProvider;

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
        // Activa el Observer para el cálculo atómico de stock
        InventoryMovementItem::observe(InventoryMovementItemObserver::class);
    }
}
