<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FuncionarioAdministrativoResource\Pages;
use App\Models\FuncaoAdministrativa;
use App\Models\EquipeGestora;
use App\Models\Professor;
use App\Models\Turma;
use App\Services\UserService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class FuncionarioAdministrativoResource extends Resource
{
    protected static ?string $model = EquipeGestora::class;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';
    public static ?string $modelLabel = 'Equipe Gestora';
    protected static ?string $navigationGroup = "Gestão Escolar";
    public static ?string $pluralModelLabel = 'Equipe Gestora';
    public static ?string $slug = 'equipe-gestora';
    public static ?int $navigationSort = 7;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Formulário de CRIAÇÃO - seleciona professor existente
                Forms\Components\Section::make('Selecionar Professor')
                    ->schema([
                        Forms\Components\Select::make('professor_id')
                            ->label('Professor')
                            ->options(function () {
                                $user = Auth::user();
                                $userService = app(UserService::class);

                                $query = Professor::query()
                                    ->whereNull('funcao_administrativa_id');

                                // Aplica filtro por escola se não for admin
                                if (!$userService->ehAdmin($user) && $user->id_escola) {
                                    $query->where('id_escola', $user->id_escola);
                                }

                                return $query->get()
                                    ->mapWithKeys(fn($prof) => [
                                        $prof->id => "{$prof->nome} - {$prof->matricula} ({$prof->escola->nome})"
                                    ]);
                            })
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Set $set, ?string $state) {
                                if ($state) {
                                    $professor = Professor::with('escola')->find($state);
                                    if ($professor) {
                                        $set('id_escola', $professor->id_escola);
                                    }
                                }
                                $set('turmasFuncao', []);
                            })
                            ->placeholder('Selecione um professor para vincular')
                            ->helperText('Apenas professores sem função administrativa são listados')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn(?Professor $record) => $record === null),
                // Formulário de EDIÇÃO - mostra dados do professor (readonly)
                Forms\Components\Section::make('Dados do Professor')
                    ->schema([
                        Forms\Components\Placeholder::make('nome_display')
                            ->label('Nome')
                            ->content(fn(?Professor $record) => $record?->nome ?? '—'),

                        Forms\Components\Placeholder::make('matricula_display')
                            ->label('Matrícula')
                            ->content(fn(?Professor $record) => $record?->matricula ?? '—'),

                        Forms\Components\Placeholder::make('escola_display')
                            ->label('Escola')
                            ->content(fn(?Professor $record) => $record?->escola?->nome ?? '—'),

                        Forms\Components\Placeholder::make('email_display')
                            ->label('E-mail')
                            ->content(fn(?Professor $record) => $record?->email ?? 'Não informado'),
                    ])
                    ->columns(2)
                    ->visible(fn(?Professor $record) => $record !== null),


                // Função Administrativa (comum para criar e editar)
                Forms\Components\Section::make('Função Administrativa')
                    ->afterStateHydrated(function (?EquipeGestora $record, Set $set) {
                        if (!$record?->portaria) {
                            return;
                        }

                        [$numero, $ano] = explode('/', $record->portaria);

                        $set('portaria_numero', $numero);
                        $set('portaria_ano', $ano);
                    })
                    ->schema([
                        Forms\Components\Select::make('funcao_administrativa_id')
                            ->label('Função Administrativa')
                            ->relationship('funcaoAdministrativa', 'nome')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Set $set) {
                                $set('turmasFuncao', []);
                                $set('selecionar_todas_turmas', false);
                            })
                            ->columnSpan(1),

                        Forms\Components\Grid::make(2)
                            ->columnSpan(1)
                            ->schema([
                                Forms\Components\TextInput::make('portaria_numero')
                                    ->label('Nº Portaria')
                                    ->placeholder('001')
                                    ->required()
                                    ->numeric()
                                    ->maxLength(10)
                                    ->suffix('/')
                                    ->dehydrated(false)
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('portaria_ano')
                                    ->label('Ano Portaria')
                                    ->placeholder('2026')
                                    ->required()
                                    ->numeric()
                                    ->minLength(4)
                                    ->maxLength(4)
                                    ->dehydrated(false)
                                    ->rules(['integer', 'min:2000'])
                                    ->columnSpan(1),
                            ]),

                        Forms\Components\Hidden::make('portaria')
                            ->dehydrateStateUsing(function (Get $get) {
                                $numero = $get('portaria_numero');
                                $ano = $get('portaria_ano');

                                if (!$numero || !$ano) {
                                    return null;
                                }

                                return "{$numero}/{$ano}";
                            })
                            ->required(),

                        Forms\Components\Checkbox::make('selecionar_todas_turmas')
                            ->label('Unidade com apenas um(a) coordenador(a)')
                            ->live()
                            ->afterStateUpdated(function (Set $set, Get $get, bool $state, ?Professor $record) {
                                if ($state) {
                                    $idEscola = $record?->id_escola ?? $get('id_escola');

                                    if ($idEscola) {
                                        $todasTurmas = Turma::where('id_escola', $idEscola)
                                            ->pluck('id')
                                            ->toArray();

                                        $set('turmasFuncao', $todasTurmas);
                                    }
                                } else {
                                    $set('turmasFuncao', []);
                                }
                            })
                            ->visible(function (Get $get): bool {
                                $funcaoId = $get('funcao_administrativa_id');

                                if (!$funcaoId) {
                                    return false;
                                }

                                $funcao = FuncaoAdministrativa::find($funcaoId);
                                return $funcao?->tem_relacao_turma ?? false;
                            })
                            ->dehydrated(false)
                            ->columnSpanFull(),

                        Forms\Components\Select::make('turmasFuncao')
                            ->label('Turmas Vinculadas à Função')
                            ->relationship('turmasFuncao', 'nome')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->options(function (Get $get, ?Professor $record) {
                                $idEscola = $record?->id_escola ?? $get('id_escola');

                                if (!$idEscola) {
                                    return [];
                                }

                                return Turma::where('id_escola', $idEscola)
                                    ->with(['serie'])
                                    ->get()
                                    ->mapWithKeys(fn($turma) => [
                                        $turma->id => "{$turma->serie->nome} - {$turma->nome} ({$turma->turno})"
                                    ]);
                            })
                            ->visible(function (Get $get): bool {
                                $funcaoId = $get('funcao_administrativa_id');

                                if (!$funcaoId) {
                                    return false;
                                }

                                $funcao = FuncaoAdministrativa::find($funcaoId);
                                return $funcao?->tem_relacao_turma ?? false;
                            })
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                // Campo oculto para armazenar id_escola (usado nas options de turmas)
                Forms\Components\Hidden::make('id_escola'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $user = Auth::user();
                $query = app(UserService::class)->aplicarFiltroPorEscolaDoUsuario($query, $user);

                return $query->whereNotNull('funcao_administrativa_id');
            })
            ->columns([
                Tables\Columns\TextColumn::make('escola.nome')
                    ->label('Escola')
                    ->sortable()
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('matricula')
                    ->label('Matrícula')
                    ->searchable()
                    ->copyable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('nome')
                    ->label('Nome')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('funcaoAdministrativa.nome')
                    ->label('Função')
                    ->sortable(),

                Tables\Columns\TextColumn::make('portaria')
                    ->label('Portaria')
                    ->searchable()
                    ->copyable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('turmas_funcao_count')
                    ->label('Turmas')
                    ->counts('turmasFuncao')
                    ->badge()
                    ->color('info')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('turmas_vinculadas')
                    ->label('Turmas Vinculadas')
                    ->badge()
                    ->separator(',')
                    ->wrap()
                    ->getStateUsing(
                        fn($record) => $record->turmasFuncao()
                            ->with('serie')
                            ->get()
                            ->map(fn($t) => "{$t->serie->nome} - {$t->nome}")
                            ->toArray()
                    )
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('email')
                    ->label('E-mail')
                    ->searchable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('telefone')
                    ->label('Telefone')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Atualizado')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('id_escola')
                    ->label('Escola')
                    ->relationship('escola', 'nome')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('funcao_administrativa_id')
                    ->label('Função')
                    ->relationship('funcaoAdministrativa', 'nome')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalHeading(fn($record) => "Detalhes - {$record->nome}")
                    ->modalWidth('3xl')
                    ->infolist([
                        \Filament\Infolists\Components\Section::make('Informações do Funcionário')
                            ->schema([
                                \Filament\Infolists\Components\TextEntry::make('escola.nome')
                                    ->label('Escola'),
                                \Filament\Infolists\Components\TextEntry::make('matricula')
                                    ->label('Matrícula')
                                    ->copyable(),
                                \Filament\Infolists\Components\TextEntry::make('nome')
                                    ->label('Nome')
                                    ->copyable(),
                                \Filament\Infolists\Components\TextEntry::make('funcaoAdministrativa.nome')
                                    ->label('Função')
                                    ->badge()
                                    ->color('warning'),
                                \Filament\Infolists\Components\TextEntry::make('portaria')
                                    ->label('Portaria')
                                    ->placeholder('Não informada'),
                                \Filament\Infolists\Components\TextEntry::make('email')
                                    ->label('E-mail')
                                    ->copyable()
                                    ->placeholder('Não informado'),
                                \Filament\Infolists\Components\TextEntry::make('telefone')
                                    ->label('Telefone')
                                    ->placeholder('Não informado'),
                            ])
                            ->columns(3),

                        \Filament\Infolists\Components\Section::make('Turmas Vinculadas')
                            ->schema([
                                \Filament\Infolists\Components\TextEntry::make('turmas_funcao_info')
                                    ->label('')
                                    ->getStateUsing(function ($record) {
                                        $turmas = $record->turmasFuncao()->with('serie')->get();

                                        if ($turmas->isEmpty()) {
                                            return 'Nenhuma turma vinculada';
                                        }

                                        $html = '<div class="flex flex-wrap gap-2">';
                                        foreach ($turmas as $turma) {
                                            $html .= '<span class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-primary-100 text-primary-800 dark:bg-primary-800 dark:text-primary-100">'
                                                . e($turma->serie->nome) . ' - ' . e($turma->nome)
                                                . '</span>';
                                        }
                                        $html .= '</div>';

                                        return $html;
                                    })
                                    ->html()
                                    ->columnSpanFull(),
                            ])
                            ->visible(fn($record) => $record->funcaoAdministrativa?->tem_relacao_turma),
                    ]),

                Tables\Actions\EditAction::make(),

                // Action para remover função administrativa (volta a ser professor)
                Tables\Actions\Action::make('remover_funcao')
                    ->label('Remover Função')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger')
                    ->visible(function():bool {
                        /** @var \App\Models\User $user */
                        $user = Auth::user();
                        return $user->hasPermissionTo('Excluir Equipe Gestora');
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Remover Função Administrativa')
                    ->modalDescription(fn($record) => "Tem certeza que deseja remover a função administrativa de {$record->nome}? Ele voltará a aparecer na lista de professores.")
                    ->modalSubmitActionLabel('Sim, remover função')
                    ->action(function ($record) {
                        // Remove turmas vinculadas
                        $record->turmasFuncao()->detach();

                        // Remove função administrativa
                        $record->update([
                            'funcao_administrativa_id' => null,
                            'portaria' => null,
                        ]);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('updated_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageFuncionariosAdministrativos::route('/'),
        ];
    }
}
