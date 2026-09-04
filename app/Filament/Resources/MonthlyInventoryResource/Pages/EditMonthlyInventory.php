<?php

namespace App\Filament\Resources\MonthlyInventoryResource\Pages;

use App\Filament\Resources\MonthlyInventoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMonthlyInventory extends EditRecord
{
    protected static string $resource = MonthlyInventoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
