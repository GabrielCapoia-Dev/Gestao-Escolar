<?php

namespace App\Filament\Resources\TipoAvaliacaoResource\Pages;

use App\Filament\Resources\TipoAvaliacaoResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageTipoAvaliacaos extends ManageRecords
{
    protected static string $resource = TipoAvaliacaoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
