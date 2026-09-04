<?php

namespace App\Filament\Resources\StockSupplyResource\Pages;

use App\Filament\Resources\StockSupplyResource;
use Filament\Resources\Pages\CreateRecord;

class CreateStockSupply extends CreateRecord
{
    protected static string $resource = StockSupplyResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Obtenemos el estado crudo del formulario antes de que Filament filtre las relaciones
        $rawState = $this->form->getRawState();
        $items = $rawState['items'] ?? [];
        $total = 0;

        foreach ($items as $item) {
            $qty = (float) ($item['quantity'] ?? 0);
            $price = (float) ($item['price'] ?? 0);
            $total += ($qty * $price);
        }

        $data['total_amount'] = $total;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}