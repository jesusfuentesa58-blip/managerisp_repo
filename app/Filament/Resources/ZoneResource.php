<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ZoneResource\Pages;
use App\Models\Zone;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Grid;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Support\Enums\FontWeight;
use Filament\Notifications\Notification; 
use Filament\Tables\Actions\ActionGroup; 

class ZoneResource extends Resource
{
    
    protected static ?string $model = Zone::class;

    protected static ?string $navigationGroup = 'Configuración';
    protected static ?string $navigationIcon = 'heroicon-o-map'; 
    
    protected static ?string $navigationLabel = 'Zonas';
    protected static ?string $modelLabel = 'Zona';
    protected static ?string $pluralLabel = 'Zonas';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Grid::make(3)->schema([
                
                Section::make('Identidad de la Zona')
                    ->description('Definición del sector o barrio.')
                    ->icon('heroicon-o-map')
                    ->columnSpan(2)
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre (Barrio/Sector)')
                            ->placeholder('Ej: Los Almendros')
                            ->required()
                            ->prefixIcon('heroicon-m-map-pin'),

                        TextInput::make('code')
                            ->label('Código Interno')
                            ->placeholder('Ej: Z-ALM')
                            ->prefixIcon('heroicon-m-qr-code'),
                            
                        Select::make('router_id')
                            ->label('Router / Nodo Principal')
                            ->relationship('router', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->prefixIcon('heroicon-m-server')
                            ->columnSpanFull()
                            ->helperText('Router al que se conectan físicamente los clientes de esta zona.'),
                    ]),

                Section::make('Estado')
                    ->icon('heroicon-o-check-circle')
                    ->columnSpan(1)
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Zona Activa')
                            ->helperText('Habilita esta zona para nuevas instalaciones.')
                            ->default(true)
                            ->onColor('success'),
                    ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(null) // Bloqueo de clic
            ->columns([
                TextColumn::make('name')
                    ->label('Zona / Barrio')
                    ->weight(FontWeight::Bold)
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-m-map')
                    ->color('primary')
                    ->description(fn (Zone $record) => $record->code ? "Código: {$record->code}" : null),

                TextColumn::make('services_count')
                    ->counts('services')
                    ->label('Servicios')
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-m-users')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('router.name')
                    ->label('Nodo / Router')
                    ->badge()
                    ->color('gray')
                    ->icon('heroicon-m-server'),

                TextColumn::make('router.jurisdiction.name')
                    ->label('Cobertura')
                    ->icon('heroicon-m-globe-americas')
                    ->color('gray')
                    ->toggleable(),

                IconColumn::make('is_active')
                    ->label('Activa')
                    ->boolean()
                    ->alignCenter(),
            ])
            ->defaultSort('name')
            ->filters([
                // SIN FILTROS DE PAPELERA (Limpio)
            ])
            ->actions([
                ActionGroup::make([
                    
                    Tables\Actions\EditAction::make()
                        ->label('Editar')
                        ->color('primary'),

                    // ACCIÓN DE ELIMINAR (Visualmente estándar)
                    // Internamente hace Soft Delete porque el Modelo tiene el Trait.
                    Tables\Actions\DeleteAction::make()
                        ->label('Eliminar')
                        ->modalHeading('¿Eliminar Zona?')
                        ->modalDescription('La zona se eliminará del sistema.')
                        ->successNotificationTitle('Zona eliminada correctamente'),
                ])
                ->link()
                ->label('')
                ->tooltip('Opciones')
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListZones::route('/'),
            'create' => Pages\CreateZone::route('/create'),
            'edit' => Pages\EditZone::route('/{record}/edit'),
        ];
    }
}