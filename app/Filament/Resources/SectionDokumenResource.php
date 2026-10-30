<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SectionDokumenResource\Pages;
use App\Models\SectionDokumen;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SectionDokumenResource extends Resource
{
    protected static ?string $model = SectionDokumen::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $modelLabel = 'Dokumen Section';

    protected static ?string $pluralModelLabel = 'Dokumen Section';

    protected static ?string $navigationLabel = 'Section (PDF)';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('front_id')
                    ->label('Front')
                    ->relationship('front', 'nama_front')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\TextInput::make('judul')
                    ->label('Judul dokumen')
                    ->required()
                    ->maxLength(255),
                Forms\Components\FileUpload::make('file_pdf')
                    ->label('File PDF')
                    ->disk('local')
                    ->directory('section_pdf')
                    ->acceptedFileTypes(['application/pdf'])
                    ->maxSize(51200)
                    ->helperText('Maksimal 50 MB. Mengganti file otomatis menghapus file lama.')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('front.nama_front')->label('Front')->sortable(),
                Tables\Columns\TextColumn::make('judul')->label('Judul')->searchable(),
                Tables\Columns\TextColumn::make('ukuran')
                    ->label('Ukuran')
                    ->formatStateUsing(fn($state) => $state ? number_format($state / 1048576, 2, ',', '.') . ' MB' : '-'),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime('d M Y H:i', 'Asia/Makassar')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('front_id')->label('Front')->relationship('front', 'nama_front'),
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
            'index' => Pages\ListSectionDokumens::route('/'),
            'create' => Pages\CreateSectionDokumen::route('/create'),
            'edit' => Pages\EditSectionDokumen::route('/{record}/edit'),
        ];
    }
}
