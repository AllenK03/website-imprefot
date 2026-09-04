<?php

namespace App\Filament\Resources\StockSupplyResource\Pages;

use App\Filament\Resources\StockSupplyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStockSupply extends EditRecord
{
    protected static string $resource = StockSupplyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
