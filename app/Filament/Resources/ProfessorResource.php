<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProfessorResource\Pages;
use App\Models\Professor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Services\UserService;
use Illuminate\Support\Facades\Auth;

class ProfessorResource extends Resource
{
    protected static ?string $model = Professor::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    public static ?string $modelLabel = 'Professor';
    protected static ?string $navigationGroup = "Gestão Escolar";
    public static ?string $pluralModelLabel = 'Professores';
    public static ?string $slug = 'professores';
    public static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        $userService = app(UserService::class);
        $ehAdmin = $userService->ehAdmin(Auth::user());

        return $form
            ->schema([
                Forms\Components\Section::make('Dados do Professor')
                    ->schema([
                        Forms\Components\Select::make('id_escola')
                            ->label('Escola')
                            ->relationship('escola', 'nome')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->placeholder('Selecione a escola')
                            ->disabled(fn(?Professor $record) => $record !== null && !$ehAdmin)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('matricula')
                            ->label('Matrícula')
                            ->required()
                            ->disabled(fn(?Professor $record) => $record !== null && !$ehAdmin)
                            ->maxLength(255)
                            ->placeholder('Ex: PROF001'),

                        Forms\Components\TextInput::make('nome')
                            ->label('Nome Completo')
                            ->required()
                            ->disabled(fn(?Professor $record) => $record !== null && !$ehAdmin)
                            ->maxLength(255)
                            ->placeholder('Ex: João da Silva'),

                        Forms\Components\TextInput::make('email')
                            ->label('E-mail')
                            ->email()
                            ->maxLength(255)
                            ->placeholder('professor@exemplo.com'),

                        Forms\Components\TextInput::make('telefone')
                            ->label('Telefone')
                            ->tel()
                            ->maxLength(255)
                            ->mask('(99) 99999-9999')
                            ->placeholder('(00) 00000-0000'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $user = Auth::user();
                $query = app(UserService::class)->aplicarFiltroPorEscolaDoUsuario($query, $user);

                return $query->whereNull('funcao_administrativa_id');
            })
            ->columns([
                Tables\Columns\TextColumn::make('escola.nome')
                    ->label('Escola')
                    ->sortable()
                    ->wrap(),

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

                Tables\Columns\TextColumn::make('email')
                    ->label('E-mail')
                    ->searchable()
                    ->copyable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('telefone')
                    ->label('Telefone')
                    ->searchable()
                    ->placeholder('Não informado')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('turmas_count')
                    ->label('Qtd. Turmas')
                    ->counts('turmas')
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color('success'),

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
                Tables\Filters\SelectFilter::make('id_escola')
                    ->label('Escola')
                    ->relationship('escola', 'nome')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('turmas')
                    ->label('Turma')
                    ->relationship('turmas', 'nome')
                    ->searchable()
                    ->preload()
                    ->multiple(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalHeading(fn($record) => "Detalhes - {$record->nome}")
                    ->modalWidth('4xl')
                    ->infolist([
                        \Filament\Infolists\Components\Section::make('Informações do Professor')
                            ->schema([
                                \Filament\Infolists\Components\TextEntry::make('escola.nome')
                                    ->label('Escola'),
                                \Filament\Infolists\Components\TextEntry::make('matricula')
                                    ->label('Matrícula')
                                    ->copyable(),
                                \Filament\Infolists\Components\TextEntry::make('nome')
                                    ->label('Nome')
                                    ->copyable(),
                                \Filament\Infolists\Components\TextEntry::make('email')
                                    ->label('E-mail')
                                    ->copyable(),
                                \Filament\Infolists\Components\TextEntry::make('telefone')
                                    ->label('Telefone')
                                    ->placeholder('Não informado'),
                            ])
                            ->columns(3),

                        \Filament\Infolists\Components\Section::make('Turmas')
                            ->schema([
                                \Filament\Infolists\Components\TextEntry::make('turmas_lista')
                                    ->label('')
                                    ->getStateUsing(function ($record) {
                                        return $record->id; // Só precisa retornar algo para o formatStateUsing funcionar
                                    })
                                    ->formatStateUsing(function ($state, $record) {
                                        $turmas = \App\Models\Turma::whereHas('componentes', function ($query) use ($record) {
                                            $query->where('turma_componente_professor.professor_id', $record->id);
                                        })->with(['serie', 'escola', 'componentes' => function ($query) use ($record) {
                                            $query->wherePivot('professor_id', $record->id);
                                        }])->get();

                                        if ($turmas->isEmpty()) {
                                            return 'Não leciona em nenhuma turma';
                                        }

                                        $html = '<div class="space-y-3">';

                                        foreach ($turmas as $turma) {
                                            $componentes = $turma->componentes->pluck('nome')->join(', ');
                                            $turno = match ($turma->turno) {
                                                'manha' => 'Manhã',
                                                'tarde' => 'Tarde',
                                                'noite' => 'Noite',
                                                'integral' => 'Integral',
                                                default => $turma->turno,
                                            };

                                            $html .= '
                                <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                                    <div class="font-semibold text-primary-600 dark:text-primary-400">
                                        ' . e($turma->serie->nome) . ' - Turma ' . e($turma->nome) . '
                                    </div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                        <span class="font-medium">Escola:</span> ' . e($turma->escola->nome) . '
                                    </div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400">
                                        <span class="font-medium">Turno:</span> ' . e($turno) . '
                                    </div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400">
                                        <span class="font-medium">Componentes:</span> ' . e($componentes) . '
                                    </div>
                                </div>
                            ';
                                        }

                                        $html .= '</div>';

                                        return new \Illuminate\Support\HtmlString($html);
                                    })
                                    ->columnSpanFull(),
                            ]),
                    ]),

                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ManageProfessors::route('/'),
        ];
    }
}
