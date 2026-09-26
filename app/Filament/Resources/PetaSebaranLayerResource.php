<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PetaSebaranLayerResource\Pages;
use App\Filament\Resources\PetaSebaranLayerResource\RelationManagers;
use App\Models\PetaSebaranLayer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Jobs\ConvertPetaSebaranJob;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PetaSebaranLayerResource extends Resource
{
    protected static ?string $model = PetaSebaranLayer::class;

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
                    ->directory('geopdf_sebaran')
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
                    ->label('Konversi Peta')
                    ->icon('heroicon-o-arrow-path')
                    ->visible(fn($record) => in_array($record->status, ['pending', 'gagal']))
                    ->action(function ($record) {
                        ConvertPetaSebaranJob::dispatch($record->id);
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
            'index' => Pages\ListPetaSebaranLayers::route('/'),
            'create' => Pages\CreatePetaSebaranLayer::route('/create'),
            'edit' => Pages\EditPetaSebaranLayer::route('/{record}/edit'),
        ];
    }
}
