<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    // AHORA SÍ FUNCIONARÁ LA DESCRIPCIÓN
    public function getSubheading(): ?string
    {
        return 'Administre el personal, asigne roles y controle el acceso al sistema.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nuevo Usuario')
                ->icon('heroicon-o-plus'),
        ];
    }
}