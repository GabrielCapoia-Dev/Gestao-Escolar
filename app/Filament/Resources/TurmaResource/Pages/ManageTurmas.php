<?php

namespace App\Filament\Resources\TurmaResource\Pages;

use App\Filament\Resources\TurmaResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageTurmas extends ManageRecords
{
    protected static string $resource = TurmaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->using(function (array $data) {
                    $componentes = $data['componentes'] ?? [];
                    unset($data['componentes']);

                    $turma = static::getModel()::create($data);

                    foreach ($componentes as $componente) {
                        if (isset($componente['componente_curricular_id'])) {
                            $turma->componentes()->attach($componente['componente_curricular_id'], [
                                'professor_id' => $componente['professor_id'] ?? null,
                                'tem_professor' => $componente['tem_professor'] ?? false,
                            ]);
                        }
                    }

                    return $turma;
                }),

        ];
    }
}
