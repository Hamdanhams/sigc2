<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PermintaanResource\Pages;
use App\Filament\Resources\PermintaanResource\RelationManagers;
use App\Models\Permintaan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PermintaanResource extends Resource
{
    protected static ?string $model = Permintaan::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Data Permintaan')
                    ->schema([
                        Forms\Components\Select::make('personil_id')
                            ->relationship('personil', 'nama')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\Select::make('front_id')
                            ->relationship('front', 'nama_front')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('jenis_permintaan')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\Textarea::make('keterangan')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpanFull(),
                        Forms\Components\Placeholder::make('gambar_preview')
                            ->label('Gambar dari Personil')
                            ->content(fn($record) => $record?->gambar
                                ? new \Illuminate\Support\HtmlString('<a href="' . $record->gambar . '" target="_blank" class="text-primary-600 underline">Lihat Gambar</a>')
                                : '-')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Proses Admin')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options([
                                'menunggu' => 'Menunggu',
                                'diproses' => 'Diproses',
                                'selesai' => 'Selesai',
                            ])
                            ->required(),
                        Forms\Components\FileUpload::make('hasil_pdf')
                            ->label('Hasil (PDF)')
                            ->disk('local')
                            ->directory('permintaan_hasil')
                            ->acceptedFileTypes(['application/pdf']),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('personil.nama')->label('Personil')->searchable(),
                Tables\Columns\TextColumn::make('front.nama_front')->label('Front'),
                Tables\Columns\TextColumn::make('jenis_permintaan')->searchable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'menunggu',
                        'info' => 'diproses',
                        'success' => 'selesai',
                    ]),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->label('Diajukan'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'menunggu' => 'Menunggu',
                        'diproses' => 'Diproses',
                        'selesai' => 'Selesai',
                    ]),
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

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'menunggu')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
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
            'index' => Pages\ListPermintaans::route('/'),
            'create' => Pages\CreatePermintaan::route('/create'),
            'edit' => Pages\EditPermintaan::route('/{record}/edit'),
        ];
    }
}
