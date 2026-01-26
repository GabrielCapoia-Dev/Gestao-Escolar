<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AvaliacaoResource\Pages;
use App\Models\Avaliacao;
use App\Models\Turma;
use App\Models\Pauta;
use App\Models\TipoAvaliacao;
use App\Models\Aluno;
use App\Models\Alternativa;
use App\Models\Resposta;
use App\Models\Professor;
use App\Models\TurmaComponenteProfessor;
use App\Services\UserService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AvaliacaoResource extends Resource
{
    protected static ?string $model = Avaliacao::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationLabel = 'Avaliações';
    protected static ?string $pluralModelLabel = 'Avaliações';
    protected static ?string $modelLabel = 'Avaliação';
    protected static ?string $navigationGroup = 'Avaliações';
    protected static ?int $navigationSort = 4;
    protected static ?string $slug = 'avaliacoes';

    protected static function getUserService(): UserService
    {
        return app(UserService::class);
    }

    protected static function ehAdmin(): bool
    {
        return self::getUserService()->ehAdmin(Auth::user());
    }

    protected static function getProfessores()
    {
        return Professor::where('user_id', Auth::id())->pluck('id');
    }

    protected static function getDadosProfessor(): array
    {
        $professoresIds = self::getProfessores();

        if ($professoresIds->isEmpty()) {
            return ['turmas' => [], 'componentes' => []];
        }

        $vinculos = TurmaComponenteProfessor::whereIn('professor_id', $professoresIds)->get();

        return [
            'turmas' => $vinculos->pluck('turma_id')->unique()->toArray(),
            'componentes' => $vinculos->pluck('componente_curricular_id')->unique()->toArray(),
        ];
    }

    protected static function getComponentesPorTurma(int $turmaId): array
    {
        return TurmaComponenteProfessor::whereIn('professor_id', self::getProfessores())
            ->where('turma_id', $turmaId)
            ->pluck('componente_curricular_id')
            ->unique()
            ->toArray();
    }
    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Seleção de Pautas')
                ->icon('heroicon-o-document-text')
                ->description('Selecione o tipo de avaliação para filtrar as pautas disponíveis')
                ->schema([
                    Forms\Components\Select::make('filtro_tipo_avaliacao')
                        ->label('Tipo de Avaliação')
                        ->options(TipoAvaliacao::orderBy('tipo_avaliacao')->pluck('tipo_avaliacao', 'id'))
                        ->searchable()
                        ->preload()
                        ->live()
                        ->dehydrated(false)
                        ->afterStateUpdated(function (Set $set) {
                            $set('pautas', []);
                            $set('turmas', []);
                            $set('todas_turmas', false);
                        })
                        ->helperText('Filtro para visualização das pautas'),

                    Forms\Components\Select::make('pautas')
                        ->label('Pautas')
                        ->multiple()
                        ->relationship('pautas', 'pauta')
                        ->options(function (Get $get) {
                            $tipoId = $get('filtro_tipo_avaliacao');

                            $query = Pauta::with('tipoAvaliacao', 'componenteCurricular', 'serie');

                            if ($tipoId) {
                                $query->where('id_tipo_avaliacao', $tipoId);
                            }

                            return $query->get()
                                ->mapWithKeys(fn($pauta) => [
                                    $pauta->id => "[{$pauta->serie->nome}] [{$pauta->componenteCurricular->nome}] {$pauta->pauta}"
                                ])
                                ->toArray();
                        })
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live()
                        ->afterStateUpdated(function (Set $set) {
                            $set('turmas', []);
                            $set('todas_turmas', false);
                        })
                        ->helperText(fn(Get $get) => $get('filtro_tipo_avaliacao')
                            ? 'Pautas filtradas pelo tipo selecionado'
                            : 'Selecione um tipo de avaliação para filtrar')
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Forms\Components\Section::make('Turmas')
                ->icon('heroicon-o-user-group')
                ->schema([
                    Forms\Components\Toggle::make('todas_turmas')
                        ->label('Selecionar todas as turmas')
                        ->live()
                        ->dehydrated(false)
                        ->afterStateUpdated(function (Set $set, Get $get, $state) {
                            if ($state) {
                                $pautasIds = $get('pautas') ?? [];

                                if (!empty($pautasIds)) {
                                    $seriesIds = Pauta::whereIn('id', $pautasIds)
                                        ->pluck('id_serie')
                                        ->unique()
                                        ->toArray();

                                    $turmasIds = Turma::whereIn('id_serie', $seriesIds)
                                        ->pluck('id')
                                        ->toArray();

                                    $set('turmas', $turmasIds);
                                }
                            } else {
                                $set('turmas', []);
                            }
                        })
                        ->disabled(fn(Get $get) => empty($get('pautas')))
                        ->helperText('Marca todas as turmas das séries correspondentes às pautas selecionadas'),

                    Forms\Components\Select::make('turmas')
                        ->label('Turmas')
                        ->multiple()
                        ->relationship('turmas', 'nome')
                        ->options(function (Get $get) {
                            $pautasIds = $get('pautas') ?? [];

                            if (empty($pautasIds)) {
                                return [];
                            }

                            $seriesIds = Pauta::whereIn('id', $pautasIds)
                                ->pluck('id_serie')
                                ->unique()
                                ->toArray();

                            if (empty($seriesIds)) {
                                return [];
                            }

                            return Turma::with('serie', 'escola')
                                ->whereIn('id_serie', $seriesIds)
                                ->get()
                                ->mapWithKeys(fn($turma) => [
                                    $turma->id => "{$turma->escola->nome} - {$turma->serie->nome} - Turma {$turma->nome}"
                                ])
                                ->toArray();
                        })
                        ->required()
                        ->searchable()
                        ->preload()
                        ->live()
                        ->visible(fn(Get $get) => !$get('todas_turmas'))
                        ->disabled(fn(Get $get) => empty($get('pautas')))
                        ->placeholder(fn(Get $get) => empty($get('pautas'))
                            ? 'Selecione as pautas primeiro'
                            : 'Selecione as turmas')
                        ->helperText('Turmas filtradas pela série das pautas selecionadas')
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Informações da Avaliação')
                ->icon('heroicon-o-information-circle')
                ->schema([
                    Forms\Components\TextInput::make('descricao')
                        ->label('Descrição')
                        ->maxLength(255)
                        ->placeholder('Ex: Avaliação Diagnóstica - 1º Bimestre'),

                    Forms\Components\DatePicker::make('data_aplicacao')
                        ->label('Data de Aplicação')
                        ->default(now()),

                    Forms\Components\Select::make('status')
                        ->label('Status')
                        ->options([
                            'aberta' => 'Aberta',
                            'fechada' => 'Fechada',
                        ])
                        ->default('aberta')
                        ->required(),
                ])
                ->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('avaliacoes.created_at', 'desc')
            ->modifyQueryUsing(function ($query) {

                $query
                    ->select([
                        'avaliacoes.*',
                        'avaliacao_turma.id_turma',
                        'turmas.nome as turma_nome',
                        'turmas.turno as turma_turno',
                        'escolas.nome as escola_nome',
                        'series.nome as serie_nome',
                    ])
                    ->join('avaliacao_turma', 'avaliacoes.id', '=', 'avaliacao_turma.id_avaliacao')
                    ->join('turmas', 'avaliacao_turma.id_turma', '=', 'turmas.id')
                    ->join('escolas', 'turmas.id_escola', '=', 'escolas.id')
                    ->join('series', 'turmas.id_serie', '=', 'series.id');

                // Admin vê tudo
                if (self::ehAdmin()) {
                    return $query;
                }

                // Professor: filtra pelos vínculos
                $dados = self::getDadosProfessor();

                if (empty($dados['turmas']) || empty($dados['componentes'])) {
                    $query->whereRaw('1 = 0');
                    return $query;
                }

                // Filtra pelas turmas do professor
                $query->whereIn('avaliacao_turma.id_turma', $dados['turmas']);

                // Filtra pelas avaliações que têm pautas dos componentes do professor
                $query->whereExists(function ($subquery) use ($dados) {
                    $subquery->select(DB::raw(1))
                        ->from('avaliacao_pauta')
                        ->join('pautas', 'avaliacao_pauta.id_pauta', '=', 'pautas.id')
                        ->whereColumn('avaliacao_pauta.id_avaliacao', 'avaliacoes.id')
                        ->whereIn('pautas.id_componente_curricular', $dados['componentes']);
                });

                return $query;
            })
            ->columns([
                Tables\Columns\TextColumn::make('escola_nome')
                    ->label('Escola')
                    ->searchable(query: function ($query, $search) {
                        $query->where('escolas.nome', 'like', "%{$search}%");
                    })

                    ->sortable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('serie_nome')
                    ->label('Série')
                    ->searchable(
                        query: fn($query, $search) =>
                        $query->where('series.nome', 'like', "%{$search}%")
                    )
                    ->sortable(),

                Tables\Columns\TextColumn::make('turma_nome')
                    ->label('Turma')
                    ->searchable(
                        query: fn($query, $search) =>
                        $query->where('turmas.nome', 'like', "%{$search}%")
                    )
                    ->sortable(),

                Tables\Columns\TextColumn::make('turma_turno')
                    ->label('Turno')
                    ->badge()
                    ->formatStateUsing(fn(string $state) => match ($state) {
                        'manha' => 'Manhã',
                        'tarde' => 'Tarde',
                        'noite' => 'Noite',
                        'integral' => 'Integral',
                        default => $state,
                    })
                    ->color(fn(string $state) => match ($state) {
                        'manha' => 'warning',
                        'tarde' => 'success',
                        'noite' => 'gray',
                        'integral' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('descricao')
                    ->label('Descrição')
                    ->sortable()
                    ->searchable()
                    ->wrap()
                    ->limit(40),

                Tables\Columns\TextColumn::make('pautas_count')
                    ->label('Pautas')
                    ->counts('pautas')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('data_aplicacao')
                    ->label('Data')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state) => match ($state) {
                        'aberta' => 'success',
                        'fechada' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state) => ucfirst($state)),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'aberta' => 'Aberta',
                        'fechada' => 'Fechada',
                    ]),

                Tables\Filters\SelectFilter::make('id_turma')
                    ->label('Turma')
                    ->options(function () {
                        return Turma::with('serie', 'escola')
                            ->get()
                            ->mapWithKeys(fn($turma) => [
                                $turma->id => "{$turma->escola->nome} - {$turma->serie->nome} - {$turma->nome}"
                            ])
                            ->toArray();
                    })
                    ->query(function ($query, array $data) {
                        if (filled($data['value'])) {
                            $query->where('avaliacao_turma.id_turma', $data['value']);
                        }
                    })
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\Action::make('avaliar')
                    ->label('Avaliar')
                    ->icon('heroicon-o-pencil-square')
                    ->color('success')
                    ->slideOver()
                    ->modalWidth('5xl')

                    // PASSA A TURMA JUNTO
                    ->action(function (Avaliacao $record, array $data, Tables\Actions\Action $action) {

                        $turmaId = data_get($record, 'id_turma');

                        $action->mount([
                            'turma_id' => $turmaId,
                        ]);
                    })

                    ->modalHeading(
                        fn($record) =>
                        "{$record->escola_nome} - {$record->serie_nome} - Turma {$record->turma_nome}"
                    )

                    ->modalContent(function (Avaliacao $record, Tables\Actions\Action $action) {

                        $turmaId = $record->id_turma;
                        
                        $turma = Turma::findOrFail($turmaId);

                        $alunos = Aluno::where('id_turma', $turmaId)
                            ->where('situacao', 'Matriculado')
                            ->orderBy('nome')
                            ->get();

                        // === PAUTAS CERTAS ===

                        if (self::ehAdmin()) {

                            $pautas = $record->pautas()
                                ->with('tipoAvaliacao', 'componenteCurricular')
                                ->orderBy('id')
                                ->get();
                        } else {

                            $componentesNaTurma = self::getComponentesPorTurma($turmaId);

                            $pautas = Pauta::whereIn('id_componente_curricular', $componentesNaTurma)
                                ->where('id_serie', $turma->id_serie)
                                ->with('tipoAvaliacao', 'componenteCurricular')
                                ->orderBy('id')
                                ->get();
                        }
                        $alternativasPorTipo = [];
                        $alternativasComObservacao = [];

                        foreach ($pautas as $pauta) {

                            $tipoId = $pauta->id_tipo_avaliacao;

                            if (!isset($alternativasPorTipo[$tipoId])) {

                                $alternativasPorTipo[$tipoId] = Alternativa::where('id_tipo_avaliacao', $tipoId)
                                    ->orderBy('id')
                                    ->get();

                                foreach ($alternativasPorTipo[$tipoId] as $alt) {
                                    if ($alt->tem_observacao) {
                                        $alternativasComObservacao[] = $alt->id;
                                    }
                                }
                            }
                        }


                        $respostasExistentes = [];
                        $observacoesExistentes = [];

                        $respostas = Resposta::where('id_avaliacao', $record->id)
                            ->whereIn('id_aluno', $alunos->pluck('id'))
                            ->get();

                        foreach ($respostas as $resposta) {

                            $respostasExistentes[$resposta->id_aluno][$resposta->id_pauta]
                                = $resposta->id_alternativa;

                            if ($resposta->observacao) {
                                $observacoesExistentes[$resposta->id_aluno][$resposta->id_pauta]
                                    = $resposta->observacao;
                            }
                        }


                        $pautasArray = $pautas->map(fn($p) => [
                            'id' => $p->id,
                            'pauta' => $p->pauta,
                            'tipo_avaliacao' => $p->tipoAvaliacao->tipo_avaliacao,
                            'id_tipo_avaliacao' => $p->id_tipo_avaliacao,
                            'componente' => $p->componenteCurricular->nome,
                        ])->values()->toArray();

                        return view('filament.modals.avaliar-turma', [
                            'avaliacao' => $record,
                            'turmaId' => $turmaId,
                            'turma' => $turma,
                            'alunos' => $alunos,
                            'pautas' => $pautas,
                            'pautasArray' => $pautasArray,
                            'alternativasPorTipo' => $alternativasPorTipo,
                            'alternativasComObservacao' => $alternativasComObservacao,
                            'respostasExistentes' => $respostasExistentes,
                            'observacoesExistentes' => $observacoesExistentes,
                        ]);
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Fechar')
                    ->visible(fn(Avaliacao $record) => $record->status === 'aberta'),

                Tables\Actions\EditAction::make()
                    ->visible(fn() => self::ehAdmin()),

                Tables\Actions\DeleteAction::make()
                    ->visible(fn() => self::ehAdmin()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn() => self::ehAdmin()),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageAvaliacaos::route('/'),
        ];
    }
}
