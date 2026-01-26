<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AlternativaResource\Pages;
use App\Models\Alternativa;
use App\Models\TipoAvaliacao;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AlternativaResource extends Resource
{
    protected static ?string $model = Alternativa::class;

    protected static ?string $navigationIcon = 'heroicon-o-list-bullet';
    protected static ?string $navigationLabel = 'Alternativas';
    protected static ?string $pluralModelLabel = 'Alternativas';
    protected static ?string $modelLabel = 'Alternativa';
    protected static ?string $navigationGroup = 'Configurações';
    public static ?int $navigationSort = 999;
    protected static ?string $slug = 'alternativas';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Alternativa')
                ->icon('heroicon-o-list-bullet')
                ->schema([
                    Forms\Components\Select::make('id_tipo_avaliacao')
                        ->label('Tipo de Avaliação')
                        ->options(TipoAvaliacao::orderBy('tipo_avaliacao')->pluck('tipo_avaliacao', 'id'))
                        ->required()
                        ->searchable()
                        ->preload()
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('texto')
                        ->label('Texto da Alternativa')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('Ex: Satisfatório, Em desenvolvimento, Insatisfatório')
                        ->columnSpanFull(),

                    Forms\Components\Toggle::make('tem_observacao')
                        ->label('Requer observação')
                        ->helperText('Quando marcado, o professor deverá informar uma observação ao selecionar esta alternativa')
                        ->inline(false)
                        ->onColor('warning')
                        ->offColor('gray')
                        ->onIcon('heroicon-s-chat-bubble-bottom-center-text')
                        ->offIcon('heroicon-s-x-mark')
                        ->default(false)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id_tipo_avaliacao', 'asc')
            ->columns([
                Tables\Columns\TextColumn::make('tipoAvaliacao.tipo_avaliacao')
                    ->label('Tipo de Avaliação')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('texto')
                    ->label('Alternativa')
                    ->sortable()
                    ->searchable()
                    ->wrap(),

                Tables\Columns\IconColumn::make('tem_observacao')
                    ->label('Observação')
                    ->boolean()
                    ->trueIcon('heroicon-o-chat-bubble-bottom-center-text')
                    ->falseIcon('heroicon-o-x-mark')
                    ->trueColor('warning')
                    ->falseColor('gray')
                    ->sortable(),

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

                Tables\Filters\TernaryFilter::make('tem_observacao')
                    ->label('Requer observação')
                    ->placeholder('Todas')
                    ->trueLabel('Com observação')
                    ->falseLabel('Sem observação'),
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
            'index' => Pages\ManageAlternativas::route('/'),
        ];
    }
}