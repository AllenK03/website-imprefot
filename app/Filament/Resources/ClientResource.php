<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClientResource\Pages;
use App\Models\Client;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;

    protected static ?string $navigationLabel = 'Clientes';
    protected static ?string $modelLabel = 'Cliente';
    protected static ?string $pluralModelLabel = 'Clientes';
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?int $navigationSort = 2;

    /**
     * Esquema reutilizable para el formulario de Cliente
     */
    public static function getFormSchema(): array
    {
        $updateCedula = function (Forms\Get $get, Forms\Set $set) {
            $type = $get('cedula_type');
            $num = $get('cedula_number');
            if ($type && $num) {
                $set('cedula', strtoupper($type) . '-' . trim($num));
            }
        };

        $updatePhone = function (Forms\Get $get, Forms\Set $set) {
            $prefix = $get('phone_prefix');
            $num = $get('phone_number');
            if ($prefix && $num) {
                $set('phone', trim($prefix) . '-' . trim($num));
            }
        };

        return [
            Forms\Components\TextInput::make('name')
                ->label('Nombre / Razón Social')
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),

            // Cédula / RIF (Tipo + Número)
            Forms\Components\Grid::make(3)
                ->schema([
                    Forms\Components\Select::make('cedula_type')
                        ->label('Tipo')
                        ->options([
                            'V' => 'V',
                            'E' => 'E',
                            'J' => 'J',
                            'G' => 'G',
                            'C' => 'C',
                            'P' => 'P',
                        ])
                        ->default('V')
                        ->required()
                        ->live()
                        ->dehydrated(false)
                        ->afterStateUpdated($updateCedula)
                        ->afterStateHydrated(function ($component, $record) {
                            if ($record?->cedula) {
                                $parts = explode('-', $record->cedula, 2);
                                $component->state($parts[0] ?? 'V');
                            }
                        })
                        ->columnSpan(1),

                    Forms\Components\TextInput::make('cedula_number')
                        ->label('Número Cédula / RIF')
                        ->required()
                        ->maxLength(10)
                        ->regex('/^[0-9]+$/')
                        ->rules([
                            fn (Forms\Get $get, $record) => function (string $attribute, $value, \Closure $fail) use ($get, $record) {
                                $type = $get('cedula_type');
                                if (! $type || ! $value) {
                                    return;
                                }

                                $fullCedula = strtoupper($type) . '-' . trim($value);

                                // Verificamos si existe otro cliente con la misma cédula concatenada
                                $exists = Client::where('cedula', $fullCedula)
                                    ->when($record, fn ($query) => $query->where('id', '!=', $record->id))
                                    ->exists();

                                if ($exists) {
                                    $fail("La Cédula / RIF {$fullCedula} ya se encuentra registrada con otro cliente.");
                                }
                            },
                        ])
                        ->validationMessages([
                            'regex' => 'El número solo debe contener dígitos.',
                        ])
                        ->extraInputAttributes([
                            'oninput' => "this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);",
                        ])
                        ->live(onBlur: true)
                        ->dehydrated(false)
                        ->afterStateUpdated($updateCedula)
                        ->afterStateHydrated(function ($component, $record) {
                            if ($record?->cedula) {
                                $parts = explode('-', $record->cedula, 2);
                                $component->state($parts[1] ?? '');
                            }
                        })
                        ->columnSpan(2),
                ])
                ->columnSpan(1),

            // Campo que almacena el valor final concatenado en la BD (ej: V-18546369)
            Forms\Components\Hidden::make('cedula')
                ->required(),

            // Teléfono (Prefijo + Número)
            Forms\Components\Grid::make(3)
                ->schema([
                    Forms\Components\Select::make('phone_prefix')
                        ->label('Prefijo')
                        ->options([
                            '+58' => '+58 (VE)',
                            '+57' => '+57 (CO)',
                            '+52' => '+52 (MX)',
                            '+1'  => '+1 (US)',
                        ])
                        ->default('+58')
                        ->required()
                        ->live()
                        ->dehydrated(false)
                        ->afterStateUpdated($updatePhone)
                        ->afterStateHydrated(function ($component, $record) {
                            if ($record?->phone) {
                                if (str_contains($record->phone, '-')) {
                                    $parts = explode('-', $record->phone, 2);
                                    $component->state($parts[0] ?? '+58');
                                } else {
                                    preg_match('/^(\+\d{1,3})(.*)$/', $record->phone, $matches);
                                    $component->state($matches[1] ?? '+58');
                                }
                            }
                        })
                        ->columnSpan(1),

                    Forms\Components\TextInput::make('phone_number')
                        ->label('Número Teléfono')
                        ->required()
                        ->maxLength(10)
                        ->regex('/^[0-9]+$/')
                        ->validationMessages([
                            'regex' => 'El teléfono solo debe contener dígitos.',
                        ])
                        ->extraInputAttributes([
                            'oninput' => "this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);",
                        ])
                        ->live(onBlur: true)
                        ->dehydrated(false)
                        ->afterStateUpdated($updatePhone)
                        ->afterStateHydrated(function ($component, $record) {
                            if ($record?->phone) {
                                if (str_contains($record->phone, '-')) {
                                    $parts = explode('-', $record->phone, 2);
                                    $component->state($parts[1] ?? '');
                                } else {
                                    preg_match('/^(\+\d{1,3})(.*)$/', $record->phone, $matches);
                                    $component->state($matches[2] ?? '');
                                }
                            }
                        })
                        ->columnSpan(2),
                ])
                ->columnSpan(1),

            // Campo que almacena el valor final concatenado en la BD (ej: +58-4121234567)
            Forms\Components\Hidden::make('phone')
                ->required(),
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Cliente')
                    ->schema(static::getFormSchema())
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('name')->label('Nombre')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('cedula')->label('Cédula / RIF')->searchable(),
                Tables\Columns\TextColumn::make('phone')->label('Teléfono')->searchable(),
                Tables\Columns\TextColumn::make('created_at')->label('Fecha Registro')->dateTime('d/m/Y')->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListClients::route('/'),
            'create' => Pages\CreateClient::route('/create'),
            'edit'   => Pages\EditClient::route('/{record}/edit'),
        ];
    }
}