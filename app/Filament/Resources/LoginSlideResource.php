<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LoginSlideResource\Pages;
use App\Models\LoginSlide;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

/**
 * Foto + keterangan yang tampil di layar login aplikasi mobile.
 * Foto diunggah ke Cloudinary lewat halaman Create/Edit (lihat trait UnggahGambarSlide).
 */
class LoginSlideResource extends Resource
{
    protected static ?string $model = LoginSlide::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static ?string $modelLabel = 'Slide Login';

    protected static ?string $pluralModelLabel = 'Slide Login';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Placeholder::make('gambar_sekarang')
                    ->label('Foto saat ini')
                    ->visible(fn(?LoginSlide $record) => (bool) $record)
                    ->content(fn(?LoginSlide $record) => $record
                        ? new HtmlString('<img src="' . e($record->gambar) . '" style="max-height:260px;border-radius:8px" />')
                        : ''),
                Forms\Components\FileUpload::make('gambar_upload')
                    ->label('Foto (portrait, sekitar 1080×1920)')
                    ->image()
                    ->maxSize(8192)
                    ->disk('local')
                    ->directory('login-slides-tmp')
                    ->helperText('Saat mengedit, kosongkan jika tidak ingin mengganti foto.'),
                Forms\Components\TextInput::make('judul')->maxLength(100),
                Forms\Components\Textarea::make('keterangan')->rows(2)->maxLength(500),
                Forms\Components\TextInput::make('urutan')
                    ->numeric()
                    ->default(0)
                    ->helperText('Angka kecil tampil lebih dulu.'),
                Forms\Components\Toggle::make('aktif')->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('gambar')->height(70),
                Tables\Columns\TextColumn::make('judul'),
                Tables\Columns\TextColumn::make('keterangan')->limit(50),
                Tables\Columns\TextColumn::make('urutan')->sortable(),
                Tables\Columns\ToggleColumn::make('aktif'),
            ])
            ->defaultSort('urutan')
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
            'index' => Pages\ListLoginSlides::route('/'),
            'create' => Pages\CreateLoginSlide::route('/create'),
            'edit' => Pages\EditLoginSlide::route('/{record}/edit'),
        ];
    }
}
