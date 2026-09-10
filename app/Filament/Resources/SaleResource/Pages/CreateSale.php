<?php

namespace App\Filament\Resources\SaleResource\Pages;

use App\Filament\Resources\SaleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSale extends CreateRecord
{
    protected static string $resource = SaleResource::class;

    protected function afterCreate(): void
    {
        $record = $this->record;

        // Abrir WhatsApp en una NUEVA pestaña si el cliente tiene teléfono
        if ($record->client?->phone) {
            $url = SaleResource::getWhatsAppUrl($record);
            $this->js("window.open('{$url}', '_blank')");
        }
    }

    protected function getRedirectUrl(): string
    {
        // Mantener el sistema abierto en el listado de ventas
        return $this->getResource()::getUrl('index');
    }
}