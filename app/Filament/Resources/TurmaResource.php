<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TurmaResource\Pages;
use App\Models\Turma;
use App\Models\Professor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use App\Services\UserService;
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

    public static function form(Form $form): Form
    {
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
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('nome')
                            ->label('Letra da Turma')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Ex: A, B, C')
                            ->hint('Apenas a letra/identificador da turma'),

                        Forms\Components\Select::make('turno')
                            ->label('Turno')
                            ->options([
                                'manha' => 'Manhã',
                                'tarde' => 'Tarde',
                                'noite' => 'Noite',
                                'integral' => 'Integral',
                            ])
                            ->required()
                            ->placeholder('Selecione o turno'),

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
                                            ->disabled(fn(Get $get) => !$get('tem_professor')),

                                        Forms\Components\Checkbox::make('tem_professor')
                                            ->label('Tem Professor?')
                                            ->live()
                                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                                if (!$state) {
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
                return app(UserService::class)->aplicarFiltroPorEscolaDoUsuario($query, $user);
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
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Atualizado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->since()
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
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('updated_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageTurmas::route('/'),
        ];
    }
}
