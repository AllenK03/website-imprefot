<?php

namespace App\Observers;

use App\Models\InventoryMovementItem;
use Illuminate\Support\Facades\DB;

class InventoryMovementItemObserver
{
    /**
     * Se ejecuta automáticamente al insertar un ítem de movimiento.
     */
    public function created(InventoryMovementItem $item): void
    {
        DB::transaction(function () use ($item) {
            $product = $item->product;
            $movementType = $item->movement->type; // 'abastecimiento', 'venta', 'ajuste_manual'

            match ($movementType) {
                // Suma stock al catálogo
                'abastecimiento' => $product->increment('stock', $item->quantity),

                // Resta stock del catálogo
                'venta'          => $product->decrement('stock', $item->quantity),

                // Reemplaza el stock con el conteo físico exacto
                'ajuste_manual'  => $product->update(['stock' => $item->quantity]),

                default          => null,
            };
        });
    }

    /**
     * Revierte el stock si se borra un ítem de movimiento.
     */
    public function deleted(InventoryMovementItem $item): void
    {
        DB::transaction(function () use ($item) {
            $product = $item->product;
            $movementType = $item->movement->type;

            match ($movementType) {
                'abastecimiento' => $product->decrement('stock', $item->quantity),
                'venta'          => $product->increment('stock', $item->quantity),
                default          => null,
            };
        });
    }
}