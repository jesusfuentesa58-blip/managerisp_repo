<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BaseResource;
use App\Filament\Resources\RouterResource\Pages;
use App\Models\Router;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Grid;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use App\Services\MikroTik\MikroTikService;
use ISP\Mikrotik\Exceptions\RouterOfflineException as ISPRouterOfflineException;
use Illuminate\Support\Str;
use App\Filament\Resources\RouterResource\RelationManagers;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\RestoreAction; // Importante
use Filament\Tables\Actions\ForceDeleteAction; // Importante
use Filament\Tables\Filters\TrashedFilter; // Importante

class RouterResource extends BaseResource
{
    protected static ?string $model = Router::class;

    protected static ?string $navigationGroup = 'Configuración';
    protected static ?string $navigationIcon = 'heroicon-m-cpu-chip';
    protected static ?string $navigationLabel = 'Routers';

    protected static ?string $modelLabel = 'Router';
    protected static ?string $pluralLabel = 'Routers';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Grid::make(3)->schema([
                Section::make('Identidad')
                    ->description('Asignación y nombre del equipo')
                    ->icon('heroicon-m-identification')
                    ->columnSpan(2)
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre del Router')
                            ->placeholder('Ej: Core-Principal-Marta')
                            ->required()
                            ->prefixIcon('heroicon-m-server'),
                        Select::make('jurisdiction_id')
                            ->label('Jurisdicción')
                            ->relationship('jurisdiction', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->prefixIcon('heroicon-m-map-pin'),
                    ]),

                Section::make('Estado Administrativo')
                    ->icon('heroicon-m-signal')
                    ->columnSpan(1)
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Router Activo')
                            ->helperText('Habilita el procesamiento de reglas en este equipo.')
                            ->default(true)
                            ->onColor('success'),
                    ]),

                Section::make('Credenciales de Conexión')
                    ->description('Parámetros de acceso a la API')
                    ->icon('heroicon-m-key')
                    ->columnSpan(3)
                    ->columns(4)
                    ->schema([
                        TextInput::make('host')
                            ->label('Host / IP')
                            ->placeholder('192.168.1.1')
                            ->prefixIcon('heroicon-m-globe-alt')
                            ->required(),
                        TextInput::make('port')
                            ->label('Puerto')
                            ->numeric()
                            ->default(8728)
                            ->prefixIcon('heroicon-m-hashtag'),
                        TextInput::make('username')
                            ->label('Usuario')
                            ->prefixIcon('heroicon-m-user')
                            ->required(),
                        TextInput::make('password')
                            ->label('Contraseña')
                            ->password()
                            ->revealable()
                            ->prefixIcon('heroicon-m-lock-closed')
                            ->required(),

                        // >>> NUEVO CAMPO: IP INTERFAZ LAN
                        TextInput::make('lan_ip')
                            ->label('IP Interfaz LAN')
                            ->placeholder('192.168.0.1')
                            ->ipv4()
                            ->required()
                            ->prefixIcon('heroicon-m-globe-alt')
                            ->helperText('IP de la interfaz LAN del router (gateway para perfiles PPP)')
                            ->columnSpan(2),
                    ]),

                Section::make('Configuración de Suspensión')
                    ->description('Método de corte de servicio')
                    ->icon('heroicon-m-no-symbol')
                    ->columnSpan(3)
                    ->columns(2)
                    ->schema([
                        Select::make('suspension_method')
                            ->label('Método de suspensión')
                            ->options([
                                'pppoe' => 'PPPoE (Deshabilitar Secret)',
                                'address-list' => 'Address List (Firewall)',
                            ])
                            ->native(false)
                            ->required()
                            ->reactive()
                            ->prefixIcon('heroicon-m-shield-check'),

                        Toggle::make('provision_address_list')
                            ->label('Provisionar reglas automáticamente')
                            ->visible(fn ($get) => $get('suspension_method') === 'address-list')
                            ->default(true)
                            ->onColor('warning'),
                    ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(null)
            ->columns([
                TextColumn::make('name')
                    ->label('Router / Nodo')
                    ->weight(FontWeight::Bold)
                    ->searchable()
                    ->sortable()
                    ->color('primary'),

                TextColumn::make('status')
                    ->label('Conexión')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => match($state) {
                        Router::STATUS_ONLINE => 'En línea',
                        Router::STATUS_DEGRADED => 'Degradado',
                        Router::STATUS_OFFLINE => 'Offline',
                        Router::STATUS_AUTH_ERROR => 'Error Auth',
                        default => 'Desconocido',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        Router::STATUS_ONLINE => 'success',
                        Router::STATUS_DEGRADED => 'warning',
                        Router::STATUS_OFFLINE, Router::STATUS_AUTH_ERROR => 'danger',
                        default => 'gray',
                    })
                    ->icon(fn (?string $state): string => match ($state) {
                        Router::STATUS_ONLINE => 'heroicon-m-signal',
                        Router::STATUS_DEGRADED => 'heroicon-m-exclamation-triangle',
                        Router::STATUS_OFFLINE => 'heroicon-m-signal-slash',
                        default => 'heroicon-m-question-mark-circle',
                    }),

                TextColumn::make('services_count')
                    ->counts('services')
                    ->label('Servicios')
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-m-users')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('host')
                    ->label('Host / IP')
                    ->fontFamily('mono')
                    ->icon('heroicon-m-globe-alt')
                    ->color('gray')
                    ->copyable(),

                TextColumn::make('jurisdiction.name')
                    ->label('Zona')
                    ->icon('heroicon-m-map-pin')
                    ->toggleable(),
            ])
            ->defaultSort('name')
            ->filters([
                // SIN FILTROS DE PAPELERA
            ])
            ->actions([
                ActionGroup::make([
                    
                    Action::make('check_status')
                        ->label('Verificar Conexión')
                        ->icon('heroicon-m-arrow-path')
                        ->color('success')
                        ->action(function (Router $record) {
                            try {
                                $service = new MikroTikService($record);
                                $statusDto = $service->getHealth(); 
                                $nuevoEstado = $service->determineRouterStateFromDto($statusDto);
                                $record->update([
                                    'status' => $nuevoEstado,
                                    'last_checked_at' => now(),
                                ]);
                                Notification::make()
                                    ->title('Estado Actualizado')
                                    ->body("El router está: " . strtoupper($nuevoEstado))
                                    ->color($nuevoEstado === Router::STATUS_ONLINE ? 'success' : 'danger')
                                    ->send();
                            } catch (\Throwable $e) {
                                $record->update(['status' => Router::STATUS_OFFLINE, 'last_checked_at' => now()]);
                                Notification::make()->title('Fallo de conexión')->danger()->body($e->getMessage())->send();
                            }
                        }),

                    EditAction::make()
                        ->label('Editar Configuración')
                        ->color('primary'),

                    // SOLO ELIMINAR (Desaparece de la vista)
                    DeleteAction::make()
                        ->label('Eliminar')
                        ->modalHeading('¿Eliminar Router?')
                        ->modalDescription('El router se eliminará del sistema.')
                        ->successNotificationTitle('Router eliminado correctamente'),
                ])
                ->link()
                ->label('')
                ->tooltip('Operaciones')
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRouters::route('/'),
            'create' => Pages\CreateRouter::route('/create'),
            'edit' => Pages\EditRouter::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ZonesRelationManager::class,
        ];
    }
}
