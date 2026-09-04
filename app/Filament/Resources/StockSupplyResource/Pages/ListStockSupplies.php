<?php

namespace App\Filament\Resources\StockSupplyResource\Pages;

use App\Filament\Resources\StockSupplyResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStockSupplies extends ListRecords
{
    protected static string $resource = StockSupplyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
