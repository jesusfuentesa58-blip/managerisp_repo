<?php

namespace App\Filament\Resources\JurisdictionResource\Pages;

use App\Filament\Resources\JurisdictionResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateJurisdiction extends CreateRecord
{
    protected static string $resource = JurisdictionResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

}
