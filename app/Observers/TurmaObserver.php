<?php

namespace App\Observers;

use App\Models\Turma;
use App\Models\TurmaComponenteProfessor;

class TurmaObserver
{
    public function created(Turma $turma): void
    {
        $this->criarComponentesPadrao($turma);
    }

    public function updated(Turma $turma): void
    {
        // Se a série mudou, atualiza os componentes
        if ($turma->isDirty('id_serie')) {
            $this->criarComponentesPadrao($turma);
        }
    }

    private function criarComponentesPadrao(Turma $turma): void
    {
        $serie = $turma->serie()->with('componentesCurriculares')->first();

        if (!$serie) {
            return;
        }

        foreach ($serie->componentesCurriculares as $componente) {
            TurmaComponenteProfessor::firstOrCreate(
                [
                    'turma_id' => $turma->id,
                    'componente_curricular_id' => $componente->id,
                ],
                [
                    'professor_id' => null,
                    'tem_professor' => false,
                ]
            );
        }
    }
}