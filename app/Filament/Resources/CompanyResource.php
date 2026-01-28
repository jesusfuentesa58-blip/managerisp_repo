<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompanyResource\Pages;
use App\Models\Company;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;
    
    protected static ?string $navigationGroup = 'Sistema';
    protected static ?string $navigationLabel = 'Mi Empresa';
    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?int $navigationSort = 1; // Para que salga de primero en Sistema   
    protected static ?string $modelLabel = 'Empresa'; // Singular (para "Crear Empresa")
    protected static ?string $pluralModelLabel = 'Mi Empresa'; // Plural (para el título de la sección)
  
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(3)->schema([
                    
                    // COLUMNA IZQUIERDA: LOGO Y DATOS BÁSICOS
                    Forms\Components\Group::make([
                        Forms\Components\Section::make('Identidad Visual')
                            ->schema([
                                Forms\Components\FileUpload::make('logo_path')
                                    ->label('Logotipo de la Empresa')
                                    ->image()
                                    ->imageEditor() // Permite recortar
                                    ->directory('company-logos')
                                    ->columnSpanFull(),
                                Forms\Components\TextInput::make('slogan')
                                    ->label('Eslogan / Frase Comercial')
                                    ->placeholder('Ej: Conectando tu mundo'),
                            ]),
                        
                        Forms\Components\Section::make('Configuración del Sistema')
                            ->schema([
                                Forms\Components\TextInput::make('domain')
                                    ->label('Dominio Corporativo')
                                    ->prefix('https://')
                                    ->placeholder('ejemplo.com')
                                    ->required(),
                                Forms\Components\Select::make('time_zone')
                                    ->label('Zona Horaria')
                                    ->options([
                                        'America/Bogota' => 'Colombia (Bogotá)',
                                        'America/Mexico_City' => 'México',
                                        'America/Lima' => 'Perú',
                                    ])
                                    ->default('America/Bogota')
                                    ->required(),
                            ]),
                    ])->columnSpan(1),

                    // COLUMNA DERECHA: DATOS LEGALES Y CONTACTO
                    Forms\Components\Section::make('Información Legal y Contacto')
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->label('Razón Social / Nombre')
                                ->required()
                                ->prefixIcon('heroicon-m-building-office'),
                            
                            Forms\Components\TextInput::make('nit')
                                ->label('NIT / Cédula / RUC')
                                ->required()
                                ->prefixIcon('heroicon-m-identification'),

                            Forms\Components\TextInput::make('email')
                                ->label('Correo Principal')
                                ->email()
                                ->required()
                                ->prefixIcon('heroicon-m-envelope'),

                            Forms\Components\TextInput::make('phone')
                                ->label('Teléfono / WhatsApp Soporte')
                                ->tel()
                                ->required()
                                ->prefixIcon('heroicon-m-phone'),

                            Forms\Components\TextInput::make('address')
                                ->label('Dirección Física')
                                ->prefixIcon('heroicon-m-map-pin')
                                ->columnSpanFull(),
                                
                            Forms\Components\TextInput::make('city')
                                ->label('Ciudad / Municipio')
                                ->prefixIcon('heroicon-m-map')
                                ->columnSpanFull(),
                        ])->columns(2)->columnSpan(2),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('logo_path')->label('Logo')->circular(),
                Tables\Columns\TextColumn::make('name')->label('Empresa')->weight('bold'),
                Tables\Columns\TextColumn::make('nit')->label('NIT'),
                Tables\Columns\TextColumn::make('domain')->label('Dominio')->icon('heroicon-m-globe-alt'),
                Tables\Columns\TextColumn::make('phone')->label('Contacto'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Configurar'),
            ])
            ->paginated(false); // No necesitamos paginación para 1 registro
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCompanies::route('/'),
            'create' => Pages\CreateCompany::route('/create'),
            'edit' => Pages\EditCompany::route('/{record}/edit'),
        ];
    }
}