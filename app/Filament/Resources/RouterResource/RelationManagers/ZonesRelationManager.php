<?php

namespace App\Filament\Resources\RouterResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ZonesRelationManager extends RelationManager
{
    protected static string $relationship = 'zones';

    protected static ?string $title = 'Zonas de Cobertura'; // Título informativo

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre del Barrio / Sector')
                    ->weight('bold'),
                
                Tables\Columns\TextColumn::make('code')
                    ->label('Código')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Estado')
                    ->boolean(),
            ])
            ->filters([
                // Puedes dejar filtros si quieres que el usuario busque zonas específicas
            ])
            ->headerActions([
                // VACÍO: Esto quita el botón "New" o "Associate"
            ])
            ->actions([
                // VACÍO: Esto quita los botones de "Edit", "Delete" o "View" de la fila
            ])
            ->bulkActions([
                // VACÍO: Esto quita las acciones por lote
            ]);
    }
}