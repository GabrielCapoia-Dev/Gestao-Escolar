<?php

namespace App\Filament\Resources\FuncionarioAdministrativoResource\Pages;

use App\Filament\Resources\FuncionarioAdministrativoResource;
use App\Models\Professor;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageFuncionariosAdministrativos extends ManageRecords
{
    protected static string $resource = FuncionarioAdministrativoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Vincular Função')
                ->modalHeading('Vincular Função Administrativa')
                ->modalSubmitActionLabel('Vincular')
                ->using(function (array $data): Professor {
                    // Busca o professor selecionado
                    $professor = Professor::findOrFail($data['professor_id']);
                    
                    // Atualiza com a função administrativa
                    $professor->update([
                        'funcao_administrativa_id' => $data['funcao_administrativa_id'],
                        'portaria' => $data['portaria'] ?? null,
                    ]);
                    
                    // Sincroniza as turmas se houver
                    if (isset($data['turmasFuncao']) && is_array($data['turmasFuncao'])) {
                        $professor->turmasFuncao()->sync($data['turmasFuncao']);
                    }
                    
                    return $professor->fresh();
                }),
        ];
    }
}