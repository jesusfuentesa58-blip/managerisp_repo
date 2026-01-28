<?php

namespace App\Filament\Resources;

// Si usas una clase base personalizada, cámbialo. Aquí uso la estándar Resource.
use Filament\Resources\Resource; 
use App\Filament\Resources\JurisdictionResource\Pages;
use App\Models\Jurisdiction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Grid;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Support\Enums\FontWeight;
use App\Filament\Resources\JurisdictionResource\RelationManagers;
use Filament\Notifications\Notification; // Importante para la alerta

class JurisdictionResource extends Resource
{
    
    protected static ?string $model = Jurisdiction::class;
    
    protected static ?string $navigationGroup = 'Configuración';
    protected static ?string $navigationIcon = 'heroicon-o-map-pin'; 

    protected static ?string $navigationLabel = 'Coberturas';
    protected static ?string $pluralLabel = 'Coberturas';
    protected static ?string $modelLabel = 'Cobertura';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Grid::make(3)
                ->schema([
                    Section::make('Identidad')
                        ->description('Información básica y administrativa')
                        ->icon('heroicon-o-identification')
                        ->columnSpan(2)
                        ->columns(2)
                        ->schema([
                            TextInput::make('name')
                                ->label('Nombre')
                                ->placeholder('Ej: Medellín Norte')
                                ->required()
                                ->prefixIcon('heroicon-m-map'),
                            TextInput::make('code')
                                ->label('Código Interno')
                                ->placeholder('Ej: MED-N')
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->prefixIcon('heroicon-m-qr-code'),
                        ]),

                    Section::make('Estado')
                        ->icon('heroicon-o-check-circle')
                        ->columnSpan(1)
                        ->schema([
                            Toggle::make('is_active')
                                ->label('Cobertura Activa') 
                                ->helperText('Habilita esta zona para nuevos contratos.')
                                ->default(true)
                                ->onColor('success'),
                        ]),

                    Section::make('Ubicación Geográfica')
                        ->icon('heroicon-o-map')
                        ->columnSpan(2)
                        ->columns(2)
                        ->schema([
                            TextInput::make('department')
                                ->label('Departamento')
                                ->required(),
                            TextInput::make('city')
                                ->label('Ciudad')
                                ->required(),
                            TextInput::make('latitude')
                                ->label('Latitud')
                                ->numeric()
                                ->prefixIcon('heroicon-m-map-pin'),
                            TextInput::make('longitude')
                                ->label('Longitud')
                                ->numeric()
                                ->prefixIcon('heroicon-m-map-pin'),
                        ]),

                    Section::make('Reglas de Facturación')
                        ->description('Días de ciclo y límites para el corte de servicio')
                        ->icon('heroicon-o-currency-dollar')
                        ->columnSpan(3)
                        ->columns(4)
                        ->schema([
                            TextInput::make('billing_day')
                                ->label('Día Facturación')
                                ->numeric()
                                ->minValue(1)->maxValue(28)
                                ->hintIcon('heroicon-m-calendar', tooltip: 'Día de generación de factura'),

                            TextInput::make('due_day')
                                ->label('Día Vencimiento')
                                ->numeric()
                                ->minValue(1)->maxValue(28)
                                ->hintIcon('heroicon-m-clock', tooltip: 'Fecha límite de pago'),

                            TextInput::make('suspension_day')
                                ->label('Día Suspensión')
                                ->numeric()
                                ->minValue(1)->maxValue(28)
                                ->hintIcon('heroicon-m-no-symbol', tooltip: 'Día de corte de servicio'),

                            TextInput::make('suspend_after_invoices')
                                ->label('Límite Facturas')
                                ->numeric()
                                ->default(1)
                                ->hintIcon('heroicon-m-exclamation-triangle', tooltip: 'Facturas impagas permitidas'),
                        ]),

                    Section::make('Automatizaciones')
                        ->icon('heroicon-o-bolt')
                        ->columnSpan(3)
                        ->columns(3)
                        ->schema([
                            Toggle::make('auto_generate_invoices')->label('Generar facturas'),
                            Toggle::make('auto_send_invoices')->label('Enviar facturas'),
                            Toggle::make('auto_suspend_services')->label('Suspender servicios'),
                            Toggle::make('auto_send_sms')->label('Enviar SMS'),
                            Toggle::make('auto_send_email')->label('Enviar Email'),
                        ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            // BLOQUEO DE CLIC EN LA FILA
            ->recordUrl(null)
            
            ->columns([
                
                // 1. NOMBRE Y CÓDIGO
                Tables\Columns\TextColumn::make('name')
                    ->label('Cobertura')
                    ->weight(FontWeight::Bold)
                    ->searchable()
                    ->sortable()
                    ->color('primary')
                    ->description(fn (Jurisdiction $record) => "Código: {$record->code}"),

                // 2. CONTEO DE ROUTERS (Infraestructura)
                Tables\Columns\TextColumn::make('routers_count')
                    ->label('Routers')
                    ->counts('routers') 
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-m-server')
                    ->alignCenter()
                    ->sortable(),
                
                // 3. CONTEO DE PLANES (Comercial)
                Tables\Columns\TextColumn::make('plans_count')
                    ->label('Planes')
                    ->counts('plans') 
                    ->badge()
                    ->color('success')
                    ->icon('heroicon-m-presentation-chart-line')
                    ->alignCenter(),

                // 4. UBICACIÓN (Combinada)
                Tables\Columns\TextColumn::make('city')
                    ->label('Ubicación')
                    ->formatStateUsing(fn ($record) => "{$record->city}, {$record->department}")
                    ->color('gray'),

                // 5. CICLO (Día de corte)
                Tables\Columns\TextColumn::make('billing_day')
                    ->label('Ciclo')
                    ->prefix('Día ')
                    ->icon('heroicon-m-calendar-days')
                    ->sortable(),

                // 6. ESTADO
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activa')
                    ->boolean()
                    ->alignCenter(),
            ])
            ->defaultSort('name')
            ->filters([
                // SIN FILTROS DE PAPELERA PARA MANTENER LA NARRATIVA DE ELIMINACIÓN LIMPIA
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    
                    Tables\Actions\EditAction::make()
                        ->label('Editar Cobertura')
                        ->color('primary'),

                    // ACCIÓN DE ELIMINAR (Soft Delete Visualmente Estándar)
                    // Quitamos el bloqueo manual para que el Soft Delete maneje la integridad.
                    Tables\Actions\DeleteAction::make()
                        ->label('Eliminar')
                        ->modalHeading('¿Eliminar Cobertura?')
                        ->modalDescription('La cobertura se eliminará del sistema. Los registros históricos se mantendrán en la base de datos.')
                        ->successNotificationTitle('Cobertura eliminada correctamente'),
                ])
                ->link()
                ->label('')
                ->tooltip('Opciones')
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJurisdictions::route('/'),
            'create' => Pages\CreateJurisdiction::route('/create'),
            'edit' => Pages\EditJurisdiction::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\RoutersRelationManager::class,
        ];
    }
}