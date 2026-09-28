<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SebaranFsbsResource\Pages;
use App\Filament\Resources\SebaranFsbsResource\RelationManagers;
use App\Models\SebaranFsbs;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SebaranFsbsResource extends Resource
{
    protected static ?string $model = SebaranFsbs::class;

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
                Forms\Components\TextInput::make('elevasi')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('koordinat_x')
                    ->numeric()
                    ->required(),
                Forms\Components\TextInput::make('koordinat_y')
                    ->numeric()
                    ->required(),
                Forms\Components\TextInput::make('ni')
                    ->numeric(),
                Forms\Components\TextInput::make('fe')
                    ->numeric(),
                Forms\Components\TextInput::make('si_mg_ratio')
                    ->label('S/M')
                    ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('inisial_front')
                    ->label('Front')
                    ->searchable(query: fn(Builder $query, string $search): Builder => $query->where('inisial_front', 'like', "{$search}%")),
                Tables\Columns\TextColumn::make('titik_produksi')
                    ->searchable(query: fn(Builder $query, string $search): Builder => $query->where('titik_produksi', 'like', "{$search}%")),
                Tables\Columns\TextColumn::make('elevasi'),
                Tables\Columns\TextColumn::make('koordinat_x'),
                Tables\Columns\TextColumn::make('koordinat_y'),
                Tables\Columns\TextColumn::make('ni')->numeric(decimalPlaces: 2),
                Tables\Columns\TextColumn::make('fe')->numeric(decimalPlaces: 2),
                Tables\Columns\TextColumn::make('si_mg_ratio')->label('S/M')->numeric(decimalPlaces: 2),
            ])
            ->deferLoading()
            ->paginationPageOptions([10, 25, 50])
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
            'index' => Pages\ListSebaranFsbs::route('/'),
            'create' => Pages\CreateSebaranFsbs::route('/create'),
            'edit' => Pages\EditSebaranFsbs::route('/{record}/edit'),
        ];
    }
}
