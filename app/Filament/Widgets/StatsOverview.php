<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class StatsOverview extends BaseWidget
{
    protected ?string $heading = 'Resumen de Inventario';
    protected function getStats(): array
    {
        return [
            Stat::make('Total Productos', Product::count())
                ->description('Productos en el catálogo')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('primary'),

            Stat::make('Sin Existencia', Product::where('stock', 0)->count())
                ->description('Requieren reposición')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('warning'),

            Stat::make('Valor del Inventario', '$' . number_format(Product::sum(DB::raw('price * stock')), 2))
                ->description('Capital total en mercancía')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),
        ];
    }
}