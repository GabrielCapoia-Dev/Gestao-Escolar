<?php

namespace App\Filament\Resources\TurmaResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Forms\Get;
use App\Models\Professor;

class ComponentesRelationManager extends RelationManager
{
    protected static string $relationship = 'componentes';

    protected static ?string $title = 'Componentes da Turma';

    public function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('nome')
                ->label('Componente')
                ->disabled(),

            Forms\Components\Select::make('professor_id')
                ->label('Professor')
                ->required()
                ->searchable()
                ->options(fn () =>
                    Professor::where('id_escola', $this->ownerRecord->id_escola)
                        ->orderBy('nome')
                        ->pluck('nome', 'id')
                ),
        ]);
    }

    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nome')
                    ->label('Componente'),

                Tables\Columns\TextColumn::make('pivot.professor.nome')
                    ->label('Professor')
                    ->badge()
                    ->color('success'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }
}
