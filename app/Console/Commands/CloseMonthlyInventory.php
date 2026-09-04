<?php

namespace App\Console\Commands;

use App\Models\MonthlyInventory;
use App\Models\Product;
use Illuminate\Console\Command;

class CloseMonthlyInventory extends Command
{
    protected $signature = 'inventory:close-month';
    protected $description = 'Genera la fotografía congelada del inventario al cierre de mes';

    public function handle(): int
    {
        $year = (int) now()->format('Y');
        $month = (int) now()->format('m');

        if (MonthlyInventory::where('year', $year)->where('month', $month)->exists()) {
            $this->warn("El cierre para el periodo {$month}/{$year} ya existe.");
            return Command::SUCCESS;
        }

        $products = Product::all(['id', 'name', 'price', 'stock']);

        $snapshot = $products->map(fn ($product) => [
            'id'    => $product->id,
            'name'  => $product->name,
            'price' => $product->price,
            'stock' => $product->stock,
            'total' => $product->stock * $product->price,
        ])->toArray();

        MonthlyInventory::create([
            'year'              => $year,
            'month'             => $month,
            'snapshot_data'     => $snapshot,
            'total_products'    => $products->count(),
            'total_stock'       => $products->sum('stock'),
            'total_value'       => $products->sum(fn ($p) => $p->stock * $p->price),
            'closed_by_user_id' => null, // Registrado de forma automática por el sistema
        ]);

        $this->info("Cierre de inventario {$month}/{$year} generado con éxito.");
        return Command::SUCCESS;
    }
}