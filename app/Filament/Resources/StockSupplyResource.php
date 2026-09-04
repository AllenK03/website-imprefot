<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockSupplyResource\Pages;
use App\Models\InventoryMovement;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class StockSupplyResource extends Resource
{
    protected static ?string $model = InventoryMovement::class;

    protected static ?string $navigationGroup = 'Gestión de Inventario';
    protected static ?string $navigationLabel = 'Abastecimiento';
    protected static ?string $modelLabel = 'Abastecimiento';
    protected static ?string $pluralModelLabel = 'Abastecimientos';
    protected static ?string $navigationIcon = 'heroicon-o-arrow-down-tray';
    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('type', 'abastecimiento');
    }

    public static function form(Form $form): Form
    {
        // Función auxiliar para recalcular el Total en tiempo real
        $updateTotal = function (Forms\Get $get, Forms\Set $set) {
            $items = $get('items') ?? [];
            $total = 0;

            foreach ($items as $item) {
                $quantity = (float) ($item['quantity'] ?? 0);
                $price = (float) ($item['price'] ?? 0);
                $total += ($quantity * $price);
            }

            $set('total_amount', number_format($total, 2, '.', ''));
        };

        return $form
            ->schema([
                Forms\Components\Hidden::make('type')->default('abastecimiento'),
                Forms\Components\Hidden::make('user_id')->default(fn () => Auth::id()),

                Forms\Components\Section::make('Datos de Recepción')
                    ->schema([
                        Forms\Components\TextInput::make('reason')
                            ->label('Observaciones')
                            ->placeholder('Ej: Entrada de mercancía lote #105')
                            ->dehydrateStateUsing(fn ($state) => blank($state) ? 'Sin observaciones' : $state)
                            ->columnSpan(2),

                        Forms\Components\TextInput::make('total_amount')
                            ->label('Monto Total Abastecimiento ($)')
                            ->prefix('$')
                            ->numeric()
                            ->readOnly()
                            ->default(0.00)
                            ->dehydrated() // Asegura que se envíe al guardar
                            ->columnSpan(1),
                    ])->columns(3),

                Forms\Components\Section::make('Productos Recibidos')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship()
                            ->addActionLabel('Agregar otro Producto')
                            ->live()
                            ->afterStateUpdated($updateTotal)
                            ->schema([
                                Forms\Components\Select::make('product_id')
                                    ->label('Producto')
                                    ->relationship('product', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->distinct()
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) use ($updateTotal) {
                                        if ($state) {
                                            $product = Product::find($state);
                                            if ($product) {
                                                $set('price', $product->price);
                                            }
                                        }
                                        $updateTotal($get, $set);
                                    })
                                    ->createOptionForm([
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('name')
                                                    ->label('Nombre')
                                                    ->required()
                                                    ->live(onBlur: true)
                                                    ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))),

                                                Forms\Components\TextInput::make('slug')
                                                    ->label('Enlace (URL)')
                                                    ->required()
                                                    ->disabled()
                                                    ->dehydrated(),
                                            ]),

                                        Forms\Components\Select::make('category_id')
                                            ->label('Rubro / Categoría')
                                            ->relationship('category', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->createOptionForm([
                                                Forms\Components\TextInput::make('name')
                                                    ->label('Nombre del nuevo Rubro')
                                                    ->required()
                                                    ->unique('categories', 'name'),
                                            ]),

                                        Forms\Components\Textarea::make('description')
                                            ->label('Descripción')
                                            ->columnSpanFull(),

                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('price')
                                                    ->label('Precio Venta')
                                                    ->numeric()
                                                    ->prefix('$')
                                                    ->required(),

                                                Forms\Components\Toggle::make('is_active')
                                                    ->label('¿Publicado?')
                                                    ->inline(false)
                                                    ->default(true),
                                            ]),

                                        Forms\Components\FileUpload::make('image')
                                            ->label('Imagen del Producto')
                                            ->image()
                                            ->directory('products')
                                            ->required()
                                            ->columnSpanFull(),

                                        Forms\Components\Hidden::make('stock')
                                            ->default(0),
                                    ]),

                                Forms\Components\TextInput::make('quantity')
                                    ->label('Cantidad Ingresada')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1)
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated($updateTotal),

                                Forms\Components\TextInput::make('price')
                                    ->label('Precio Unitario ($)')
                                    ->numeric()
                                    ->prefix('$')
                                    ->required()
                                    ->dehydrated()
                                    ->default(0.00)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated($updateTotal),
                            ])
                            ->columns(3)
                            ->minItems(1),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('reason')
                    ->label('Motivo / Observación')
                    ->searchable()
                    ->default('Sin observaciones'),
                Tables\Columns\TextColumn::make('user.name')->label('Registrado Por'),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Monto Total')
                    ->money('USD')
                    ->sortable(),
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
            'index'  => Pages\ListStockSupplies::route('/'),
            'create' => Pages\CreateStockSupply::route('/create'),
            'view'   => Pages\ViewStockSupply::route('/{record}'),
        ];
    }
}