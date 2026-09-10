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

        if ($record->client?->phone) {
            $url = SaleResource::getWhatsAppUrl($record);

            // Cambiamos '_blank' por un nombre de ventana fijo ('whatsapp')
            $this->js("window.open('{$url}', 'whatsapp')");
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}