<?php
// app/Filament/Resources/AlunoResource.php

namespace App\Filament\Resources;

use App\Filament\Resources\AlunoResource\Pages;
use App\Models\Aluno;
use App\Models\Escola;
use App\Models\Turma;
use App\Services\UserService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use AlperenErsoy\FilamentExport\Actions\FilamentExportBulkAction;
use Filament\Tables\Table;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;

class AlunoResource extends Resource
{
    protected static ?string $model = Aluno::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Alunos';
    protected static ?string $pluralModelLabel = 'Alunos';
    protected static ?string $navigationGroup = "Gestão Escolar";
    protected static ?string $slug = 'alunos';

    public static function form(Form $form): Form
    {
        $userService = app(UserService::class);
        $ehAdmin = $userService->ehAdmin(Auth::user());

        return $form->schema([
            // SEÇÃO: ESCOLA
            Forms\Components\Section::make('Unidade Escolar')
                ->icon('heroicon-o-building-library')
                ->schema([
                    Forms\Components\Select::make('id_escola')
                        ->label('Escola')
                        ->options(Escola::orderBy('nome')->pluck('nome', 'id'))
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live()
                        ->afterStateUpdated(fn(Set $set) => $set('id_turma', null))
                        ->afterStateHydrated(function (Set $set, ?Aluno $record) {
                            if ($record?->turma) {
                                $set('id_escola', $record->turma->id_escola);
                            }
                        })
                        ->dehydrated(false)
                        // Bloqueia escola para não-admin na edição
                        ->disabled(
                            fn(string $context): bool =>
                            $context === 'edit' && !$ehAdmin
                        )
                        ->helperText(
                            fn(string $context): ?string =>
                            $context === 'edit' && !$ehAdmin
                                ? 'Para transferir o aluno de escola, entre em contato com o administrador.'
                                : null
                        )
                        ->columnSpanFull(),
                ]),

            // SEÇÃO: TURMA
            Forms\Components\Section::make('Turma')
                ->icon('heroicon-o-user-group')
                ->schema([
                    Forms\Components\Select::make('id_turma')
                        ->label('Série / Turma')
                        ->options(function (Get $get) {
                            $escolaId = $get('id_escola');

                            if (!$escolaId) {
                                return [];
                            }

                            return Turma::where('id_escola', $escolaId)
                                ->with('serie')
                                ->orderBy('id_serie')
                                ->get()
                                ->mapWithKeys(fn($turma) => [
                                    $turma->id => "{$turma->serie->nome} - Turma {$turma->nome} (" . match ($turma->turno) {
                                        'manha' => 'Manhã',
                                        'tarde' => 'Tarde',
                                        'noite' => 'Noite',
                                        'integral' => 'Integral',
                                        default => $turma->turno,
                                    } . ")"
                                ])
                                ->toArray();
                        })
                        ->searchable()
                        ->required()
                        ->live()
                        ->disabled(fn(Get $get) => !$get('id_escola'))
                        ->placeholder(
                            fn(Get $get) => !$get('id_escola')
                                ? 'Selecione a escola primeiro'
                                : 'Selecione a turma'
                        )
                        ->columnSpanFull(),
                ]),

            // SEÇÃO: DADOS DO ALUNO
            Forms\Components\Section::make('Dados do Aluno')
                ->icon('heroicon-o-identification')
                ->schema([
                    Forms\Components\TextInput::make('cgm')
                        ->label('CGM')
                        ->required()
                        ->maxLength(255)
                        ->disabled(fn(string $context): bool => $context === 'edit')
                        ->placeholder('Código Geral de Matrícula'),

                    Forms\Components\TextInput::make('nome')
                        ->label('Nome Completo')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('Nome do aluno'),

                    Forms\Components\Select::make('sexo')
                        ->label('Sexo')
                        ->required()
                        ->options([
                            'M' => 'Masculino',
                            'F' => 'Feminino',
                        ]),

                    Forms\Components\DatePicker::make('data_nascimento')
                        ->label('Data de Nascimento')
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (Set $set, ?string $state) {
                            if ($state) {
                                $set('idade', Carbon::parse($state)->age);
                            }
                        })
                        ->maxDate(now()),

                    Forms\Components\TextInput::make('idade')
                        ->label('Idade')
                        ->disabled()
                        ->dehydrated(true)
                        ->suffix('anos'),
                ])
                ->columns(2),

            // SEÇÃO: MATRÍCULA
            Forms\Components\Section::make('Informações da Matrícula')
                ->icon('heroicon-o-document-text')
                ->schema([
                    Forms\Components\DatePicker::make('data_matricula')
                        ->label('Data da Matrícula')
                        ->required()
                        ->default(now()),

                    Forms\Components\Select::make('situacao')
                        ->label('Situação')
                        ->required()
                        ->default('Matriculado')
                        ->options([
                            'Matriculado' => 'Matriculado',
                            'Desistente' => 'Desistente',
                            'Remanejado' => 'Remanejado',
                            'Transferência' => 'Transferência',
                        ])
                        // Na edição, se não for admin, desabilita (será calculado automaticamente)
                        ->disabled(
                            fn(string $context): bool =>
                            $context === 'edit' && !$ehAdmin
                        )
                        ->helperText(
                            fn(string $context): ?string =>
                            $context === 'edit' && !$ehAdmin
                                ? 'A situação será definida automaticamente com base na alteração.'
                                : null
                        ),
                ])
                ->columns(2),

            // Campo oculto para armazenar dados originais (usado na lógica de histórico)
            Forms\Components\Hidden::make('_turma_original_id'),
            Forms\Components\Hidden::make('_escola_original_id'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->modifyQueryUsing(fn(Builder $query) => $query->ativos()) // Só mostra registros ativos
            ->columns([
                Tables\Columns\TextColumn::make('turma.escola.nome')
                    ->label('Escola')
                    ->sortable()
                    ->wrap()
                    ->searchable(),

                Tables\Columns\TextColumn::make('turma.serie.nome')
                    ->label('Série')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('turma.nome')
                    ->label('Turma')
                    ->sortable(),

                Tables\Columns\TextColumn::make('turma.turno')
                    ->label('Turno')
                    ->badge()
                    ->formatStateUsing(fn(string $state) => match ($state) {
                        'manha' => 'Manhã',
                        'tarde' => 'Tarde',
                        'noite' => 'Noite',
                        'integral' => 'Integral',
                        default => $state,
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('cgm')
                    ->label('CGM')
                    ->sortable()
                    ->copyable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('nome')
                    ->label('Aluno')
                    ->wrap()
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('idade')
                    ->label('Idade')
                    ->suffix(' anos')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('situacao')
                    ->label('Situação')
                    ->badge()
                    ->color(fn(string $state) => match ($state) {
                        'Matriculado' => 'success',
                        'Desistente' => 'danger',
                        'Remanejado' => 'warning',
                        'Transferência' => 'info',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('data_matricula')
                    ->label('Matrícula')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Atualizado')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('escola')
                    ->label('Escola')
                    ->options(Escola::orderBy('nome')->pluck('nome', 'id'))
                    ->query(function ($query, array $state) {
                        if (filled($state['value'])) {
                            $query->whereHas('turma', fn($q) => $q->where('id_escola', $state['value']));
                        }
                    }),

                Tables\Filters\SelectFilter::make('serie')
                    ->label('Série')
                    ->options(\App\Models\Serie::orderBy('nome')->pluck('nome', 'id'))
                    ->query(function ($query, array $state) {
                        if (filled($state['value'])) {
                            $query->whereHas('turma', fn($q) => $q->where('id_serie', $state['value']));
                        }
                    }),

                Tables\Filters\SelectFilter::make('turno')
                    ->label('Turno')
                    ->options([
                        'manha' => 'Manhã',
                        'tarde' => 'Tarde',
                        'noite' => 'Noite',
                        'integral' => 'Integral',
                    ])
                    ->query(function ($query, array $state) {
                        if (filled($state['value'])) {
                            $query->whereHas('turma', fn($q) => $q->where('turno', $state['value']));
                        }
                    }),

                Tables\Filters\SelectFilter::make('sexo')
                    ->options([
                        'M' => 'Masculino',
                        'F' => 'Feminino',
                    ]),

                Tables\Filters\SelectFilter::make('situacao')
                    ->label('Situação')
                    ->options([
                        'Matriculado' => 'Matriculado',
                        'Desistente' => 'Desistente',
                        'Remanejado' => 'Remanejado',
                        'Transferência' => 'Transferência',
                    ]),
            ])
            ->actions([
                // No AlunoResource.php, na action de histórico:

                Tables\Actions\Action::make('historico')
                    ->label('Histórico')
                    ->icon('heroicon-o-clock')
                    ->color('gray')
                    ->slideOver()
                    ->modalHeading(fn(Aluno $record) => "Histórico de Matrícula")
                    ->modalDescription(fn(Aluno $record) => "{$record->nome} • CGM: {$record->cgm}")
                    ->modalIcon('heroicon-o-academic-cap')
                    ->modalContent(fn(Aluno $record) => view('filament.modals.historico-aluno', [
                        'historico' => $record->getHistoricoCompleto(),
                    ])),

                Tables\Actions\EditAction::make()
                    ->fillForm(function (Aluno $record): array {
                        return [
                            'id_escola' => $record->turma?->id_escola,
                            'id_turma' => $record->id_turma,
                            'cgm' => $record->cgm,
                            'nome' => $record->nome,
                            'data_nascimento' => $record->data_nascimento,
                            'idade' => $record->idade,
                            'sexo' => $record->sexo,
                            'situacao' => $record->situacao,
                            'data_matricula' => $record->data_matricula,
                            '_turma_original_id' => $record->id_turma,
                            '_escola_original_id' => $record->turma?->id_escola,
                        ];
                    })
                    ->using(function (Aluno $record, array $data): Aluno {
                        $userService = app(\App\Services\UserService::class);
                        $ehAdmin = $userService->ehAdmin(\Illuminate\Support\Facades\Auth::user());

                        $turmaOriginalId = $data['_turma_original_id'];
                        $escolaOriginalId = $data['_escola_original_id'];
                        $novaTurmaId = $data['id_turma'];

                        $novaTurma = \App\Models\Turma::find($novaTurmaId);
                        $novaEscolaId = $novaTurma?->id_escola;

                        $mudouTurma = $turmaOriginalId != $novaTurmaId;
                        $mudouEscola = $escolaOriginalId != $novaEscolaId;

                        // Define situação automaticamente se não for admin
                        if (!$ehAdmin && ($mudouTurma || $mudouEscola)) {
                            $data['situacao'] = $mudouEscola ? 'Transferência' : 'Remanejado';
                        }

                        // Verifica se houve alteração
                        $camposVerificar = ['id_turma', 'nome', 'data_nascimento', 'sexo', 'situacao', 'data_matricula'];
                        $alterou = false;

                        foreach ($camposVerificar as $campo) {
                            $original = $record->{$campo};
                            $novo = $data[$campo] ?? null;

                            if ($original instanceof \Carbon\Carbon) {
                                $original = $original->format('Y-m-d');
                            }
                            if ($novo instanceof \Carbon\Carbon) {
                                $novo = $novo->format('Y-m-d');
                            }

                            if ($original != $novo) {
                                $alterou = true;
                                break;
                            }
                        }

                        if ($alterou) {
                            // Desativa registro atual
                            $record->update(['ativo' => false]);

                            // Cria novo registro
                            return Aluno::create([
                                'id_turma' => $data['id_turma'],
                                'cgm' => $record->cgm,
                                'nome' => $data['nome'],
                                'data_nascimento' => $data['data_nascimento'],
                                'idade' => $data['idade'],
                                'sexo' => $data['sexo'],
                                'situacao' => $data['situacao'],
                                'data_matricula' => $data['data_matricula'],
                                'ativo' => true,
                                'registro_anterior_id' => $record->id,
                            ]);
                        }

                        return $record;
                    }),

                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                FilamentExportBulkAction::make('exportar_filtrados')
                    ->label('Exportar XLSX')
                    ->defaultFormat('xlsx')
                    ->directDownload(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageAlunos::route('/'),
        ];
    }
}
