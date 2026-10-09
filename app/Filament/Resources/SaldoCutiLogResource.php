<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SaldoCutiLogResource\Pages;
use App\Models\SaldoCutiLog;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/** Riwayat perubahan saldo cuti (hanya baca). */
class SaldoCutiLogResource extends Resource
{
    protected static ?string $model = SaldoCutiLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationGroup = 'Cuti';

    protected static ?string $modelLabel = 'Riwayat Saldo Cuti';

    protected static ?string $pluralModelLabel = 'Riwayat Saldo Cuti';

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->label('Waktu')->dateTime('d/m/Y H:i', 'Asia/Makassar')->sortable(),
                Tables\Columns\TextColumn::make('personil.nama')->label('Personil')->searchable(),
                Tables\Columns\TextColumn::make('perubahan')
                    ->formatStateUsing(fn($state) => ($state > 0 ? '+' : '') . $state)
                    ->color(fn($state) => $state < 0 ? 'danger' : 'success'),
                Tables\Columns\TextColumn::make('saldo_sebelum')->label('Sebelum'),
                Tables\Columns\TextColumn::make('saldo_sesudah')->label('Sesudah'),
                Tables\Columns\BadgeColumn::make('jenis')
                    ->formatStateUsing(fn($state) => match ($state) {
                        'manual' => 'Atur manual',
                        'cuti_disetujui' => 'Cuti disetujui',
                        'cuti_dikembalikan' => 'Dikembalikan',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('catatan')->limit(40)->tooltip(fn($record) => $record->catatan),
                Tables\Columns\TextColumn::make('oleh'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('personil_id')->label('Personil')->relationship('personil', 'nama')->searchable()->preload(),
                Tables\Filters\SelectFilter::make('jenis')->options([
                    'manual' => 'Atur manual',
                    'cuti_disetujui' => 'Cuti disetujui',
                    'cuti_dikembalikan' => 'Dikembalikan',
                ]),
            ])
            ->actions([])
            ->bulkActions([]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSaldoCutiLogs::route('/'),
        ];
    }
}
