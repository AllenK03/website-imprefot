<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SaleResource\Pages;
use App\Models\Client;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Service;
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

                Forms\Components\Section::make('Detalle de la Venta')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship()
                            ->addActionLabel('Agregar ítem (Producto o Servicio)')
                            ->schema([
                                // Selector Tipo de Ítem
                                Forms\Components\Select::make('item_type')
                                    ->label('Tipo')
                                    ->options([
                                        'product' => 'Producto',
                                        'service' => 'Servicio',
                                    ])
                                    ->default('product')
                                    ->required()
                                    ->live()
                                    ->dehydrated(false)
                                    ->afterStateUpdated(function (Forms\Set $set) {
                                        $set('product_id', null);
                                        $set('service_id', null);
                                        $set('price', 0);
                                    })
                                    ->afterStateHydrated(function (Forms\Components\Select $component, $record) {
                                        if ($record) {
                                            $component->state($record->service_id ? 'service' : 'product');
                                        }
                                    }),

                                // Select Producto (visible solo si Tipo == product)
                                Forms\Components\Select::make('product_id')
                                    ->label('Producto')
                                    ->options(Product::pluck('name', 'id'))
                                    ->searchable()
                                    ->required(fn (Forms\Get $get) => $get('item_type') === 'product')
                                    ->visible(fn (Forms\Get $get) => $get('item_type') === 'product')
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                        $product = Product::find($state);
                                        if ($product) {
                                            $set('price', $product->price);
                                        }
                                        static::updateTotalAmount($get, $set);
                                    }),

                                // Select Servicio (visible solo si Tipo == service)
                                Forms\Components\Select::make('service_id')
                                    ->label('Servicio')
                                    ->options(Service::where('is_active', true)->pluck('name', 'id'))
                                    ->searchable()
                                    ->required(fn (Forms\Get $get) => $get('item_type') === 'service')
                                    ->visible(fn (Forms\Get $get) => $get('item_type') === 'service')
                                    ->live(),

                                Forms\Components\TextInput::make('quantity')
                                    ->label('Cantidad')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1)
                                    ->maxValue(function (Forms\Get $get) {
                                        if ($get('item_type') === 'service') {
                                            return null; // Sin límite de stock para servicios
                                        }
                                        $productId = $get('product_id');
                                        if (! $productId) {
                                            return 1;
                                        }
                                        $product = Product::find($productId);
                                        return $product ? $product->stock : 1;
                                    })
                                    ->helperText(function (Forms\Get $get) {
                                        if ($get('item_type') === 'service') {
                                            return 'Servicio (no descuenta stock)';
                                        }
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
                            ->columns(4)
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
                    ->summarize(
                        Tables\Columns\Summarizers\Sum::make()
                            ->label('Total General')
                            ->money('USD')
                    ),
                Tables\Columns\TextColumn::make('created_at')->label('Fecha')->dateTime('d/m/Y h:i A')->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')->label('Desde'),
                        Forms\Components\DatePicker::make('created_until')->label('Hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['created_from'], fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date))
                            ->when($data['created_until'], fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date));
                    }),

                Tables\Filters\SelectFilter::make('client_id')
                    ->label('Cliente')
                    ->relationship('client', 'name')
                    ->searchable()
                    ->preload(),
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

    public static function getWhatsAppUrl(InventoryMovement $record): string
    {
        $client = $record->client;

        if (!$client || empty($client->phone)) {
            return '#';
        }

        $phone = preg_replace('/[^0-9]/', '', $client->phone);
        if (str_starts_with($phone, '0')) {
            $phone = '58' . substr($phone, 1);
        }

        $record->loadMissing(['items.product', 'items.service']);

        $itemsList = "";
        foreach ($record->items as $item) {
            $name = $item->product?->name ?? $item->service?->name ?? 'Detalle';
            $qty = $item->quantity;
            $subtotal = number_format($item->price * $item->quantity, 2);
            $itemsList .= "• {$name} x{$qty} — *\${$subtotal}*\n";
        }

        $totalFormatted = number_format($record->total_amount, 2);

        $message = "Muchas gracias por su compra Sr(a) *{$client->name}*.\n\n"
            . "El detalle de su consumo es:\n\n"
            . $itemsList . "\n"
            . "*Monto Total:* *\${$totalFormatted}*\n\n"
            . "Le invitamos a visitar nuestra tienda online:\n"
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