<?php

namespace App\Filament\Resources\ComponenteCurricularResource\Pages;

use App\Filament\Resources\ComponenteCurricularResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageComponenteCurriculars extends ManageRecords
{
    protected static string $resource = ComponenteCurricularResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
