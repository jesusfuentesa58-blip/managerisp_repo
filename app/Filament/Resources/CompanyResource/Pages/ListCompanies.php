<?php

namespace App\Filament\Resources\CompanyResource\Pages;

use App\Filament\Resources\CompanyResource;
use App\Models\Company;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCompanies extends ListRecords
{
    protected static string $resource = CompanyResource::class;

    public function mount(): void
    {
        // Buscamos si ya existe la empresa
        $company = Company::first();

        if ($company) {
            // CASO 1: Ya existe -> Vamos directo a editarla
            redirect()->to(CompanyResource::getUrl('edit', ['record' => $company]));
        } else {
            // CASO 2: No existe -> Vamos directo a crearla
            redirect()->to(CompanyResource::getUrl('create'));
        }
    }

    // Ya no necesitamos botones aquí porque nunca verás esta página
    protected function getHeaderActions(): array
    {
        return [];
    }
}