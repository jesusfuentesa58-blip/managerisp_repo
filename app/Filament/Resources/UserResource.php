<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use App\Models\Company; // Importante para el dominio dinámico
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationGroup = 'Sistema';
    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationLabel = 'Gestión de Usuarios';
    protected static ?string $modelLabel = 'Usuario';

    // 1. CONSTANTES DE PERMISOS
    const PERMISSION_LABELS = [
        'manage_jurisdictions' => 'Gestionar Jurisdicciones',
        'manage_routers'       => 'Gestionar Routers (MikroTik)',
        'manage_zones'         => 'Gestionar Zonas y Barrios',
        'manage_customers'     => 'Gestionar Clientes',
        'manage_service_requests' => 'Gestionar Solicitudes',
        'manage_installations' => 'Gestionar Instalaciones',
        'manage_services'      => 'Gestionar Servicios de Internet',
        'suspend_services'     => 'Suspender Servicios',
        'reactivate_services'  => 'Reactivar Servicios',
        'view_service_status'  => 'Ver Estado de Conexión',
        'manage_billing'       => 'Gestionar Facturación',
        'view_payments'        => 'Ver Historial de Pagos',
        'send_reminders'       => 'Enviar Recordatorios',
        'manage_automations'   => 'Configurar Automatizaciones',
        'run_automations'      => 'Ejecutar Cortes Automáticos',
        'view_logs'            => 'Auditoría y Logs',
        'manage_users'         => 'Gestionar Usuarios',
        'manage_plans'         => 'Gestionar Planes',
        'manage_installations' => 'Gestionar Instalaciones',
        'manage_service_requests' => 'Gestionar Solicitudes',
    ];

    // 2. CONSTANTES DE ROLES (¡Aquí estaba el faltante!)
    const ROLE_LABELS = [
        'super-admin' => 'Súper Administrador',
        'admin'       => 'Administrador / Gerente',
        'support'     => 'Soporte Técnico',
        'billing'     => 'Facturación y Caja',
    ];

    // 3. COLORES DE ROLES
    const ROLE_COLORS = [
        'super-admin' => 'danger',
        'admin'       => 'warning',
        'support'     => 'info',
        'billing'     => 'success',
    ];

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(3)->schema([
                    
                    // COLUMNA IZQUIERDA (2/3)
                    Forms\Components\Group::make([
                        Forms\Components\Section::make('Credenciales Corporativas')
                            ->icon('heroicon-o-identification')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nombre Completo')
                                    ->required()
                                    ->prefixIcon('heroicon-m-user'),
                                
                                // CORREO DINÁMICO
                                Forms\Components\TextInput::make('email')
                                    ->label('Usuario Corporativo')
                                    ->placeholder('ej: jesus.fuentes')
                                    ->prefixIcon('heroicon-m-at-symbol')
                                    // Suffix visual dinámico
                                    ->suffix(fn() => '@' . (Company::first()?->domain ?? 'miempresa.com'))
                                    ->required()
                                    ->autocomplete('off')
                                    
                                    // --- CORRECCIÓN DE CARGA (FORMAT) ---
                                    // Usamos Str::beforeLast para cortar todo desde la última '@'
                                    // Así solo muestra "jesus.fuentes" sin importar qué dominio tenga guardado
                                    ->formatStateUsing(fn ($state) => Str::beforeLast($state, '@'))
                                    
                                    // --- CORRECCIÓN DE GUARDADO (DEHYDRATE) ---
                                    // Limpiamos cualquier @ residual y pegamos el dominio actual de la empresa
                                    ->dehydrateStateUsing(fn ($state) => Str::beforeLast($state, '@') . '@' . (Company::first()?->domain ?? 'miempresa.com'))
                                    
                                    ->rule(function ($record) {
                                        return function (string $attribute, $value, \Closure $fail) use ($record) {
                                            $domain = '@' . (Company::first()?->domain ?? 'miempresa.com');
                                            // Reconstruimos el email para validar unicidad
                                            $fullEmail = Str::beforeLast($value, '@') . $domain;
                                            
                                            $query = User::where('email', $fullEmail);
                                            if ($record) $query->where('id', '!=', $record->id);
                                            
                                            if ($query->exists()) $fail('Este usuario corporativo ya existe.');
                                        };
                                    }),

                                Forms\Components\TextInput::make('password')
                                    ->label('Contraseña')
                                    ->password()
                                    ->revealable()
                                    ->maxLength(255)
                                    ->autocomplete('new-password')
                                    ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                                    ->dehydrated(fn ($state) => filled($state))
                                    ->required(fn (string $context): bool => $context === 'create')
                                    ->prefixIcon('heroicon-m-key'),
                                    
                                Forms\Components\Toggle::make('is_active')
                                    ->label('Acceso al Sistema')
                                    ->default(true)
                                    ->onColor('success')
                                    ->offColor('danger'),
                            ])->columns(2),

                        Forms\Components\Section::make('Perfil de Acceso')
                            ->description('Define el rol único del colaborador.')
                            ->schema([
                                Forms\Components\Select::make('roles')
                                    ->label('Rol Institucional')
                                    ->options(function () {
                                        return \Spatie\Permission\Models\Role::all()
                                            ->pluck('name', 'id')
                                            ->map(fn ($name) => self::ROLE_LABELS[$name] ?? ucfirst($name));
                                    })
                                    ->native(false)
                                    ->required()
                                    ->prefixIcon('heroicon-m-shield-check')
                                    ->formatStateUsing(fn ($record) => $record?->roles->first()?->id)
                                    ->saveRelationshipsUsing(fn ($record, $state) => $record->roles()->sync([$state]))
                                    ->live()
                                    ->afterStateUpdated(fn ($set) => $set('permissions', [])), 

                                Forms\Components\Placeholder::make('role_summary')
                                    ->label('Permisos incluidos:')
                                    ->content(function (Forms\Get $get) {
                                        $roleId = $get('roles');
                                        if (!$roleId) return 'Selecciona un rol...';

                                        $role = \Spatie\Permission\Models\Role::find($roleId);
                                        if (!$role) return '';

                                        $permissions = $role->permissions->pluck('name');
                                        if ($permissions->isEmpty()) return 'Sin permisos asignados.';

                                        $translated = $permissions->map(fn($p) => self::PERMISSION_LABELS[$p] ?? $p)->join(', ');
                                        return new \Illuminate\Support\HtmlString("<span class='text-xs text-gray-500'>{$translated}</span>");
                                    })
                                    ->columnSpanFull(),
                            ]),
                    ])->columnSpan(2),

                    // COLUMNA DERECHA (1/3)
                    Forms\Components\Section::make('Permisos Extra')
                        ->description('Excepciones adicionales.')
                        ->icon('heroicon-o-finger-print')
                        ->schema([
                            Forms\Components\CheckboxList::make('permissions')
                                ->label('')
                                ->relationship(
                                    name: 'permissions', 
                                    titleAttribute: 'name',
                                    modifyQueryUsing: function (Builder $query, Forms\Get $get) {
                                        $roleId = $get('roles');
                                        if ($roleId) {
                                            $covered = \DB::table('role_has_permissions')
                                                ->where('role_id', $roleId)
                                                ->pluck('permission_id');
                                            return $query->whereNotIn('id', $covered);
                                        }
                                        return $query;
                                    }
                                )
                                ->searchable()
                                ->bulkToggleable()
                                ->columns(1)
                                ->getOptionLabelFromRecordUsing(fn ($record) => self::PERMISSION_LABELS[$record->name] ?? $record->name)
                                ->noSearchResultsMessage('Todo cubierto por el rol.'),
                        ])->columnSpan(1),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Usuario')
                    ->searchable()
                    ->weight('bold')
                    ->description(fn (User $record) => $record->email)
                    ->icon('heroicon-m-user-circle')
                    ->iconColor('primary'),
                
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Rol Asignado')
                    ->badge()
                    // AQUÍ ES DONDE FALLABA ANTES:
                    ->formatStateUsing(fn ($state) => self::ROLE_LABELS[$state] ?? ucfirst($state)) 
                    ->color(fn ($state) => self::ROLE_COLORS[$state] ?? 'gray'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Acceso')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->alignCenter(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('Filtrar por Estado'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('')
                    ->tooltip('Gestionar Usuario'), 
                Tables\Actions\DeleteAction::make()
                    ->label('')
                    ->tooltip('Eliminar')
                    ->hidden(fn (User $record) => $record->id === auth()->id()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}