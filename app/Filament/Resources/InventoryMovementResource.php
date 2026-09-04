<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InventoryMovementResource\Pages;
use App\Models\InventoryMovement;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class InventoryMovementResource extends Resource
{
    protected static ?string $model = InventoryMovement::class;

    protected static ?string $navigationGroup = 'Gestión de Inventario';
    protected static ?string $navigationLabel = 'Registro de Movimientos';
    protected static ?string $modelLabel = 'Registro de Movimiento';
    protected static ?string $pluralModelLabel = 'Registros de Movimientos';
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?int $navigationSort = 3;

    // Deshabilitamos la creación, edición y eliminación manual
    public static function canCreate(): bool { return false; }
    public static function canEdit(Model $record): bool { return false; }
    public static function canDelete(Model $record): bool { return false; }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Movimiento')
                    ->description('Datos generales de la transacción')
                    ->schema([
                        Forms\Components\TextInput::make('id')
                            ->label('ID Movimiento')
                            ->disabled(),

                        Forms\Components\TextInput::make('type')
                            ->label('Tipo de Movimiento')
                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                'abastecimiento' => 'Entrada (Abastecimiento)',
                                'venta'          => 'Salida (Venta)',
                                'ajuste_manual'  => 'Ajuste Manual',
                                default          => $state ?? '-',
                            })
                            ->disabled(),

                        Forms\Components\DateTimePicker::make('created_at')
                            ->label('Fecha y Hora')
                            ->disabled(),

                        Forms\Components\Select::make('user_id')
                            ->label('Usuario / Operador')
                            ->relationship('user', 'name')
                            ->placeholder('Sistema')
                            ->disabled(),

                        Forms\Components\Select::make('client_id')
                            ->label('Cliente')
                            ->relationship('client', 'name')
                            ->placeholder('N/A (Sin cliente registrado)')
                            ->disabled(),

                        Forms\Components\TextInput::make('total_amount')
                            ->label('Monto Total ($)')
                            ->prefix('$')
                            ->numeric()
                            ->disabled(),

                        Forms\Components\Textarea::make('reason')
                            ->label('Motivo / Observación')
                            ->disabled()
                            ->columnSpanFull(),
                    ])->columns(3),

                Forms\Components\Section::make('Productos Involucrados')
                    ->description('Detalle de ítems, cantidades y precios registrados en este movimiento')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship('items')
                            ->label('')
                            ->schema([
                                Forms\Components\Select::make('product_id')
                                    ->label('Producto')
                                    ->relationship('product', 'name')
                                    ->disabled()
                                    ->columnSpan(2),

                                Forms\Components\TextInput::make('quantity')
                                    ->label('Cantidad')
                                    ->numeric()
                                    ->disabled()
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('price')
                                    ->label('Precio/Costo Unit. ($)')
                                    ->prefix('$')
                                    ->numeric()
                                    ->disabled()
                                    ->columnSpan(1),
                            ])
                            ->columns(4)
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->disabled(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'abastecimiento' => 'success',
                        'venta'          => 'info',
                        'ajuste_manual'  => 'warning',
                        default          => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'abastecimiento' => 'Entrada (Abastecimiento)',
                        'venta'          => 'Salida (Venta)',
                        'ajuste_manual'  => 'Ajuste Manual',
                        default          => $state,
                    }),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Operador')
                    ->sortable()
                    ->placeholder('Sistema'),

                Tables\Columns\TextColumn::make('client.name')
                    ->label('Cliente')
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('reason')
                    ->label('Motivo / Observación')
                    ->limit(35)
                    ->tooltip(fn (Model $record): ?string => $record->reason),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Monto Total')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y h:i A')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipo de Movimiento')
                    ->options([
                        'abastecimiento' => 'Entrada (Abastecimiento)',
                        'venta'          => 'Salida (Venta)',
                        'ajuste_manual'  => 'Ajuste Manual',
                    ])
                    ->native(),

                // Filtro por Rango de Fechas (Desde / Hasta)
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Desde'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['created_from'] ?? null) {
                            $indicators['created_from'] = 'Desde: ' . Carbon::parse($data['created_from'])->format('d/m/Y');
                        }
                        if ($data['created_until'] ?? null) {
                            $indicators['created_until'] = 'Hasta: ' . Carbon::parse($data['created_until'])->format('d/m/Y');
                        }
                        return $indicators;
                    }),
            ])
            ->headerActions([
                Action::make('exportPdf')
                    ->label('Exportar PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('danger')
                    ->action(function ($livewire) {
                        // Obtenemos la consulta con los filtros activos directamente desde Livewire
                        $movements = $livewire->getFilteredTableQuery()
                            ->with(['user', 'client'])
                            ->get();

                        $pdf = Pdf::loadView('pdf.inventory-movements', [
                            'movements'   => $movements,
                            'generatedAt' => now()->format('d/m/Y h:i A'),
                        ]);

                        return response()->streamDownload(
                            fn () => print($pdf->output()),
                            'reporte-movimientos-' . now()->format('Y-m-d-His') . '.pdf'
                        );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInventoryMovements::route('/'),
            'view'  => Pages\ViewInventoryMovement::route('/{record}'),
        ];
    }
}