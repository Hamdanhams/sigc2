<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PetaLayerResource\Pages;
use App\Filament\Resources\PetaLayerResource\RelationManagers;
use App\Models\PetaLayer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Jobs\ConvertGeoPdfJob;

class PetaLayerResource extends Resource
{
    protected static ?string $model = PetaLayer::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nama_peta')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('front_id')
                    ->label('Front')
                    ->relationship('front', 'nama_front'),
                Forms\Components\FileUpload::make('file_geopdf')
                    ->label('File GeoPDF')
                    ->disk('local')
                    ->directory('geopdf')
                    ->acceptedFileTypes(['application/pdf'])
                    ->required(),
            ]);
    }



    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama_peta'),
                Tables\Columns\TextColumn::make('front.nama_front')->label('Front'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'secondary' => 'pending',
                        'warning' => 'processing',
                        'success' => 'selesai',
                        'danger' => 'gagal',
                    ]),
                Tables\Columns\TextColumn::make('pesan_error')->limit(30),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('konversi')
                    ->label('Konversi ke MBTiles')
                    ->icon('heroicon-o-arrow-path')
                    ->visible(fn($record) => in_array($record->status, ['pending', 'gagal']))
                    ->action(function ($record) {
                        ConvertGeoPdfJob::dispatch($record->id);
                        \Filament\Notifications\Notification::make()
                            ->title('Konversi dimulai di background')
                            ->success()
                            ->send();
                    }),
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
            'index' => Pages\ListPetaLayers::route('/'),
            'create' => Pages\CreatePetaLayer::route('/create'),
            'edit' => Pages\EditPetaLayer::route('/{record}/edit'),
        ];
    }
}
