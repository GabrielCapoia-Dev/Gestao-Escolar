<?php
// app/Filament/Resources/AlunoResource/Pages/ManageAlunos.php

namespace App\Filament\Resources\AlunoResource\Pages;

use App\Filament\Resources\AlunoResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageAlunos extends ManageRecords
{
    protected static string $resource = AlunoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->mutateFormDataUsing(function (array $data): array {
                    $data['ativo'] = true;
                    $data['situacao'] = $data['situacao'] ?? 'Matriculado';
                    return $data;
                }),
        ];
    }
}