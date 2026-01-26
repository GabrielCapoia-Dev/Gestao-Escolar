<?php

namespace App\Filament\Pages;

use App\Models\Avaliacao;
use App\Models\Aluno;
use App\Models\Resposta;
use App\Models\Alternativa;
use App\Models\Turma;
use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use App\Models\Pauta;
use App\Models\TurmaComponenteProfessor;

class AvaliarTurma extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-pencil-square';
    protected static ?string $title = 'Avaliar Turma';
    protected static ?string $slug = 'avaliar-turma';
    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.pages.avaliar-turma';

    #[Url]
    public ?int $avaliacao = null;

    #[Url]
    public ?int $turma = null;

    public ?Avaliacao $avaliacaoModel = null;
    public ?Turma $turmaModel = null;
    public $turmasDisponiveis = [];
    public $alunos = [];
    public $pautas = [];
    public $alternativasPorTipo = [];
    public $respostas = [];

    public function mount(): void
    {
        if (!$this->avaliacao) {
            $this->redirect(route('filament.admin.resources.avaliacoes.index'));
            return;
        }

        $this->avaliacaoModel = Avaliacao::with(['turmas.escola', 'turmas.serie', 'pautas.tipoAvaliacao.alternativas'])
            ->find($this->avaliacao);

        if (!$this->avaliacaoModel) {
            Notification::make()
                ->title('Avaliação não encontrada')
                ->danger()
                ->send();
            $this->redirect(route('filament.admin.resources.avaliacoes.index'));
            return;
        }

        if ($this->avaliacaoModel->status === 'fechada') {
            Notification::make()
                ->title('Avaliação fechada')
                ->warning()
                ->send();
            $this->redirect(route('filament.admin.resources.avaliacoes.index'));
            return;
        }

        // Carrega turmas disponíveis
        $this->turmasDisponiveis = $this->avaliacaoModel->turmas;

        // Se tiver turma selecionada, carrega os dados
        if ($this->turma) {
            $this->carregarDadosTurma();
        }
    }

    public function selecionarTurma(int $turmaId): void
    {
        $this->turma = $turmaId;
        $this->carregarDadosTurma();
    }

