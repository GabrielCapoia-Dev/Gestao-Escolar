<?php

namespace App\Filament\Resources\FuncaoAdministrativaResource\Pages;

use App\Filament\Resources\FuncaoAdministrativaResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageFuncaoAdministrativas extends ManageRecords
{
    protected static string $resource = FuncaoAdministrativaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
