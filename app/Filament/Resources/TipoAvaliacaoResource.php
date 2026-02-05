<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TipoAvaliacaoResource\Pages;
use App\Models\TipoAvaliacao;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Table;

class TipoAvaliacaoResource extends Resource
{
    protected static ?string $model = TipoAvaliacao::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationLabel = 'Tipos de Avaliação';
    protected static ?string $pluralModelLabel = 'Tipos de Avaliação';
    protected static ?string $modelLabel = 'Tipo de Avaliação';
    protected static ?string $navigationGroup = 'Configurações';
    public static ?int $navigationSort = 999;
    protected static ?string $slug = 'tipos-avaliacao';
    // protected static bool $shouldRegisterNavigation = false;

    public static function canAccess(): bool
    {
        /** @var \App\Models\User */
        $user = Auth::user();

        return $user->hasRole('Admin');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Tipo de Avaliação')
                ->icon('heroicon-o-clipboard-document-list')
                ->schema([
                    Forms\Components\TextInput::make('tipo_avaliacao')
                        ->label('Nome do Tipo')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('Ex: Diagnóstica, Comportamental, Participativa')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('tipo_avaliacao', 'asc')
            ->columns([
                Tables\Columns\TextColumn::make('tipo_avaliacao')
                    ->label('Tipo de Avaliação')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('alternativas_count')
                    ->label('Alternativas')
                    ->counts('alternativas')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('pautas_count')
                    ->label('Pautas')
                    ->counts('pautas')
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            'index' => Pages\ManageTipoAvaliacaos::route('/'),
        ];
    }
}
