<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlanResource\Pages;
use App\Models\Plan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Grid;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Get;
use Closure;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

class PlanResource extends Resource
{
    protected static ?string $model = Plan::class;
    protected static ?string $navigationGroup = 'Configuración';
    protected static ?string $navigationIcon = 'heroicon-o-queue-list';
    protected static ?string $modelLabel = 'Plan';
    protected static ?string $pluralLabel = 'Planes';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Grid::make(3)->schema([
                
                // --- SECCIÓN ASIGNACIÓN Y DESTINO ---
                Section::make('Asignación y Destino')
                    ->description('Selecciona la cobertura y los routers específicos para este perfil')
                    ->columnSpan(2)
                    ->columns(2)
                    ->schema([
                        Select::make('jurisdiction_id')
                            ->label('Cobertura')
                            ->relationship('jurisdiction', 'name')
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Forms\Set $set) => $set('routers', [])),

                        Select::make('routers')
                            ->label('Routers Destino')
                            ->multiple()
                            ->relationship('routers', 'name', fn (Builder $query, Forms\Get $get) => 
                                $query->where('jurisdiction_id', $get('jurisdiction_id'))
                            )
                            ->preload()
                            ->required()
                            ->helperText('Solo se sincronizará el perfil en los routers seleccionados.'),
                    ]),

                // --- SECCIÓN ESTADO ---
                Section::make('Estado')
                    ->columnSpan(1)
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Plan Activo')
                            ->helperText('Si se desactiva, no podrá asignarse a nuevos clientes.')
                            ->default(true)
                            ->onColor('success'),
                    ]),

                // --- SECCIÓN DETALLES ---
                Section::make('Detalles del Plan')
                    ->columnSpan(2)
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre Comercial')
                            ->placeholder('Ej: Plan Hogar 100 Megas')
                            ->required(),
                        TextInput::make('pppoe_profile_name')
                            ->label('Perfil PPPoE (MikroTik)')
                            ->required()
                            ->rules([
                                fn (Get $get, ?Model $record): Closure => function (string $attribute, $value, Closure $fail) use ($get, $record) {
                                    if ($record && $record->pppoe_profile_name === $value) {
                                        return;
                                    }

                                    $routerIds = $get('routers') ?? [];
                                    
                                    foreach ($routerIds as $routerId) {
                                        $router = \App\Models\Router::find($routerId);
                                        if (!$router || !$router->is_active) continue;

                                        try {
                                            $service = new \App\Services\MikroTik\MikroTikService($router);
                                            if ($service->getProfile($value)) {
                                                $fail("¡Conflicto de Red! El perfil '{$value}' ya existe en el MikroTik '{$router->name}'.");
                                            }
                                        } catch (\Throwable $e) {
                                            $fail("Error de conexión con {$router->name}: " . $e->getMessage());
                                        }
                                    }
                                },
                            ]),
                        Textarea::make('description')
                            ->label('Descripción interna')
                            ->columnSpanFull(),
                    ]),

                // --- SECCIÓN TÉCNICA ---
                Section::make('Configuración Técnica')
                    ->description('Velocidades de subida y bajada')
                    ->icon('heroicon-o-bolt')
                    ->columnSpan(1)
                    ->schema([
                        TextInput::make('upload_speed')
                            ->label('Subida')
                            ->placeholder('10M')
                            ->prefixIcon('heroicon-m-arrow-up-circle')
                            ->required(),
                        TextInput::make('download_speed')
                            ->label('Bajada')
                            ->placeholder('100M')
                            ->prefixIcon('heroicon-m-arrow-down-circle')
                            ->required(),
                    ]),

                // --- SECCIÓN FINANZAS ---
                Section::make('Finanzas')
                    ->description('Configuración de cobro y beneficios')
                    ->icon('heroicon-o-currency-dollar')
                    ->columnSpan(3)
                    ->columns(4)
                    ->schema([
                        TextInput::make('price')
                            ->label('Precio Mensual')
                            ->prefix('$')
                            ->numeric()
                            ->inputMode('numeric')
                            ->required(),

                        Select::make('tax_type')
                            ->label('Impuestos')
                            ->options([
                                'included' => 'IVA Incluido',
                                'added' => 'IVA Adicional (+)',
                            ])
                            ->native(false)
                            ->default('included'),

                        TextInput::make('discount_value')
                            ->label('Tasa de Descuento')
                            ->suffix('%')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->default(0)
                            ->helperText('Porcentaje que se restará del precio mensual.'),
                            
                        TextInput::make('discount_duration_months')
                            ->label('Meses de Descuento')
                            ->helperText('0 para permanente')
                            ->numeric()
                            ->default(0),
                    ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            // --- 1. BLOQUEO DE CLIC EN LA FILA ---
            ->recordUrl(null) 

            ->columns([
                
                // NOMBRE + PERFIL
                Tables\Columns\TextColumn::make('name')
                    ->label('Plan / Perfil')
                    ->weight('bold')
                    ->searchable()
                    ->color('primary')
                    ->description(fn (Plan $record) => "MikroTik: {$record->pppoe_profile_name}"),

                // CONTEO DE CLIENTES
                Tables\Columns\TextColumn::make('services_count')
                    ->counts('services') 
                    ->label('Servicios')
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-m-users')
                    ->alignCenter()
                    ->sortable(),

                // VELOCIDAD
                Tables\Columns\TextColumn::make('download_speed')
                    ->label('Velocidad')
                    ->formatStateUsing(fn (Plan $record) => "↓ {$record->download_speed}  |  ↑ {$record->upload_speed}")
                    ->icon('heroicon-m-bolt')
                    ->badge()
                    ->color('gray'),

                // PRECIO
                Tables\Columns\TextColumn::make('price')
                    ->label('Mensualidad')
                    ->money('COP', divideBy: 1)
                    ->sortable()
                    ->weight('bold')
                    ->color('success'),

                // COBERTURA
                Tables\Columns\TextColumn::make('jurisdiction.name')
                    ->label('Zona')
                    ->icon('heroicon-m-map')
                    ->toggleable(),

                // ESTADO
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean()
                    ->alignCenter(),
            ])
            ->defaultSort('price', 'asc')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Estado')
                    ->boolean()
                    ->trueLabel('Planes Activos')
                    ->falseLabel('Planes Inactivos'),
                
                // NOTA: No agregamos el TrashedFilter para mantener la interfaz limpia
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    
                    Tables\Actions\EditAction::make()
                        ->label('Editar Plan')
                        ->color('primary'),

                    // ACCIÓN DE ELIMINAR (Soft Delete visualmente estándar)
                    // Eliminamos el bloque before() para permitir la eliminación aunque tenga registros,
                    // ya que el Soft Delete mantendrá la integridad referencial.
                    Tables\Actions\DeleteAction::make()
                        ->label('Eliminar')
                        ->modalHeading('¿Eliminar Plan?')
                        ->modalDescription('El plan se eliminará del sistema. Los servicios actuales no se verán afectados en la base de datos.')
                        ->successNotificationTitle('Plan eliminado correctamente'),
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
            'index' => Pages\ListPlans::route('/'),
            'create' => Pages\CreatePlan::route('/create'),
            'edit' => Pages\EditPlan::route('/{record}/edit'),
        ];
    }
}