protected function carregarDadosTurma(): void
{
    $this->turmaModel = Turma::with(['escola', 'serie'])->find($this->turma);

    if (!$this->turmaModel) {
        return;
    }

    // Verifica se a turma pertence à avaliação
    if (!$this->avaliacaoModel->turmas->contains($this->turma)) {
        Notification::make()
            ->title('Turma não pertence a esta avaliação')
            ->danger()
            ->send();

        $this->turma = null;
        $this->turmaModel = null;
        return;
    }

    // ----------------------------
    // ALUNOS
    // ----------------------------

    $this->alunos = $this->turmaModel->alunos()
        ->where('situacao', 'Matriculado')
        ->orderBy('nome')
        ->get();

    // ----------------------------
    // PAUTAS (CORRIGIDO)
    // ----------------------------

    if (Auth::user()->eh_admin ?? false) {

        // Admin mantém comportamento antigo
        $this->pautas = $this->avaliacaoModel->pautas;

    } else {

        $professorId = Auth::user()->professor?->id;

        if (!$professorId) {
            $this->pautas = collect();
            return;
        }

        // Componentes que o professor leciona nessa turma
        $componentesNaTurma = \App\Models\TurmaComponenteProfessor::where('professor_id', $professorId)
            ->where('turma_id', $this->turma)
            ->pluck('componente_curricular_id')
            ->unique()
            ->toArray();

        // Pautas do componente + série da turma
        $this->pautas = \App\Models\Pauta::whereIn('id_componente_curricular', $componentesNaTurma)
            ->where('id_serie', $this->turmaModel->id_serie)
            ->with('tipoAvaliacao')
            ->orderBy('id')
            ->get();
    }

    // ----------------------------
    // ALTERNATIVAS POR TIPO
    // ----------------------------

    $this->alternativasPorTipo = [];

    foreach ($this->pautas as $pauta) {

        $tipoId = $pauta->id_tipo_avaliacao;

        if (!isset($this->alternativasPorTipo[$tipoId])) {

            $this->alternativasPorTipo[$tipoId] = Alternativa::where('id_tipo_avaliacao', $tipoId)
                ->orderBy('id')
                ->get();
        }
    }

    // ----------------------------
    // RESPOSTAS EXISTENTES
    // ----------------------------

    $this->respostas = [];

    $alunosIds = $this->alunos->pluck('id')->toArray();

    $respostasExistentes = Resposta::where('id_avaliacao', $this->avaliacao)
        ->whereIn('id_aluno', $alunosIds)
        ->get();

    foreach ($respostasExistentes as $resposta) {
        $this->respostas[$resposta->id_aluno][$resposta->id_pauta] = $resposta->id_alternativa;
    }
}

    public function voltarSelecao(): void
    {
        $this->turma = null;
        $this->turmaModel = null;
        $this->alunos = [];
        $this->respostas = [];
    }

    public function salvarRespostas(): void
    {
        $professor = Auth::user()->professor ?? null;
        $professorId = $professor?->id ?? Auth::id();

        foreach ($this->respostas as $alunoId => $pautasRespostas) {
            foreach ($pautasRespostas as $pautaId => $alternativaId) {
                if ($alternativaId) {
                    Resposta::updateOrCreate(
                        [
                            'id_avaliacao' => $this->avaliacao,
                            'id_aluno' => $alunoId,
                            'id_pauta' => $pautaId,
                        ],
                        [
                            'id_alternativa' => $alternativaId,
                            'id_professor' => $professorId,
                        ]
                    );
                }
            }
        }

        Notification::make()
            ->title('Respostas salvas com sucesso!')
            ->success()
            ->send();
    }

    public function finalizarTurma(): void
    {
        // Verifica se todas as respostas foram preenchidas para esta turma
        $totalEsperado = $this->alunos->count() * $this->pautas->count();
        $alunosIds = $this->alunos->pluck('id')->toArray();
        
        $totalRespondido = Resposta::where('id_avaliacao', $this->avaliacao)
            ->whereIn('id_aluno', $alunosIds)
            ->count();

        if ($totalRespondido < $totalEsperado) {
            Notification::make()
                ->title('Avaliação incompleta')
                ->body("Faltam " . ($totalEsperado - $totalRespondido) . " respostas para finalizar esta turma.")
                ->warning()
                ->send();
            return;
        }

        Notification::make()
            ->title('Turma avaliada com sucesso!')
            ->success()
            ->send();

        $this->voltarSelecao();
    }

    public function getTitle(): string
    {
        if ($this->turmaModel) {
            return "Avaliar: {$this->turmaModel->serie->nome} - Turma {$this->turmaModel->nome}";
        }
        
        if ($this->avaliacaoModel) {
            return "Selecionar Turma - {$this->avaliacaoModel->descricao}";
        }
        
        return 'Avaliar Turma';
    }

    public function getProgressoTurma(Turma $turma): array
    {
        $alunosCount = $turma->alunos()
            ->where('situacao', 'Matriculado')
            ->count();
        
        $pautasCount = $this->avaliacaoModel->pautas->count();
        $totalEsperado = $alunosCount * $pautasCount;

        if ($totalEsperado === 0) {
            return ['total' => 0, 'respondido' => 0, 'percentual' => 0];
        }

        $alunosIds = $turma->alunos()
            ->where('situacao', 'Matriculado')
            ->pluck('id')
            ->toArray();

        $totalRespondido = Resposta::where('id_avaliacao', $this->avaliacao)
            ->whereIn('id_aluno', $alunosIds)
            ->count();

        return [
            'total' => $totalEsperado,
            'respondido' => $totalRespondido,
            'percentual' => round(($totalRespondido / $totalEsperado) * 100),
        ];
    }
}