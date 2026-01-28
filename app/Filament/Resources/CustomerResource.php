<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Models\Customer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists\Infolist; 
use Filament\Infolists\Components;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection; 
use Illuminate\Database\Eloquent\Model;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;
    
    protected static ?string $navigationLabel = 'Lista de Clientes';
    protected static ?string $navigationGroup = 'Clientes';
    protected static ?string $pluralModelLabel = 'Clientes';
    protected static ?string $modelLabel = 'Cliente';
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?int $navigationSort = 1;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    // --- CORRECCIÓN AQUÍ: FILTRO ESTRICTO (NO PROSPECTOS) ---
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            // Solo mostramos Activos y Retirados.
            // Los 'prospectos' quedan ocultos en esta vista.
            ->whereIn('status', ['activo', 'retirado']);
    }
    // --------------------------------------------------------

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // SECCIÓN 1: DATOS GENERALES
                Forms\Components\Section::make('Información del Titular')
                    ->description('Datos personales, estado administrativo y contacto.')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            
                            // Estado General (Solo Activo o Retirado)
                            Forms\Components\Select::make('status')
                                ->label('Estado General')
                                ->options([
                                    'activo' => 'Activo',
                                    'retirado' => 'Retirado',
                                ])
                                ->required()
                                ->native(false)
                                ->prefixIcon('heroicon-m-flag')
                                ->selectablePlaceholder(false),

                            Forms\Components\TextInput::make('name')
                                ->label('Nombre Completo')
                                ->required()
                                ->prefixIcon('heroicon-m-user'),
                            
                            Forms\Components\Grid::make(2)->schema([
                                Forms\Components\Select::make('document_type')
                                    ->label('Tipo Doc.')
                                    ->options(['CC' => 'Cédula', 'NIT' => 'NIT', 'CE' => 'Extranjería'])
                                    ->required(),
                                Forms\Components\TextInput::make('document_number')
                                    ->label('Número')
                                    ->required()
                                    ->prefixIcon('heroicon-m-identification'),
                            ]),

                            Forms\Components\TextInput::make('phone')
                                ->label('Teléfono / Celular')
                                ->tel()
                                ->required()
                                ->prefixIcon('heroicon-m-phone'),
                            
                            Forms\Components\TextInput::make('email')
                                ->label('Correo Electrónico')
                                ->email()
                                ->prefixIcon('heroicon-m-at-symbol')
                                ->columnSpanFull(),
                        ]),

                        Forms\Components\Section::make('Dirección de Facturación (Principal)')
                            ->schema([
                                Forms\Components\Grid::make(2)->schema([
                                    Forms\Components\TextInput::make('neighborhood')
                                        ->label('Barrio')
                                        ->required()
                                        ->prefixIcon('heroicon-m-map'),
                                    Forms\Components\TextInput::make('address')
                                        ->label('Dirección')
                                        ->required()
                                        ->prefixIcon('heroicon-m-home'),
                                ]),
                                Forms\Components\TextInput::make('coordinates')
                                    ->label('Coordenadas GPS (Principal)')
                                    ->prefixIcon('heroicon-m-globe-americas'),
                            ])->compact(),
                    ]),

                // SECCIÓN 2: SERVICIOS TÉCNICOS
                Forms\Components\Section::make('Servicios Técnicos Contratados')
                    ->description('Gestione los planes, routers y credenciales de cada instalación.')
                    ->icon('heroicon-o-server-stack')
                    ->schema([
                        Forms\Components\Repeater::make('services')
                            ->relationship()
                            ->label('Servicio / Instalación')
                            ->itemLabel(fn (array $state): ?string => 'Servicio en: ' . ($state['address'] ?? 'Sin dirección'))
                            
                            ->addable(false) 
                            ->deletable(fn () => auth()->user()->hasAnyRole(['super-admin', 'admin']))
                            ->collapsed(true)

                            ->schema([
                                // Fila 1: Estado del Servicio (Técnico)
                                Forms\Components\Grid::make(2)->schema([
                                    Forms\Components\Select::make('status')
                                        ->label('Estado del Servicio')
                                        ->options([
                                            'activo' => 'Activo',
                                            'suspendido' => 'Suspendido',
                                        ])
                                        ->required()
                                        ->native(false),
                                        
                                    Forms\Components\Select::make('plan_id')
                                        ->label('Plan de Internet')
                                        ->relationship('plan', 'name')
                                        ->required()
                                        ->searchable()
                                        ->preload()
                                        ->prefixIcon('heroicon-m-bolt'),
                                ]),

                                // Fila 2: Credenciales
                                Forms\Components\Section::make('Autenticación & Red')
                                    ->schema([
                                        Forms\Components\Grid::make(2)->schema([
                                            Forms\Components\TextInput::make('pppoe_user')
                                                ->label('Usuario PPPoE')
                                                ->required()
                                                ->prefixIcon('heroicon-m-user-circle'),
                                            
                                            Forms\Components\TextInput::make('pppoe_password')
                                                ->label('Contraseña PPPoE')
                                                ->password()
                                                ->revealable()
                                                ->required()
                                                ->prefixIcon('heroicon-m-key'),
                                            
                                            Forms\Components\TextInput::make('remote_address')
                                                ->label('IP Asignada (MikroTik)')
                                                ->ipv4()
                                                ->required()
                                                ->prefixIcon('heroicon-m-globe-alt'),
                                            
                                            Forms\Components\DatePicker::make('installation_date')
                                                ->label('Fecha Instalación')
                                                ->prefixIcon('heroicon-m-calendar'),
                                        ]),
                                    ])->compact(),

                                // Fila 3: Infraestructura
                                Forms\Components\Section::make('Infraestructura')
                                    ->schema([
                                        Forms\Components\Grid::make(2)->schema([
                                            Forms\Components\Select::make('router_id')
                                                ->label('Router / Nodo')
                                                ->options(\App\Models\Router::pluck('name', 'id'))
                                                ->required()
                                                ->live()
                                                ->afterStateUpdated(fn (Forms\Set $set) => $set('zone_id', null))
                                                ->prefixIcon('heroicon-m-server'),

                                            Forms\Components\Select::make('zone_id')
                                                ->label('Zona / Caja NAP')
                                                ->options(function (Forms\Get $get) {
                                                    $routerId = $get('router_id');
                                                    if (!$routerId) return [];
                                                    return \App\Models\Zone::where('router_id', $routerId)->pluck('name', 'id');
                                                })
                                                ->required()
                                                ->prefixIcon('heroicon-m-signal'),
                                        ]),
                                    ])->compact(),

                                // Fila 4: Ubicación
                                Forms\Components\Section::make('Ubicación de Instalación')
                                    ->schema([
                                        Forms\Components\Grid::make(2)->schema([
                                            Forms\Components\TextInput::make('neighborhood')
                                                ->label('Barrio Instalación')
                                                ->required(),
                                            Forms\Components\TextInput::make('address')
                                                ->label('Dirección Exacta')
                                                ->required(),
                                        ]),
                                        Forms\Components\TextInput::make('coordinates')
                                            ->label('Coordenadas GPS Instalación')
                                            ->prefixIcon('heroicon-m-map-pin'),
                                    ])->collapsible(),

                            ])
                            ->columnSpanFull()
                            ->cloneable(false)
                            ->columns(1),
                    ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Información del Titular')
                    ->description('Datos de facturación y contacto principal.')
                    ->icon('heroicon-o-user-circle')
                    ->schema([
                        Components\Grid::make(3)->schema([
                            Components\Group::make([
                                Components\TextEntry::make('name')->label('Nombre Completo')->weight('bold')->size(Components\TextEntry\TextEntrySize::Large),
                                Components\TextEntry::make('document_number')->label('Cédula / NIT')->icon('heroicon-m-identification')->copyable(),
                            ]),
                            Components\Group::make([
                                Components\TextEntry::make('phone')->label('Teléfono Móvil')->icon('heroicon-m-phone')->url(fn ($record) => "tel:{$record->phone}")->color('success'),
                                Components\TextEntry::make('email')->label('Correo Electrónico')->icon('heroicon-m-envelope'),
                            ]),
                            Components\Group::make([
                                Components\TextEntry::make('neighborhood')->label('Barrio Principal')->icon('heroicon-m-map'),
                                Components\TextEntry::make('address')->label('Dirección Principal')->icon('heroicon-m-home'),
                                Components\TextEntry::make('coordinates')->label('Coordenadas GPS')->icon('heroicon-m-globe-americas')
                                    ->formatStateUsing(fn ($state) => $state ? "Ver Mapa" : "Sin Datos")
                                    ->url(fn ($record) => $record->coordinates ? "https://maps.google.com/?q={$record->coordinates}" : null, true)
                                    ->color('info')
                                    ->visible(fn ($record) => !empty($record->coordinates)),
                            ]),
                        ]),
                    ]),

                Components\Section::make('Servicios Instalados')
                    ->description('Listado de conexiones activas por ubicación.')
                    ->icon('heroicon-o-wifi')
                    ->schema([
                        Components\RepeatableEntry::make('services')
                            ->label('') 
                            ->schema([
                                Components\Grid::make(2)->schema([
                                    Components\Group::make([
                                        Components\TextEntry::make('plan.name')->label('Plan')->badge()->color('info')->icon('heroicon-m-bolt'),
                                        Components\Grid::make(2)->schema([
                                            Components\TextEntry::make('pppoe_user')->label('Usuario PPPoE')->icon('heroicon-m-user')->copyable(),
                                            
                                            // IP CLICABLE EN NUEVA PESTAÑA
                                            Components\TextEntry::make('remote_address')
                                                ->label('IP Asignada')
                                                ->icon('heroicon-m-globe-alt')
                                                ->copyable()
                                                ->fontFamily('mono')
                                                ->url(fn ($state) => "http://{$state}")
                                                ->openUrlInNewTab()
                                                ->color('primary'),

                                        ]),
                                        Components\TextEntry::make('router.name')->label('Conectado a Nodo')->icon('heroicon-m-server'),
                                    ]),
                                    Components\Group::make([
                                        Components\TextEntry::make('neighborhood')->label('Barrio de Instalación')->icon('heroicon-m-map-pin')->weight('bold'),
                                        Components\TextEntry::make('address')->label('Dirección de Instalación')->icon('heroicon-m-home-modern'),
                                        Components\Grid::make(2)->schema([
                                            Components\TextEntry::make('status')
                                                ->label('Estado')
                                                ->badge()
                                                ->color(fn (string $state): string => match ($state) { 
                                                    'activo' => 'success', 
                                                    'suspendido' => 'danger', 
                                                    default => 'gray', 
                                                }),
                                            Components\TextEntry::make('installation_date')->label('Instalado el')->date('d M, Y')->icon('heroicon-m-calendar'),
                                        ]),
                                    ]),
                                ]),
                            ])
                            ->grid(1) 
                            ->contained(true) 
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            // BLOQUEO TOTAL DE CLIC
            ->recordUrl(null)
            ->recordAction(null)

            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Cliente')
                    ->searchable()
                    ->weight('bold')
                    ->description(fn (Customer $record) => "CC: {$record->document_number}"),
                    
                Tables\Columns\TextColumn::make('phone')
                    ->label('Contacto')
                    ->searchable()
                    ->description(fn (Customer $record) => $record->email),

                Tables\Columns\TextColumn::make('services_count')
                    ->counts('services')
                    ->label('Servicios')
                    ->badge()
                    ->color('info')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->icon(fn (string $state): string => match ($state) {
                        'activo' => 'heroicon-m-check-circle',
                        'retirado' => 'heroicon-m-x-circle',
                        default => 'heroicon-m-question-mark-circle',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'activo' => 'success',
                        'retirado' => 'gray',
                        default => 'warning',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
            ])
            ->defaultSort('name', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado del Cliente')
                    ->options([
                        'activo' => 'Activo',
                        'retirado' => 'Retirado',
                    ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activate_bulk')
                        ->label('Activar Seleccionados')
                        ->icon('heroicon-o-play')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn (Collection $records) => $records->each->update(['status' => 'activo']))
                        ->deselectRecordsAfterCompletion(),
                    
                    // Solo activar para clientes retirados que regresan
                ]),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()->label('Ver Detalles')->color('gray'),
                    Tables\Actions\EditAction::make()->label('Editar Datos Completos')->color('primary')->icon('heroicon-m-pencil-square'),
                    Tables\Actions\Action::make('manual_invoice')
                        ->label('Generar Factura')
                        ->icon('heroicon-m-document-currency-dollar')
                        ->color('warning')
                        ->form([
                            Forms\Components\DatePicker::make('billing_date')->label('Fecha de Emisión')->default(now())->required(),
                            Forms\Components\TextInput::make('amount')->label('Monto')->numeric()->prefix('$')->required(),
                            Forms\Components\TextInput::make('concept')->label('Concepto')->default('Mensualidad Internet')->required(),
                        ])
                        ->action(function ($record, array $data) {
                            \Filament\Notifications\Notification::make()->title('Factura Generada')->success()->send();
                        }),
                ])
                ->label(null)
                ->tooltip('Operaciones')
                ->icon('heroicon-m-ellipsis-vertical')
                ->color('info')
                ->iconButton()
                ->size('sm'),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // No necesitamos RelationManagers porque usamos un Repeater en el Form
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }
}