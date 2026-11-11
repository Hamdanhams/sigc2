<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LoopSampelResource\Pages;
use App\Jobs\HitungEmbeddingLoopJob;
use App\Models\LoopSampel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class LoopSampelResource extends Resource
{
    protected static ?string $model = LoopSampel::class;

    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass-circle';

    protected static ?string $modelLabel = 'Sampel Loop';

    protected static ?string $pluralModelLabel = 'Sampel Loop';

    protected static ?string $navigationLabel = 'Loop (Sampel Referensi)';

    public static function form(Form $form): Form
    {
        $angka = fn(string $nama, string $label) => Forms\Components\TextInput::make($nama)->label($label)->numeric();

        return $form
            ->schema([
                Forms\Components\Section::make('Identitas')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->label('Code')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->helperText('Kode unik sampel, mis. BG1-01.'),
                        Forms\Components\TextInput::make('kode_sample')->label('Kode Sample')->maxLength(255),
                        Forms\Components\TextInput::make('incr')->label('INCR')->maxLength(20),
                        Forms\Components\TextInput::make('inisial_front')->label('Front (inisial)')->maxLength(10),
                        Forms\Components\TextInput::make('zona')
                            ->label('Zona')
                            ->maxLength(255)
                            ->datalist(fn() => LoopSampel::query()->whereNotNull('zona')->distinct()->pluck('zona')->all()),
                        Forms\Components\TextInput::make('warna')->label('Warna')->maxLength(255),
                        Forms\Components\TextInput::make('ukuran')->label('Ukuran')->maxLength(255)->columnSpan(3),
                    ]),
                Forms\Components\Section::make('Deskripsi')
                    ->schema([
                        Forms\Components\Textarea::make('komposisi')->label('Komposisi')->rows(2),
                        Forms\Components\Textarea::make('keterangan')->label('Keterangan')->rows(2),
                    ]),
                Forms\Components\Section::make('Kadar')
                    ->columns(4)
                    ->schema([
                        $angka('ni', 'Ni'), $angka('co', 'Co'), $angka('fe', 'Fe'), $angka('sio2', 'SiO2'),
                        $angka('cao', 'CaO'), $angka('mgo', 'MgO'), $angka('cr2o3', 'Cr2O3'), $angka('al2o3', 'Al2O3'),
                        $angka('cr', 'Cr'), $angka('fe_ni', 'Fe/Ni'), $angka('s_m', 'S/M'), $angka('bc', 'BC'),
                    ]),
                Forms\Components\Section::make('Foto referensi')
                    ->schema([
                        Forms\Components\FileUpload::make('foto')
                            ->label('Foto')
                            ->disk('public')
                            ->directory('loop')
                            ->image()
                            ->imageResizeMode('contain')
                            ->imageResizeTargetWidth('1024')
                            ->imageResizeTargetHeight('1024')
                            ->maxSize(15360)
                            ->helperText('Mengganti foto menghitung ulang sidik jarinya otomatis (status "Memproses" lalu "Siap").'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('code')
            ->columns([
                Tables\Columns\ImageColumn::make('foto')->disk('public')->label('Foto')->height(48),
                Tables\Columns\TextColumn::make('code')->label('Code')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('inisial_front')->label('Front')->sortable(),
                Tables\Columns\TextColumn::make('zona')->label('Zona')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('ni')->label('Ni'),
                Tables\Columns\TextColumn::make('fe')->label('Fe'),
                Tables\Columns\TextColumn::make('mgo')->label('MgO'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'siap',
                        'danger' => 'gagal',
                    ]),
                Tables\Columns\TextColumn::make('pesan_error')->limit(40)->tooltip(fn($record) => $record->pesan_error)->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('inisial_front')->label('Front')
                    ->options(fn() => LoopSampel::query()->whereNotNull('inisial_front')->distinct()->pluck('inisial_front', 'inisial_front')->all()),
                Tables\Filters\SelectFilter::make('zona')
                    ->options(fn() => LoopSampel::query()->whereNotNull('zona')->distinct()->pluck('zona', 'zona')->all()),
                Tables\Filters\SelectFilter::make('status')->options([
                    'pending' => 'pending',
                    'siap' => 'siap',
                    'gagal' => 'gagal',
                ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('hitung_ulang')
                    ->label('Hitung ulang')
                    ->icon('heroicon-o-arrow-path')
                    ->visible(fn($record) => $record->foto && in_array($record->status, ['pending', 'gagal']))
                    ->action(function ($record) {
                        $record->forceFill(['status' => 'pending', 'pesan_error' => null])->saveQuietly();
                        HitungEmbeddingLoopJob::dispatch($record->id);
                        Notification::make()->title('Dimasukkan ke antrean')->success()->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('hitung_ulang_terpilih')
                        ->label('Hitung ulang yang dipilih')
                        ->icon('heroicon-o-arrow-path')
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records) {
                            $jumlah = 0;
                            foreach ($records->filter(fn($r) => $r->foto) as $r) {
                                $r->forceFill(['status' => 'pending', 'pesan_error' => null])->saveQuietly();
                                HitungEmbeddingLoopJob::dispatch($r->id);
                                $jumlah++;
                            }
                            Notification::make()->title("{$jumlah} sampel dimasukkan ke antrean")->success()->send();
                        }),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLoopSampels::route('/'),
            'create' => Pages\CreateLoopSampel::route('/create'),
            'edit' => Pages\EditLoopSampel::route('/{record}/edit'),
        ];
    }
}
