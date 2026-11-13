<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JamKerjaShiftResource\Pages;
use App\Models\JamKerjaShift;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/** Pengaturan jam kerja per shift. Hanya tiga baris tetap (Shift 1-3): jamnya bisa diubah, barisnya tidak ditambah/dihapus. */
class JamKerjaShiftResource extends Resource
{
    protected static ?string $model = JamKerjaShift::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationGroup = 'Pengaturan';

    protected static ?string $navigationLabel = 'Jam Kerja Shift';

    protected static ?string $modelLabel = 'Jam Kerja Shift';

    protected static ?string $pluralModelLabel = 'Jam Kerja Shift';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('shift')
                    ->label('Shift')
                    ->disabled()
                    ->dehydrated(false)
                    ->formatStateUsing(fn($state) => 'Shift ' . $state),
                Forms\Components\TimePicker::make('jam_mulai')
                    ->label('Jam mulai')
                    ->seconds(false)
                    ->required(),
                Forms\Components\TimePicker::make('jam_selesai')
                    ->label('Jam selesai')
                    ->seconds(false)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('shift')
            ->paginated(false)
            ->columns([
                Tables\Columns\TextColumn::make('shift')
                    ->formatStateUsing(fn($state) => 'Shift ' . $state),
                Tables\Columns\TextColumn::make('jam_mulai')
                    ->label('Jam mulai')
                    ->formatStateUsing(fn($state) => $state ? substr($state, 0, 5) : 'Belum diatur'),
                Tables\Columns\TextColumn::make('jam_selesai')
                    ->label('Jam selesai')
                    ->formatStateUsing(fn($state) => $state ? substr($state, 0, 5) : 'Belum diatur'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function canCreate(): bool
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
            'index' => Pages\ListJamKerjaShifts::route('/'),
            'edit' => Pages\EditJamKerjaShift::route('/{record}/edit'),
        ];
    }
}
