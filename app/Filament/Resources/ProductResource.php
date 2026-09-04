<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\InventoryMovement;
use App\Models\Product;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Productos';
    protected static ?string $modelLabel = 'Producto';
    protected static ?string $pluralModelLabel = 'Productos';
    protected static ?string $navigationGroup = 'Gestión de Inventario';
    protected static ?int $navigationSort = 0;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Información General')
                    ->description('Detalles principales del producto')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Nombre')
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))),

                                TextInput::make('slug')
                                    ->label('Enlace (URL)')
                                    ->required()
                                    ->disabled()
                                    ->dehydrated(),
                            ]),

                        Select::make('category_id')
                            ->label('Rubro / Categoría')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->label('Nombre del nuevo Rubro')
                                    ->required()
                                    ->unique('categories', 'name'),
                            ]),

                        Forms\Components\Textarea::make('description')
                            ->label('Descripción')
                            ->columnSpanFull(),
                    ]),

                Section::make('Precio e Inventario')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('price')
                                    ->label('Precio')
                                    ->numeric()
                                    ->prefix('$')
                                    ->required(),

                                TextInput::make('stock')
                                    ->label('Stock Inicial')
                                    ->numeric()
                                    ->default(0)
                                    ->required()
                                    ->hidden(fn (string $operation): bool => $operation === 'edit'),

                                Forms\Components\Toggle::make('is_active')
                                    ->label('¿Publicado?')
                                    ->inline(false)
                                    ->default(true),
                            ]),
                    ]),

                Section::make('Multimedia')
                    ->schema([
                        Forms\Components\FileUpload::make('image')
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
                Tables\Columns\ImageColumn::make('image')
                    ->label('Imagen'),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('price')
                    ->label('Precio')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('stock')
                    ->label('Existencia')
                    ->numeric()
                    ->sortable()
                    ->color(fn (int $state): string => $state < 5 ? 'danger' : 'success'),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Rubro')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Publicado')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Estado de publicación')
                    ->placeholder('Todos')
                    ->trueLabel('Solo Publicados')
                    ->falseLabel('Ocultos'),
            ])
            ->headerActions([
                Action::make('downloadPdf')
                    ->label('Exportar PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('danger')
                    ->action(function () {
                        $products = Product::with('category')->get();

                        $pdf = Pdf::loadView('pdf.inventory', [
                            'products'    => $products,
                            'generatedAt' => now()->format('d/m/Y h:i A'),
                        ]);

                        return response()->streamDownload(
                            fn () => print($pdf->output()),
                            'inventario-actual-' . now()->format('Y-m-d') . '.pdf'
                        );
                    }),
            ])
            ->actions([
                Action::make('adjustStock')
                    ->label('Ajustar Stock')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->color('warning')
                    ->form([
                        TextInput::make('current_stock')
                            ->label('Stock Actual en Sistema')
                            ->default(fn ($record) => $record->stock)
                            ->disabled(),

                        TextInput::make('new_stock')
                            ->label('Nuevo Stock Real (Conteo Físico)')
                            ->numeric()
                            ->required()
                            ->minValue(0),

                        Textarea::make('reason')
                            ->label('Motivo del Ajuste Obligatorio')
                            ->placeholder('Ej: Pérdida por empaque dañado, conteo físico de fin de mes...')
                            ->required(),
                    ])
                    ->action(function ($record, array $data): void {
                        $difference = $data['new_stock'] - $record->stock;

                        if ($difference === 0) {
                            return;
                        }

                        DB::transaction(function () use ($record, $data, $difference) {
                            $movement = InventoryMovement::create([
                                'type'         => 'ajuste_manual',
                                'user_id'      => Auth::id(),
                                'reason'       => $data['reason'],
                                'total_amount' => 0,
                            ]);

                            $movement->items()->create([
                                'product_id' => $record->id,
                                'quantity'   => $difference,
                                'price'      => $record->price,
                            ]);

                            $record->update(['stock' => $data['new_stock']]);
                        });
                    }),

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
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit'   => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}