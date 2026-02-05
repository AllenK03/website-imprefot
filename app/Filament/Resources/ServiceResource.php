<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceResource\Pages;
use App\Filament\Resources\ServiceResource\RelationManagers;
use App\Models\Service;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ServiceResource extends Resource
{
    protected static ?string $model = Service::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Información General')
            ->description('Detalles principales del Servicio')
            ->schema([
                // Quitamos el Grid y el Slug para evitar errores de BD
                \Filament\Forms\Components\TextInput::make('name')
                    ->label('Nombre del Servicio')
                    ->placeholder('Ej: Impresión de Gigantografías')
                    ->required()
                    ->maxLength(255),
                
                \Filament\Forms\Components\Textarea::make('description')
                    ->label('Descripción')
                    ->placeholder('Describe qué incluye este servicio...')
                    ->rows(3)
                    ->columnSpanFull(),
            ]),

            Section::make('Estado y Multimedia')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            \Filament\Forms\Components\FileUpload::make('image')
                                ->label('Imagen del Servicio')
                                ->image()
                                ->imageEditor() // Permite recortar la foto si queda mal
                                ->directory('services') // Carpeta organizada
                                ->required(),

                            \Filament\Forms\Components\Toggle::make('is_active')
                                ->label('¿Mostrar en la web?')
                                ->helperText('Si se desactiva, los clientes no podrán verlo.')
                                ->default(true)
                                ->inline(false),
                        ]),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
            \Filament\Tables\Columns\ImageColumn::make('image')
                ->label('Imagen')
                ->circular(), // Se ve más moderno en círculos para servicios

            \Filament\Tables\Columns\TextColumn::make('name')
                ->label('Nombre')
                ->searchable()
                ->sortable()
                ->weight('bold'),

            \Filament\Tables\Columns\TextColumn::make('description')
                ->label('Descripción')
                ->limit(50) // Solo muestra los primeros 50 caracteres
                ->color('gray'),

            \Filament\Tables\Columns\IconColumn::make('is_active')
                ->label('Estado')
                ->boolean()
                ->sortable(),
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
            'index' => Pages\ListServices::route('/'),
            'create' => Pages\CreateService::route('/create'),
            'edit' => Pages\EditService::route('/{record}/edit'),
        ];
    }
}
