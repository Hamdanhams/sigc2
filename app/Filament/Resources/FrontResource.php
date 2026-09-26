<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FrontResource\Pages;
use App\Filament\Resources\FrontResource\RelationManagers;
use App\Models\Front;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class FrontResource extends Resource
{
    protected static ?string $model = Front::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nama_front')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('inisial')
                    ->required()
                    ->maxLength(3),
                Forms\Components\TextInput::make('lokasi')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                    ])
                    ->default('active')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama_front')->searchable(),
                Tables\Columns\TextColumn::make('inisial'),
                Tables\Columns\TextColumn::make('lokasi')->limit(30),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'active',
                        'danger' => 'inactive',
                    ]),
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
            'index' => Pages\ListFronts::route('/'),
            'create' => Pages\CreateFront::route('/create'),
            'edit' => Pages\EditFront::route('/{record}/edit'),
        ];
    }
}
