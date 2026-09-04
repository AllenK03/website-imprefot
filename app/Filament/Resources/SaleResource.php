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

    /**
     * Reusable total calculation function for repeater items
     */
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
                Tables\Columns\TextColumn::make('total_amount')->label('Total')->money('USD')->sortable(),
                Tables\Columns\TextColumn::make('created_at')->label('Fecha')->dateTime('d/m/Y h:i A')->sortable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
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