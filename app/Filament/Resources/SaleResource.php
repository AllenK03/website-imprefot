<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SaleResource\Pages;
use App\Models\Client;
use App\Models\InventoryMovement;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class SaleResource extends Resource
{
    protected static ?string $model = InventoryMovement::class;

    protected static ?string $navigationGroup = 'Gestión de Inventario';
    protected static ?string $navigationLabel = 'Ventas';
    protected static ?string $modelLabel = 'Venta';
    protected static ?string $pluralModelLabel = 'Ventas';
    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('type', 'venta');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('type')->default('venta'),
                Forms\Components\Hidden::make('user_id')->default(fn () => Auth::id()),

                Forms\Components\Section::make('Datos de la Venta')
                    ->schema([
                        Forms\Components\Select::make('client_id')
                            ->label('Cliente')
                            ->relationship('client', 'name')
                            ->getOptionLabelFromRecordUsing(fn (Client $record) => "{$record->name} ({$record->cedula})")
                            ->searchable(['name', 'cedula'])
                            ->preload()
                            ->required()
                            ->createOptionForm(ClientResource::getFormSchema()),

                        Forms\Components\TextInput::make('reason')
                            ->label('Observación')
                            ->placeholder('Ej: Pedido #1024 - Pago Móvil')
                            ->dehydrateStateUsing(fn ($state) => blank($state) ? 'Sin observaciones' : $state),
                    ])->columns(2),

                Forms\Components\Section::make('Detalle de Productos')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship()
                            ->addActionLabel('Agregar otro Producto')
                            ->schema([
                                Forms\Components\Select::make('product_id')
                                    ->label('Producto / Repuesto')
                                    ->options(Product::pluck('name', 'id'))
                                    ->searchable()
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                        $product = Product::find($state);
                                        if ($product) {
                                            $set('price', $product->price);
                                        }
                                        static::updateTotalAmount($get, $set);
                                    }),

                                Forms\Components\TextInput::make('quantity')
                                    ->label('Cantidad')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1)
                                    ->maxValue(function (Forms\Get $get) {
                                        $productId = $get('product_id');
                                        if (! $productId) {
                                            return 1;
                                        }
                                        $product = Product::find($productId);
                                        return $product ? $product->stock : 1;
                                    })
                                    ->helperText(function (Forms\Get $get) {
                                        $productId = $get('product_id');
                                        if (! $productId) {
                                            return null;
                                        }
                                        $product = Product::find($productId);
                                        return $product ? "Stock: {$product->stock} unid." : null;
                                    })
                                    ->validationMessages([
                                        'max' => 'La cantidad supera el stock disponible (:max unidades).',
                                    ])
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set) => static::updateTotalAmount($get, $set)),

                                Forms\Components\TextInput::make('price')
                                    ->label('Precio Unitario ($)')
                                    ->numeric()
                                    ->prefix('$')
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set) => static::updateTotalAmount($get, $set)),
                            ])
                            ->columns(3)
                            ->minItems(1)
                            ->live()
                            ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set) => static::updateTotalAmount($get, $set)),

                        Forms\Components\TextInput::make('total_amount')
                            ->label('Monto Total ($)')
                            ->numeric()
                            ->prefix('$')
                            ->readOnly()
                            ->default(0.00),
                    ]),
            ]);
    }

    protected static function updateTotalAmount(Forms\Get $get, Forms\Set $set): void
    {
        $items = $get('items') ?? [];
        $total = array_reduce($items, function ($sum, $item) {
            $qty = (float) ($item['quantity'] ?? 0);
            $price = (float) ($item['price'] ?? 0);
            return $sum + ($qty * $price);
        }, 0);

        $set('total_amount', $total);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('client.name')->label('Cliente')->searchable(),
                Tables\Columns\TextColumn::make('client.cedula')->label('RIF / Cédula')->searchable(),
                Tables\Columns\TextColumn::make('reason')->label('Observaciones')->limit(30),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('USD')
                    ->sortable()
                    // Sumatoria global o filtrada de la columna al pie de la tabla
                    ->summarize(
                        Tables\Columns\Summarizers\Sum::make()
                            ->label('Total General')
                            ->money('USD')
                    ),
                Tables\Columns\TextColumn::make('created_at')->label('Fecha')->dateTime('d/m/Y h:i A')->sortable(),
            ])
            ->filters([
                // 1. Filtro por Rango de Fechas (Desde - Hasta)
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Desde')
                            ->placeholder('Fecha inicio'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Hasta')
                            ->placeholder('Fecha fin'),
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
                    }),

                // 2. Filtro por Cliente específico
                Tables\Filters\SelectFilter::make('client_id')
                    ->label('Cliente')
                    ->relationship('client', 'name')
                    ->searchable()
                    ->preload(),

                // 3. Filtro por Ventas de Alto Valor / Clientes que más gastan
                Tables\Filters\Filter::make('high_value_sales')
                    ->form([
                        Forms\Components\TextInput::make('min_amount')
                            ->label('Monto mínimo de venta ($)')
                            ->numeric()
                            ->placeholder('Ej: 50'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['min_amount'],
                            fn (Builder $query, $amount): Builder => $query->where('total_amount', '>=', $amount),
                        );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('whatsapp')
                    ->label('WhatsApp')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('success')
                    ->visible(fn (InventoryMovement $record): bool => !empty($record->client?->phone))
                    ->url(
                        fn (InventoryMovement $record): string => static::getWhatsAppUrl($record),
                        shouldOpenInNewTab: true
                    )
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([]);
    }

    /**
     * Genera la URL de WhatsApp con la nota de entrega formateada
     */
    public static function getWhatsAppUrl(InventoryMovement $record): string
    {
        $client = $record->client;

        if (!$client || empty($client->phone)) {
            return '#';
        }

        // Normalizar número telefónico
        $phone = preg_replace('/[^0-9]/', '', $client->phone);
        if (str_starts_with($phone, '0')) {
            $phone = '58' . substr($phone, 1);
        }

        // Cargar ítems y productos asociados
        $record->loadMissing('items.product');

        $itemsList = "";
        foreach ($record->items as $item) {
            $productName = $item->product->name ?? 'Producto';
            $qty = $item->quantity;
            $subtotal = number_format($item->price * $item->quantity, 2);
            $itemsList .= "• {$productName} x{$qty} — *\${$subtotal}*\n";
        }

        $totalFormatted = number_format($record->total_amount, 2);

        // Construcción del mensaje sin emojis propensos a errores de encoding
        $message = "Muchas gracias por su compra Sr(a) *{$client->name}*.\n\n"
            . "La lista de sus productos es:\n\n"
            . $itemsList . "\n"
            . "*Monto Total:* *\${$totalFormatted}*\n\n"
            . "Le invitamos a visitar nuestra tienda online para que pueda hacer sus compras y solicitar nuestros servicios desde la comodidad de su hogar:\n"
            . "imprefot.com";

        return "https://wa.me/{$phone}?text=" . urlencode($message);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSales::route('/'),
            'create' => Pages\CreateSale::route('/create'),
            'view'   => Pages\ViewSale::route('/{record}'),
        ];
    }
}