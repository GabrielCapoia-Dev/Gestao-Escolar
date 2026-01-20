<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TurmaResource\Pages;
use AlperenErsoy\FilamentExport\Actions\FilamentExportBulkAction;
use App\Models\Turma;
use App\Models\Professor;
use App\Services\UserService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class TurmaResource extends Resource
{
    protected static ?string $model = Turma::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    public static ?string $modelLabel = 'Turma';
    protected static ?string $navigationGroup = "Gestão Escolar";
    public static ?string $pluralModelLabel = 'Turmas';
    public static ?string $slug = 'turmas';

    public static ?int $navigationSort = 0;

    public static function form(Form $form): Form
    {
        $userService = app(UserService::class);
        $ehAdmin = $userService->ehAdmin(Auth::user());

        return $form
            ->schema([
                Forms\Components\Section::make('Dados da Turma')
                    ->schema([
                        Forms\Components\Select::make('id_escola')
                            ->label('Escola')
                            ->relationship('escola', 'nome')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->placeholder('Selecione a escola')
                            ->disabled(fn(?Turma $record) => $record !== null && !$ehAdmin)
                            ->columnSpanFull(),

                        Forms\Components\Select::make('id_serie')
                            ->label('Série')
                            ->relationship('serie', 'nome')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if (!$state) {
                                    $set('componentes', []);
                                    return;
                                }

                                $serie = \App\Models\Serie::with('componentesCurriculares')->find($state);
                                if (!$serie) {
                                    $set('componentes', []);
                                    return;
                                }

                                $componentes = $serie->componentesCurriculares->map(function ($componente) {
                                    return [
                                        'componente_curricular_id' => $componente->id,
                                        'componente_nome' => $componente->nome,
                                        'professor_id' => null,
                                    ];
                                })->toArray();

                                $set('componentes', $componentes);
                            })
                            ->placeholder('Selecione a série')
                            ->disabled(fn(?Turma $record) => $record !== null && !$ehAdmin)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('nome')
                            ->label('Letra da Turma')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Ex: A, B, C')
                            ->hint('Apenas a letra/identificador da turma')
                            ->disabled(fn(?Turma $record) => $record !== null && !$ehAdmin),

                        Forms\Components\Select::make('turno')
                            ->label('Turno')
                            ->options([
                                'manha' => 'Manhã',
                                'tarde' => 'Tarde',
                                'noite' => 'Noite',
                                'integral' => 'Integral',
                            ])
                            ->required()
                            ->placeholder('Selecione o turno')
                            ->disabled(fn(?Turma $record) => $record !== null && !$ehAdmin),

                        Forms\Components\Hidden::make('codigo')
                            ->default(fn() => 'TUR' . str_pad(Turma::max('id') + 1, 3, '0', STR_PAD_LEFT)),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Professores por Componente')
                    ->schema([
                        Forms\Components\Placeholder::make('aviso')
                            ->label('')
                            ->content('Selecione a escola e a série para carregar os componentes curriculares')
                            ->visible(fn(Get $get) => !$get('id_serie') || !$get('id_escola')),

                        Forms\Components\Repeater::make('componentes')
                            ->label('')
                            ->schema([
                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('componente_nome')
                                            ->label('Componente Curricular')
                                            ->disabled()
                                            ->dehydrated(false),

                                        Forms\Components\Select::make('professor_id')
                                            ->label('Professor')
                                            ->options(function (Get $get) {
                                                $escolaId = $get('../../id_escola');
                                                if (!$escolaId) {
                                                    return [];
                                                }
                                                return Professor::where('id_escola', $escolaId)
                                                    ->pluck('nome', 'id')
                                                    ->toArray();
                                            })
                                            ->searchable()
                                            ->placeholder('Selecione o professor')
                                            ->disabled(fn(Get $get) => $get('tem_professor'))
                                            ->dehydrated(fn(Get $get) => !$get('tem_professor')),

                                        Forms\Components\Checkbox::make('tem_professor')
                                            ->label('Não tem Professor?')
                                            ->default(false)
                                            ->live()
                                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                                if ($state) {
                                                    $set('professor_id', null);
                                                }
                                            }),
                                        Forms\Components\Hidden::make('componente_curricular_id'),
                                    ]),
                            ])
                            ->visible(fn(Get $get) => $get('id_serie') && $get('id_escola'))
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->columnSpanFull(),
                    ])
                    ->visible(fn(Get $get) => $get('id_serie') && $get('id_escola')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $user = Auth::user();
                })
            ->columns([
                Tables\Columns\TextColumn::make('escola.nome')
                    ->label('Escola')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('serie.nome')
                    ->label('Série')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('nome')
                    ->label('Turma')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('turno')
                    ->label('Turno')
                    ->badge()
                    ->formatStateUsing(fn(string $state) => match ($state) {
                        'manha' => 'Manhã',
                        'tarde' => 'Tarde',
                        'noite' => 'Noite',
                        'integral' => 'Integral',
                        default => ucfirst($state),
                    })
                    ->color(fn(string $state) => match ($state) {
                        'manha' => 'info',
                        'tarde' => 'warning',
                        'noite' => 'gray',
                        'integral' => 'success',
                        default => 'secondary',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('id_serie')
                    ->label('Série')
                    ->relationship('serie', 'nome')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('id_escola')
                    ->label('Escola')
                    ->relationship('escola', 'nome')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('turno')
                    ->label('Turno')
                    ->options([
                        'manha' => 'Manhã',
                        'tarde' => 'Tarde',
                        'noite' => 'Noite',
                        'integral' => 'Integral',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->fillForm(function ($record): array {
                        $serie = \App\Models\Serie::with('componentesCurriculares')->find($record->id_serie);
                        $componentesVinculados = $record->componentes()->withPivot('professor_id', 'tem_professor')->get()->keyBy('id');

                        $componentesData = $serie->componentesCurriculares->map(function ($componente) use ($componentesVinculados) {
                            $professorId = $componentesVinculados->has($componente->id)
                                ? $componentesVinculados->get($componente->id)->pivot->professor_id
                                : null;
                            $temProfessor = $componentesVinculados->has($componente->id)
                                ? $componentesVinculados->get($componente->id)->pivot->tem_professor
                                : false;

                            return [
                                'componente_curricular_id' => $componente->id,
                                'componente_nome' => $componente->nome,
                                'professor_id' => $professorId,
                                'tem_professor' => $temProfessor,
                            ];
                        })->toArray();

                        return [
                            'codigo' => $record->codigo,
                            'nome' => $record->nome,
                            'turno' => $record->turno,
                            'id_serie' => $record->id_serie,
                            'id_escola' => $record->id_escola,
                            'componentes' => $componentesData,
                        ];
                    })
                    ->using(function ($record, array $data) {
                        $componentes = $data['componentes'] ?? [];
                        unset($data['componentes']);

                        $record->update($data);

                        $syncData = [];
                        foreach ($componentes as $componente) {
                            if (isset($componente['componente_curricular_id'])) {
                                $syncData[$componente['componente_curricular_id']] = [
                                    'professor_id' => $componente['professor_id'] ?? null,
                                    'tem_professor' => $componente['tem_professor'] ?? false,
                                ];
                            }
                        }

                        $record->componentes()->sync($syncData);

                        return $record;
                    }),

                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                FilamentExportBulkAction::make('exportar_filtrados')
                    ->label('Exportar XLSX')
                    ->defaultFormat('xlsx')
                    ->directDownload(),
            ])
            ->defaultSort('escola.nome');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageTurmas::route('/'),
        ];
    }
}
