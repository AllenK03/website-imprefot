<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Productos';
    protected static ?string $modelLabel = 'Producto';
    protected static ?string $pluralModelLabel = 'Productos';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Información General')
                ->description('Detalles principales del producto')
                ->schema([
                    Grid::make(2) // Divide en 2 columnas
                        ->schema([
                            \Filament\Forms\Components\TextInput::make('name')
                                ->label('Nombre')
                                ->required()
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn ($state, callable $set) => $set('slug', \Illuminate\Support\Str::slug($state))),

                            \Filament\Forms\Components\TextInput::make('slug')
                                ->label('Enlace (URL)')
                                ->required()
                                ->disabled() // Evitamos que lo editen por error
                                ->dehydrated(), 
                        ]),
                    
                    \Filament\Forms\Components\Textarea::make('description')
                        ->label('Descripción')
                        ->columnSpanFull(),
                ]),

            Section::make('Inventario y Precio')
                ->schema([
                    Grid::make(3) // Divide en 3 columnas
                        ->schema([
                            \Filament\Forms\Components\TextInput::make('price')
                                ->label('Precio')
                                ->numeric()
                                ->prefix('$')
                                ->required(),

                            \Filament\Forms\Components\TextInput::make('stock')
                                ->label('Existencia')
                                ->numeric()
                                ->required(),

                            \Filament\Forms\Components\Toggle::make('is_active')
                                ->label('¿Publicado?')
                                ->inline(false)
                                ->default(true),
                        ]),
                ]),

            Section::make('Multimedia')
                ->schema([
                    \Filament\Forms\Components\FileUpload::make('image')
                        ->label('Imagen del Producto')
                        ->image()
                        ->directory('products')
                        ->required(),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\ImageColumn::make('image')
                    ->label('Imagen'),

                \Filament\Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                // Precio formateado. Usamos 'money' pero puedes usar 'numeric' si prefieres algo simple
                \Filament\Tables\Columns\TextColumn::make('price')
                    ->label('Precio')
                    ->money('USD') // Cambia a la moneda que necesites
                    ->sortable(),

                // Existencia con color dinámico (Rojo si hay poco, Verde si hay suficiente)
                \Filament\Tables\Columns\TextColumn::make('stock')
                    ->label('Existencia')
                    ->numeric()
                    ->sortable()
                    ->color(fn (int $state): string => $state < 5 ? 'danger' : 'success'),

                // Un icono que indica si está activo o no
                \Filament\Tables\Columns\IconColumn::make('is_active')
                    ->label('Publicado')
                    ->boolean(),
            ])
            ->filters([
                \Filament\Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Estado de publicación')
                    ->placeholder('Todos')
                    ->trueLabel('Solo Publicados')
                    ->falseLabel('Ocultos'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
