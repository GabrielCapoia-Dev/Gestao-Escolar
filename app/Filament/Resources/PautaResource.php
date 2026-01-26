<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PautaResource\Pages;
use App\Models\Pauta;
use App\Models\TipoAvaliacao;
use App\Models\ComponenteCurricular;
use App\Models\Serie;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PautaResource extends Resource
{
    protected static ?string $model = Pauta::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Pautas';
    protected static ?string $pluralModelLabel = 'Pautas';
    protected static ?string $modelLabel = 'Pauta';
    protected static ?string $navigationGroup = 'Avaliações';
    protected static ?int $navigationSort = 3;
    protected static ?string $slug = 'pautas';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Vinculação')
                ->icon('heroicon-o-link')
                ->schema([
                    Forms\Components\Select::make('id_tipo_avaliacao')
                        ->label('Tipo de Avaliação')
                        ->options(TipoAvaliacao::orderBy('tipo_avaliacao')->pluck('tipo_avaliacao', 'id'))
                        ->required()
                        ->searchable()
                        ->preload()
                        ->helperText('Define quais alternativas estarão disponíveis para esta pauta'),

                    Forms\Components\Select::make('id_componente_curricular')
                        ->label('Componente Curricular')
                        ->options(ComponenteCurricular::orderBy('nome')->pluck('nome', 'id'))
                        ->required()
                        ->searchable()
                        ->preload(),

                    Forms\Components\Select::make('id_serie')
                        ->label('Série')
                        ->options(Serie::orderBy('nome')->pluck('nome', 'id'))
                        ->required()
                        ->searchable()
                        ->preload(),
                ])
                ->columns(3),

            Forms\Components\Section::make('Pauta')
                ->icon('heroicon-o-document-text')
                ->schema([
                    Forms\Components\Textarea::make('pauta')
                        ->label('Texto da Pauta (Pergunta)')
                        ->required()
                        ->rows(3)
                        ->maxLength(1000)
                        ->placeholder('Ex: O aluno demonstra interesse nas atividades propostas?')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('tipoAvaliacao.tipo_avaliacao')
                    ->label('Tipo')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('componenteCurricular.nome')
                    ->label('Componente')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('serie.nome')
                    ->label('Série')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('pauta')
                    ->label('Pauta')
                    ->sortable()
                    ->searchable()
                    ->wrap()
                    ->limit(60),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('id_tipo_avaliacao')
                    ->label('Tipo de Avaliação')
                    ->options(TipoAvaliacao::orderBy('tipo_avaliacao')->pluck('tipo_avaliacao', 'id'))
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('id_componente_curricular')
                    ->label('Componente Curricular')
                    ->options(ComponenteCurricular::orderBy('nome')->pluck('nome', 'id'))
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('id_serie')
                    ->label('Série')
                    ->options(Serie::orderBy('nome')->pluck('nome', 'id'))
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManagePautas::route('/'),
        ];
    }
}