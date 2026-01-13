<?php

namespace App\Filament\Resources\SerieResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use Filament\Resources\RelationManagers\RelationManager;

class ComponentesCurricularesRelationManager extends RelationManager
{
    protected static string $relationship = 'componentesCurriculares';

    protected static ?string $title = 'Componentes Curriculares';

    public function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('codigo')
                ->required()
                ->maxLength(50),

            Forms\Components\TextInput::make('nome')
                ->required()
                ->maxLength(255),
        ]);
    }

    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('codigo')->sortable(),
                Tables\Columns\TextColumn::make('nome')->searchable(),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make(),
            ])
            ->actions([
                Tables\Actions\DetachAction::make(),
            ]);
    }
}
