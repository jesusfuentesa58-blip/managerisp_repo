<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceRequestResource\Pages;
use App\Models\ServiceRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ServiceRequestResource extends Resource
{
    protected static ?string $model = ServiceRequest::class;
    
    protected static ?string $navigationGroup = 'Clientes';
    protected static ?string $navigationLabel = 'Solicitud de Servicio';
    protected static ?string $pluralModelLabel = 'Solicitudes';
    protected static ?string $modelLabel = 'Solicitud';
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            // --- SEGURIDAD: SOLO LECTURA SI YA ESTÁ APROBADA ---
            ->disabled(fn ($record) => $record && $record->status === 'aprobada')
            ->schema([
                Forms\Components\Grid::make(3)->schema([
                    
                    // COLUMNA IZQUIERDA (2/3)
                    Forms\Components\Group::make([
                        
                        // SECCIÓN 1: PERFIL DEL PROSPECTO
                        Forms\Components\Section::make('Perfil del Prospecto')
                            ->description('Seleccione un cliente existente o registre uno nuevo.')
                            ->icon('heroicon-o-user-group')
                            ->schema([
                                Forms\Components\Select::make('customer_id')
                                    ->label('Cliente / Titular')
                                    ->relationship('customer', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live()
                                    ->prefixIcon('heroicon-m-user')
                                    ->createOptionForm([
                                        Forms\Components\Section::make('Datos Personales')
                                            ->icon('heroicon-o-identification')
                                            ->schema([
                                                Forms\Components\TextInput::make('name')
                                                    ->label('Nombre Completo')
                                                    ->required()
                                                    ->prefixIcon('heroicon-m-user'),
                                                Forms\Components\Grid::make(2)->schema([
                                                    Forms\Components\Select::make('document_type')
                                                        ->label('Tipo Doc.')
                                                        ->options(['CC' => 'Cédula', 'NIT' => 'NIT', 'CE' => 'Extranjería'])
                                                        ->default('CC')
                                                        ->required(),
                                                    Forms\Components\TextInput::make('document_number')
                                                        ->label('Número')
                                                        ->required()
                                                        ->unique('customers', 'document_number')
                                                        ->prefixIcon('heroicon-m-identification'),
                                                ]),
                                                Forms\Components\Grid::make(2)->schema([
                                                    Forms\Components\TextInput::make('phone')
                                                        ->label('Celular')
                                                        ->tel()
                                                        ->required()
                                                        ->prefixIcon('heroicon-m-phone'),
                                                    Forms\Components\TextInput::make('email')
                                                        ->label('Correo')
                                                        ->email()
                                                        ->prefixIcon('heroicon-m-at-symbol'),
                                                ]),
                                            ]),
                                        
                                        Forms\Components\Section::make('Ubicación Principal (Facturación)')
                                            ->description('Domicilio fiscal o principal del cliente.')
                                            ->icon('heroicon-o-home')
                                            ->schema([
                                                Forms\Components\Grid::make(2)->schema([
                                                    Forms\Components\TextInput::make('neighborhood')
                                                        ->label('Barrio Principal')
                                                        ->required()
                                                        ->prefixIcon('heroicon-m-map'),
                                                    Forms\Components\TextInput::make('coordinates')
                                                        ->label('Coordenadas (Opcional)')
                                                        ->prefixIcon('heroicon-m-globe-alt'),
                                                ]),
                                                Forms\Components\TextInput::make('address')
                                                    ->label('Dirección Principal')
                                                    ->required()
                                                    ->prefixIcon('heroicon-m-home'),
                                            ])
                                    ]),
                            ]),

                        // SECCIÓN 2: GEOLOCALIZACIÓN DEL SERVICIO
                        Forms\Components\Section::make('Lugar de Instalación')
                            ->description('¿Dónde se instalará el servicio?')
                            ->icon('heroicon-o-map-pin')
                            ->headerActions([
                                Forms\Components\Actions\Action::make('copy_address')
                                    ->label('Copiar del Cliente')
                                    ->tooltip('Usar la misma dirección del perfil del cliente')
                                    ->icon('heroicon-m-arrow-down-on-square')
                                    ->color('primary')
                                    ->size('sm')
                                    ->visible(fn ($record) => !$record || $record->status !== 'aprobada')
                                    ->action(function (Forms\Get $get, Forms\Set $set) {
                                        $customerId = $get('customer_id');
                                        if ($customerId) {
                                            $customer = \App\Models\Customer::find($customerId);
                                            if ($customer) {
                                                $set('neighborhood', $customer->neighborhood);
                                                $set('address', $customer->address);
                                                $set('coordinates', $customer->coordinates);
                                                \Filament\Notifications\Notification::make()
                                                    ->title('Dirección copiada del perfil')
                                                    ->success()
                                                    ->send();
                                            }
                                        } else {
                                            \Filament\Notifications\Notification::make()
                                                ->title('Primero seleccione un cliente')
                                                ->warning()
                                                ->send();
                                        }
                                    }),
                            ])
                            ->schema([
                                Forms\Components\Grid::make(2)->schema([
                                    Forms\Components\TextInput::make('neighborhood')
                                        ->label('Barrio / Sector')
                                        ->required()
                                        ->prefixIcon('heroicon-m-map-pin'),
                                    Forms\Components\TextInput::make('coordinates')
                                        ->label('Coordenadas GPS')
                                        ->placeholder('Lat, Lon')
                                        ->prefixIcon('heroicon-m-globe-americas'),
                                ]),
                                Forms\Components\TextInput::make('address')
                                    ->label('Dirección Exacta de Instalación')
                                    ->required()
                                    ->placeholder('Ej: Calle 10 # 5-20, Local 1')
                                    ->prefixIcon('heroicon-m-home-modern')
                                    ->columnSpanFull(),
                            ]),
                    ])->columnSpan(2),
                    
                    // COLUMNA DERECHA (1/3)
                     Forms\Components\Group::make([
                        Forms\Components\Section::make('Condiciones Comerciales')
                            ->icon('heroicon-o-currency-dollar')
                            ->schema([
                                Forms\Components\Select::make('plan_id')
                                    ->label('Plan Solicitado')
                                    ->relationship('plan', 'name')
                                    ->required()
                                    ->preload()
                                    ->prefixIcon('heroicon-m-rocket-launch'),
                                
                                Forms\Components\Textarea::make('notes')
                                    ->label('Observaciones')
                                    ->placeholder('Ej: El cliente prefiere instalación en la tarde...')
                                    ->rows(5),
                            ]),
                    ])->columnSpan(1),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // (ID ELIMINADO PARA LIMPIEZA VISUAL)

                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Cliente')
                    ->searchable()
                    ->weight('bold')
                    ->description(fn ($record) => $record->customer->phone),
                
                Tables\Columns\TextColumn::make('plan.name')
                    ->label('Plan')
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-m-bolt'),
                
                Tables\Columns\TextColumn::make('neighborhood')
                    ->label('Ubicación')                    
                    ->description(fn ($record) => str($record->address)->limit(20)),
                
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pendiente' => 'warning',
                        'aprobada'  => 'success',
                        'rechazada' => 'danger',
                        default     => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'pendiente' => 'heroicon-m-clock',
                        'aprobada'  => 'heroicon-m-check-circle',
                        'rechazada' => 'heroicon-m-x-circle',
                        default     => 'heroicon-m-question-mark-circle',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                
                // 1. GRUPO DE OPCIONES
                Tables\Actions\ActionGroup::make([
                    
                    // EDITAR (Solo si NO está aprobada)
                    Tables\Actions\EditAction::make()
                        ->label('Editar')
                        ->color('primary')
                        ->visible(fn ($record) => $record->status !== 'aprobada'),

                    // BORRAR (Solo si NO está aprobada)
                    Tables\Actions\DeleteAction::make()
                        ->label('Eliminar')
                        ->visible(fn ($record) => $record->status !== 'aprobada'),

                    // VER (Modo Lectura, solo si ESTÁ aprobada)
                    Tables\Actions\ViewAction::make()
                        ->label('Ver Detalles')
                        ->color('gray')
                        ->icon('heroicon-o-eye')
                        ->visible(fn ($record) => $record->status === 'aprobada'),
                ])
                ->icon('heroicon-m-ellipsis-vertical')
                ->tooltip('Opciones')
                ->color('gray'),

                // 2. APROBAR (Solo Pendientes y Solo Admins)
                Tables\Actions\Action::make('approve')
                    ->label('Aprobar')
                    ->icon('heroicon-m-check-badge')
                    ->button()
                    ->color('success')
                    ->visible(fn ($record) => 
                        $record->status === 'pendiente' && 
                        auth()->user()->hasAnyRole(['super-admin', 'admin'])
                    )
                    ->modalHeading('Aprobar Solicitud de Servicio')
                    ->modalDescription('Complete los datos técnicos para generar la orden de instalación.')
                    ->modalSubmitActionLabel('Confirmar y Crear Orden')
                    ->form([
                        Forms\Components\Section::make('Agendamiento')
                            ->columns(2)
                            ->schema([
                                Forms\Components\DatePicker::make('scheduled_at')
                                    ->label('Fecha de Instalación')
                                    ->required()
                                    ->native(false)
                                    ->prefixIcon('heroicon-m-calendar'),
                                Forms\Components\Select::make('technician_id')
                                    ->label('Asignar Técnico')
                                    ->options(\App\Models\User::role('support')->pluck('name', 'id'))
                                    ->required()
                                    ->prefixIcon('heroicon-m-wrench'),
                            ]),
                        
                        Forms\Components\Section::make('Asignación de Red')
                            ->columns(2)
                            ->schema([
                                Forms\Components\Select::make('router_id')
                                    ->label('Nodo / Router')
                                    ->options(\App\Models\Router::pluck('name', 'id'))
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(fn (Forms\Set $set) => $set('zone_id', null))
                                    ->prefixIcon('heroicon-m-server'),
                                
                                Forms\Components\Select::make('zone_id')
                                    ->label('Zona Técnica')
                                    ->options(function (Forms\Get $get) {
                                        $routerId = $get('router_id');
                                        if (!$routerId) return [];
                                        return \App\Models\Zone::where('router_id', $routerId)->pluck('name', 'id');
                                    })
                                    ->required()
                                    ->prefixIcon('heroicon-m-signal'),
                                
                                Forms\Components\TextInput::make('pppoe_user')
                                    ->label('Usuario PPPoE')
                                    ->required()
                                    ->prefixIcon('heroicon-m-user-circle'),
                                Forms\Components\TextInput::make('pppoe_password')
                                    ->label('Contraseña')
                                    ->password()
                                    ->revealable()
                                    ->required()
                                    ->prefixIcon('heroicon-m-key'),
                                
                                Forms\Components\TextInput::make('remote_address')
                                    ->label('IP Asignada')
                                    ->ipv4()
                                    ->required()
                                    ->prefixIcon('heroicon-m-globe-alt'),
                            ]),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'status' => 'aprobada', 
                            'zone_id' => $data['zone_id']
                        ]);

                        \App\Models\Installation::create([
                            'service_request_id' => $record->id,
                            'technician_id' => $data['technician_id'],
                            'scheduled_at' => $data['scheduled_at'],
                            'router_id' => $data['router_id'],
                            'zone_id' => $data['zone_id'],
                            'pppoe_user' => $data['pppoe_user'],
                            'pppoe_password' => $data['pppoe_password'],
                            'remote_address' => $data['remote_address'],
                            'status' => 'programada',
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Solicitud Aprobada')
                            ->body('Se ha generado la orden de instalación correctamente.')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServiceRequests::route('/'),
            'create' => Pages\CreateServiceRequest::route('/create'),
            'edit' => Pages\EditServiceRequest::route('/{record}/edit'),
        ];
    }
}