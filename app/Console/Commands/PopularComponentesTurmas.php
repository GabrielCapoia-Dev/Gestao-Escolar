<?php

namespace App\Console\Commands;

use App\Models\Turma;
use App\Models\TurmaComponenteProfessor;
use Illuminate\Console\Command;

class PopularComponentesTurmas extends Command
{
    protected $signature = 'turmas:popular-componentes';

    protected $description = 'Popula componentes curriculares para turmas que ainda não possuem registros';

    public function handle(): int
    {
        $turmas = Turma::with('serie.componentesCurriculares')->get();

        $criados = 0;
        $ignorados = 0;

        $this->withProgressBar($turmas, function (Turma $turma) use (&$criados, &$ignorados) {
            if (!$turma->serie) {
                return;
            }

            foreach ($turma->serie->componentesCurriculares as $componente) {
                $existe = TurmaComponenteProfessor::where('turma_id', $turma->id)
                    ->where('componente_curricular_id', $componente->id)
                    ->exists();

                if (!$existe) {
                    TurmaComponenteProfessor::create([
                        'turma_id' => $turma->id,
                        'componente_curricular_id' => $componente->id,
                        'professor_id' => null,
                        'tem_professor' => false,
                    ]);
                    $criados++;
                } else {
                    $ignorados++;
                }
            }
        });

        $this->newLine(2);
        $this->info("Registros criados: {$criados}");
        $this->info("Registros ignorados (já existiam): {$ignorados}");

        return Command::SUCCESS;
    }
}