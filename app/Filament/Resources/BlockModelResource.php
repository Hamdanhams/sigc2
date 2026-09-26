<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BlockModelResource\Pages;
use App\Filament\Resources\BlockModelResource\RelationManagers;
use App\Models\BlockModel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BlockModelResource extends Resource
{
    protected static ?string $model = BlockModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('inisial_front')
                    ->label('Front')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('titik_produksi')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('elevasi_genap')
                    ->maxLength(255),
                Forms\Components\TextInput::make('elevasi_ganjil')
                    ->maxLength(255),
                Forms\Components\TextInput::make('ni_persen')
                    ->numeric()
                    ->step(0.0001),
                Forms\Components\TextInput::make('fe_persen')
                    ->numeric()
                    ->step(0.0001),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('inisial_front')->label('Front')->searchable(),
                Tables\Columns\TextColumn::make('titik_produksi')->searchable(),
                Tables\Columns\TextColumn::make('elevasi_genap'),
                Tables\Columns\TextColumn::make('elevasi_ganjil'),
                Tables\Columns\TextColumn::make('ni_persen')
                    ->numeric(decimalPlaces: 2),
                Tables\Columns\TextColumn::make('fe_persen')
                    ->numeric(decimalPlaces: 2),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBlockModels::route('/'),
            'create' => Pages\CreateBlockModel::route('/create'),
            'edit' => Pages\EditBlockModel::route('/{record}/edit'),
        ];
    }
}
