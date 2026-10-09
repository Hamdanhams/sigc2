<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HariLiburResource\Pages;
use App\Models\HariLibur;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Hari libur nasional dan cuti bersama. Tanggal di sini tidak dihitung sebagai
 * hari cuti. Perlu diisi admin setiap tahun.
 */
class HariLiburResource extends Resource
{
    protected static ?string $model = HariLibur::class;

    protected static ?string $navigationIcon = 'heroicon-o-flag';

    protected static ?string $navigationGroup = 'Cuti';

    protected static ?string $modelLabel = 'Hari Libur';

    protected static ?string $pluralModelLabel = 'Hari Libur';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('tanggal')
                    ->required()
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('keterangan')
                    ->placeholder('Contoh: Hari Kemerdekaan RI')
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('tanggal', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('tanggal')->date('l, d F Y')->sortable(),
                Tables\Columns\TextColumn::make('keterangan')->searchable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tahun')
                    ->options(fn() => HariLibur::query()
                        ->selectRaw('YEAR(tanggal) as tahun')
                        ->groupBy('tahun')
                        ->orderByDesc('tahun')
                        ->pluck('tahun', 'tahun')
                        ->all())
                    ->query(fn($query, array $data) => $query->when($data['value'] ?? null, fn($q, $y) => $q->whereYear('tanggal', $y))),
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
            'index' => Pages\ListHariLiburs::route('/'),
            'create' => Pages\CreateHariLibur::route('/create'),
            'edit' => Pages\EditHariLibur::route('/{record}/edit'),
        ];
    }
}
