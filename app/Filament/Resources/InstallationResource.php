<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InstallationResource\Pages;
use App\Models\Installation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class InstallationResource extends Resource
{
    protected static ?string $model = Installation::class;
    
    protected static ?string $navigationGroup = 'Clientes';
    protected static ?string $navigationLabel = 'Órdenes de Instalación';
    protected static ?string $pluralModelLabel = 'Instalaciones';
    protected static ?string $modelLabel = 'Instalación';
    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';
    protected static ?int $navigationSort = 3;

    public static function canDelete(Model $record): bool
    {
        return false; 
    }

    /**
     * FIX: no filtrar por roles aquí (evita que billing quede sin registros).
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }
    
    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->disabled(fn ($record) => 
                $record && 
                $record->status === 'finalizada' && 
                !auth()->user()->hasAnyRole(['super-admin', 'admin'])
            )
            ->schema([
                Forms\Components\Grid::make(3)->schema([
                    
                    // COLUMNA IZQUIERDA (2/3)
                    Forms\Components\Group::make([
                        
                        // DATOS DEL TITULAR
                        Forms\Components\Section::make('Datos del Titular')
                            ->columns(2)
                            ->schema([
                                Forms\Components\TextInput::make('client_display')
                                    ->label('Cliente')
                                    ->formatStateUsing(fn ($record) => $record->serviceRequest->customer->name)
                                    ->disabled()->dehydrated(false)
                                    ->prefixIcon('heroicon-m-user'),
                                
                                Forms\Components\TextInput::make('phone_display')
                                    ->label('Teléfono')
                                    ->formatStateUsing(fn ($record) => $record->serviceRequest->customer->phone)
                                    ->disabled()->dehydrated(false)
                                    ->prefixIcon('heroicon-m-phone'),

                                // --- NUEVO CAMPO: PLAN CONTRATADO ---
                                Forms\Components\TextInput::make('plan_display')
                                    ->label('Plan')
                                    ->formatStateUsing(fn ($record) => $record->serviceRequest->plan->name)
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->prefixIcon('heroicon-m-rocket-launch')
                                    ->columnSpanFull(), // Ocupa todo el ancho para resaltar
                                // ------------------------------------

                                Forms\Components\TextInput::make('main_address_display')
                                    ->label('Dirección Principal (Fiscal)')
                                    ->formatStateUsing(fn ($record) => $record->serviceRequest->customer->address)
                                    ->disabled()->dehydrated(false)
                                    ->prefixIcon('heroicon-m-home'),

                                Forms\Components\TextInput::make('main_neighborhood_display')
                                    ->label('Barrio')
                                    ->formatStateUsing(fn ($record) => $record->serviceRequest->customer->neighborhood)
                                    ->disabled()->dehydrated(false)
                                    ->prefixIcon('heroicon-m-map'),
                            ]),

                        // LUGAR DE INSTALACIÓN
                        Forms\Components\Section::make('Lugar de Instalación')
                            ->columns(2)
                            ->schema([
                                Forms\Components\TextInput::make('address_display')
                                    ->label('Dirección de Instalación')
                                    ->formatStateUsing(fn ($record) => $record->serviceRequest->address)
                                    ->disabled()->dehydrated(false)
                                    ->prefixIcon('heroicon-m-home-modern')
                                    ->columnSpanFull(), 

                                Forms\Components\TextInput::make('neighborhood_display')
                                    ->label('Barrio de Instalación')
                                    ->formatStateUsing(fn ($record) => $record->serviceRequest->neighborhood)
                                    ->disabled()->dehydrated(false)
                                    ->prefixIcon('heroicon-m-map-pin'),
                                
                                Forms\Components\TextInput::make('coordinates_display')
                                    ->label('Coordenadas GPS')
                                    ->formatStateUsing(fn ($record) => $record->serviceRequest->coordinates)
                                    ->disabled()->dehydrated(false)
                                    ->prefixIcon('heroicon-m-globe-alt'),

                                Forms\Components\DatePicker::make('scheduled_at')
                                    ->label('Fecha Programada')
                                    ->displayFormat('d/m/Y')
                                    ->required()
                                    ->prefixIcon('heroicon-m-calendar'),
                            ]),

                        // CIERRE
                        Forms\Components\Section::make('Reporte de Cierre')
                            ->schema([
                                Forms\Components\Grid::make(2)->schema([
                                    Forms\Components\TextInput::make('onu_serial')
                                        ->label('Serial ONU')
                                        ->prefixIcon('heroicon-m-qr-code'),
                                    Forms\Components\TextInput::make('signal_dbm')
                                        ->label('Potencia (dBm)')
                                        ->numeric()
                                        ->prefixIcon('heroicon-m-signal'),
                                ]),
                            ])->visible(fn ($record) => $record->status === 'finalizada'),

                    ])->columnSpan(2),

                    // COLUMNA DERECHA (1/3)
                    Forms\Components\Group::make([
                        Forms\Components\Section::make('Configuración Técnica')
                            ->schema([
                                Forms\Components\Select::make('router_id')
                                    ->label('Router')
                                    ->options(\App\Models\Router::pluck('name', 'id'))
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(fn (Forms\Set $set) => $set('zone_id', null))
                                    ->prefixIcon('heroicon-m-server'),

                                Forms\Components\Select::make('zone_id')
                                    ->label('Zona')
                                    ->options(function (Forms\Get $get) {
                                        $routerId = $get('router_id');
                                        if (!$routerId) return [];
                                        return \App\Models\Zone::where('router_id', $routerId)->pluck('name', 'id');
                                    })
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->prefixIcon('heroicon-m-signal'),

                                Forms\Components\TextInput::make('pppoe_user')
                                    ->label('Usuario PPPoE')
                                    ->required()
                                    ->prefixIcon('heroicon-m-user-circle'),
                                    
                                Forms\Components\TextInput::make('pppoe_password')
                                    ->label('Contraseña PPPoE')
                                    ->password()->revealable()->required()
                                    ->prefixIcon('heroicon-m-key'),
                                    
                                Forms\Components\TextInput::make('remote_address')
                                    ->label('IP Asignada')
                                    ->required()
                                    ->prefixIcon('heroicon-m-globe-alt'),
                            ]),
                        
                        Forms\Components\Section::make('Estado')
                            ->schema([
                                Forms\Components\Placeholder::make('status_display')
                                    ->label('')
                                    ->content(fn ($record) => ucfirst($record->status))
                                    ->extraAttributes(fn ($record) => [
                                        'class' => match ($record->status) {
                                            'programada' => 'text-warning-600 font-bold text-center text-xl',
                                            'finalizada' => 'text-success-600 font-bold text-center text-xl',
                                            'cancelada' => 'text-danger-600 font-bold text-center text-xl',
                                            default => 'text-gray-600',
                                        }
                                    ]),
                            ]),
                    ])->columnSpan(1),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {

                $user = auth()->user();

                // 🔴 FIX CRÍTICO: forzar carga de roles (Livewire bug)
                $user->loadMissing('roles');

                // Admin / Billing / Super admin ven TODO
                if ($user->hasAnyRole(['super-admin', 'admin', 'billing'])) {
                    return; // no tocar el query
                }

                // Support / técnicos: solo lo suyo
                $query->where('technician_id', $user->id);
            })

            ->columns([
                Tables\Columns\TextColumn::make('serviceRequest.customer.name')
                    ->label('Cliente')
                    ->searchable()
                    ->weight('bold')
                    ->description(fn ($record) => $record->serviceRequest->customer->phone),

                Tables\Columns\TextColumn::make('serviceRequest.neighborhood')
                    ->label('Lugar Instalación')
                    ->sortable()
                    ->description(fn ($record) => $record->serviceRequest->address),

                Tables\Columns\TextColumn::make('scheduled_at')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable()
                    ->icon('heroicon-m-calendar'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'programada' => 'warning',
                        'finalizada' => 'success',
                        'cancelada'  => 'danger',
                        default      => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => ucfirst($state)),

                Tables\Columns\TextColumn::make('technician.name')
                    ->label('Técnico')
                    ->toggleable(),
            ])

            ->defaultSort('scheduled_at', 'asc')

            ->actions([
                // ======================================================
                // EDITAR
                // ======================================================
                Tables\Actions\EditAction::make()
                    ->iconButton()
                    ->tooltip('Gestionar / Editar')
                    ->color('primary')
                    ->visible(fn ($record) =>
                        auth()->user()->hasAnyRole(['super-admin', 'admin']) ||
                        $record->status !== 'finalizada'
                    ),

                // ======================================================
                // VER
                // ======================================================
                Tables\Actions\ViewAction::make()
                    ->iconButton()
                    ->tooltip('Ver Ficha')
                    ->color('gray')
                    ->modalHeading('Detalles de Instalación')
                    ->visible(fn ($record) =>
                        $record->status === 'finalizada' &&
                        !auth()->user()->hasAnyRole(['super-admin', 'admin'])
                    ),

                // ======================================================
                // FINALIZAR (AHORA: solo visible para admins y renombrado a "Activar")
                // ======================================================
                Tables\Actions\Action::make('finalizar')
                    ->label('Activar')
                    ->icon('heroicon-m-check')
                    ->button()
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Finalizar Instalación y Activar en Router')
                    ->visible(fn ($record) =>
                        auth()->user()->hasAnyRole(['super-admin', 'admin']) &&
                        $record->status === 'programada'
                    )
                    ->form([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('onu_serial')
                                ->label('Serial ONU')
                                ->required()
                                ->prefixIcon('heroicon-m-qr-code'),

                            Forms\Components\TextInput::make('signal_dbm')
                                ->label('Potencia (dBm)')
                                ->numeric()
                                ->required()
                                ->prefixIcon('heroicon-m-signal'),
                        ]),
                    ])

                    ->action(function ($record, array $data, \Filament\Tables\Actions\Action $action) {

                        $router = $record->router;
                        $planProfileName = $record->serviceRequest->plan->pppoe_profile_name;

                        $pppoeUser = $record->pppoe_user;
                        $pppoePass = $record->pppoe_password;
                        $ip = $record->remote_address;
                        $clientName = $record->serviceRequest->customer->name;

                        $mkService = new \App\Services\MikroTik\MikroTikService($router);

                        try {
                            $mkService->testConnection();

                            if ($mkService->checkSecretExists($pppoeUser)) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Error: Usuario Duplicado')
                                    ->body("El usuario PPPoE '{$pppoeUser}' ya existe en el Router.")
                                    ->danger()
                                    ->persistent()
                                    ->send();

                                $action->halt();
                            }

                            $mkService->createPppSecret(
                                user: $pppoeUser,
                                password: $pppoePass,
                                profile: $planProfileName,
                                remoteAddress: $ip,
                                comment: $clientName
                            );

                        } catch (\Throwable $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Error MikroTik')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();

                            $action->halt();
                        }

                        // DB
                        $record->serviceRequest->customer->update(['status' => 'activo']);

                        \App\Models\Service::create([
                            'customer_id' => $record->serviceRequest->customer_id,
                            'plan_id' => $record->serviceRequest->plan_id,
                            'router_id' => $record->router_id,
                            'zone_id' => $record->zone_id,
                            'address' => $record->serviceRequest->address,
                            'neighborhood' => $record->serviceRequest->neighborhood,
                            'coordinates' => $record->serviceRequest->coordinates,
                            'pppoe_user' => $pppoeUser,
                            'pppoe_password' => $pppoePass,
                            'remote_address' => $ip,
                            'status' => 'activo',
                            'installation_date' => now(),
                        ]);

                        $record->update([
                            'status' => 'finalizada',
                            'onu_serial' => $data['onu_serial'],
                            'signal_dbm' => $data['signal_dbm'],
                            'completed_at' => now(),
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Instalación Finalizada')
                            ->success()
                            ->body('Servicio creado correctamente en MikroTik y Base de Datos.')
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInstallations::route('/'),
            'create' => Pages\CreateInstallation::route('/create'),
            'edit' => Pages\EditInstallation::route('/{record}/edit'),
        ];
    }
}
