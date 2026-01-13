<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProfessorResource\Pages;
use App\Filament\Resources\ProfessorResource\RelationManagers;
use App\Models\Professor;
use App\Models\Turma;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\Escola;
use Filament\Forms\Get;

class ProfessorResource extends Resource
{
    protected static ?string $model = Professor::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    public static ?string $modelLabel = 'Professor';
    protected static ?string $navigationGroup = "Gestão Escolar";
    public static ?string $pluralModelLabel = 'Professores';
    public static ?string $slug = 'professores';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Dados do Professor')
                    ->schema([
                        Forms\Components\TextInput::make('matricula')
                            ->label('Matrícula')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->placeholder('Ex: PROF001'),

                        Forms\Components\TextInput::make('nome')
                            ->label('Nome Completo')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Ex: João da Silva'),

                        Forms\Components\TextInput::make('email')
                            ->label('E-mail')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->placeholder('professor@exemplo.com'),

                        Forms\Components\TextInput::make('telefone')
                            ->label('Telefone')
                            ->tel()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->mask('(99) 99999-9999')
                            ->placeholder('(00) 00000-0000'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Turmas')
                    ->schema([

                        // SELECT DE ESCOLA
                        Forms\Components\Select::make('escola_id')
                            ->label('Escola')
                            ->options(
                                Escola::query()
                                    ->orderBy('nome')
                                    ->pluck('nome', 'id')
                            )
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->required()
                            ->afterStateUpdated(fn($set) => $set('turmas', []))
                            ->dehydrated(false),

                        // SELECT DE TURMAS (DEPENDENTE)
                        Forms\Components\Select::make('turmas')
                            ->label('Turmas')
                            ->relationship('turmas', 'nome')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->disabled(fn(Get $get) => blank($get('escola_id')))
                            ->options(function (Get $get) {
                                if (!$get('escola_id')) {
                                    return [];
                                }

                                return Turma::query()
                                    ->where('id_escola', $get('escola_id'))
                                    ->with('serie')
                                    ->orderBy('nome')
                                    ->get()
                                    ->mapWithKeys(fn($turma) => [
                                        $turma->id => "{$turma->nome} - {$turma->serie->nome}",
                                    ]);
                            })
                            ->helperText('Selecione as turmas da escola escolhida'),

                    ])
                    ->columns(2),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('matricula')
                    ->label('Matrícula')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('nome')
                    ->label('Nome')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('email')
                    ->label('E-mail')
                    ->searchable()
                    ->copyable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('telefone')
                    ->label('Telefone')
                    ->searchable()
                    ->placeholder('Não informado'),

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
                Tables\Filters\SelectFilter::make('turmas')
                    ->label('Turma')
                    ->relationship('turmas', 'nome')
                    ->searchable()
                    ->preload()
                    ->multiple(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
