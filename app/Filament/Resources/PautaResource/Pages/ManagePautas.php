<?php

namespace App\Filament\Resources\PautaResource\Pages;

use App\Filament\Resources\PautaResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManagePautas extends ManageRecords
{
    protected static string $resource = PautaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
