<?php

namespace App\Filament\Resources\AlternativaResource\Pages;

use App\Filament\Resources\AlternativaResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageAlternativas extends ManageRecords
{
    protected static string $resource = AlternativaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
