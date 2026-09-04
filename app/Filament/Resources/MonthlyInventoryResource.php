<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MonthlyInventoryResource\Pages;
use App\Models\MonthlyInventory;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class MonthlyInventoryResource extends Resource
{
    protected static ?string $model = MonthlyInventory::class;

    protected static ?string $navigationGroup = 'Gestión de Inventario';
    protected static ?string $navigationLabel = 'Cierres Mensuales';
    protected static ?string $modelLabel = 'Cierre Mensual';
    protected static ?string $pluralModelLabel = 'Cierres Mensuales';
    protected static ?string $navigationIcon = 'heroicon-o-lock-closed';
    protected static ?int $navigationSort = 4;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Resumen del Cierre')
                    ->schema([
                        Forms\Components\TextInput::make('period')
                            ->label('Periodo')
                            ->formatStateUsing(fn ($record) => "{$record->month}/{$record->year}")
                            ->disabled(),

                        Forms\Components\TextInput::make('total_products')
                            ->label('Tipos de Producto')
                            ->disabled(),

                        Forms\Components\TextInput::make('total_stock')
                            ->label('Unidades Físicas Totales')
                            ->disabled(),

                        Forms\Components\TextInput::make('total_value')
                            ->label('Valorización ($)')
                            ->prefix('$')
                            ->disabled(),
                    ])->columns(3),

                Forms\Components\Section::make('Fotografía / Snapshot de Productos')
                    ->schema([
                        Forms\Components\Repeater::make('snapshot_data')
                            ->label('Estado de Productos al Cierre')
                            ->schema([
                                Forms\Components\TextInput::make('name')->label('Producto')->disabled(),
                                Forms\Components\TextInput::make('stock')->label('Stock Congelado')->disabled(),
                                Forms\Components\TextInput::make('price')->label('Precio Unitario ($)')->prefix('$')->disabled(),
                                Forms\Components\TextInput::make('total')->label('Valor Total ($)')->prefix('$')->disabled(),
                            ])
                            ->columns(4)
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('period')
                    ->label('Periodo')
                    ->state(fn ($record) => str_pad($record->month, 2, '0', STR_PAD_LEFT) . '/' . $record->year)
                    ->sortable(['year', 'month']),

                Tables\Columns\TextColumn::make('total_products')
                    ->label('Cant. Productos')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_stock')
                    ->label('Stock Físico Total')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_value')
                    ->label('Valor del Inventario')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('closedBy.name')
                    ->label('Cerrado Por')
                    ->placeholder('Sistema'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha Cierre')
                    ->dateTime('d/m/Y h:i A')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make(),
                Action::make('downloadPdf')
                    ->label('PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('danger')
                    ->action(function (MonthlyInventory $record) {
                        $pdf = Pdf::loadView('pdf.monthly-inventory', [
                            'record' => $record,
                        ]);

                        $period = str_pad($record->month, 2, '0', STR_PAD_LEFT) . '-' . $record->year;

                        return response()->streamDownload(
                            fn () => print($pdf->output()),
                            "cierre-inventario-{$period}.pdf"
                        );
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMonthlyInventories::route('/'),
            'view'  => Pages\ViewMonthlyInventory::route('/{record}'),
        ];
    }
}