<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FuncaoAdministrativaResource\Pages;
use App\Filament\Resources\FuncaoAdministrativaResource\RelationManagers;
use App\Models\FuncaoAdministrativa;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class FuncaoAdministrativaResource extends Resource
{

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    public static ?string $modelLabel = 'Função Administrativa';
    protected static ?string $navigationGroup = "Configurações";
    public static ?string $pluralModelLabel = 'Funções Administrativas';
    public static ?string $slug = 'funcoes-administrativas';
    public static ?int $navigationSort = 999;
    // protected static bool $shouldRegisterNavigation = false;
    protected static ?string $model = FuncaoAdministrativa::class;


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nome')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Toggle::make('tem_relacao_turma')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nome')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('tem_relacao_turma')
                    ->boolean(),
            ])
            ->filters([
                //
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
            'index' => Pages\ManageFuncaoAdministrativas::route('/'),
        ];
    }
}
