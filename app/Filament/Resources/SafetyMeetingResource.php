<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SafetyMeetingResource\Pages;
use App\Models\Personil;
use App\Models\SafetyMeeting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

/**
 * Arsip Safety Meeting. Data dibuat dari aplikasi; admin hanya mengedit
 * keterangan dan menghapus. Teks di dalam gambar tidak ikut berubah saat
 * keterangan diedit (foto = catatan asli saat kejadian).
 */
class SafetyMeetingResource extends Resource
{
    protected static ?string $model = SafetyMeeting::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $modelLabel = 'Safety Meeting';

    protected static ?string $pluralModelLabel = 'Safety Meeting';

    private const ZONA = 'Asia/Makassar';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Foto')
                    ->description('Foto tidak bisa diubah di sini. Teks di dalam foto tidak ikut berubah saat keterangan diedit.')
                    ->schema([
                        Forms\Components\Placeholder::make('foto_preview')
                            ->hiddenLabel()
                            ->content(fn(?SafetyMeeting $record) => $record
                                ? new HtmlString('<a href="' . e($record->foto) . '" target="_blank"><img src="' . e($record->foto) . '" style="max-width:480px;width:100%;border-radius:8px" /></a>')
                                : ''),
                    ]),
                Forms\Components\Section::make('Keterangan')
                    ->schema([
                        Forms\Components\Placeholder::make('info')
                            ->label('Waktu foto diambil')
                            ->content(fn(?SafetyMeeting $record) => $record
                                ? $record->waktu->timezone(self::ZONA)->format('d-m-Y | H:i') . ' WITA — oleh ' . ($record->pembuat?->nama ?? '-')
                                : ''),
                        Forms\Components\TextInput::make('lokasi')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Repeater::make('anggota')
                            ->label('Anggota')
                            ->schema([
                                Forms\Components\Select::make('id')
                                    ->label('Nama')
                                    ->options(fn() => Personil::orderBy('nama')->pluck('nama', 'id')->all())
                                    ->searchable()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(fn($state, callable $set) => $set('nama', Personil::find($state)?->nama)),
                                Forms\Components\Hidden::make('nama'),
                            ])
                            ->minItems(1)
                            ->addActionLabel('Tambah anggota')
                            ->itemLabel(fn(array $state) => $state['nama'] ?? null)
                            ->collapsible(),
                        Forms\Components\Textarea::make('pembahasan')
                            ->required()
                            ->rows(4),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('waktu', 'desc')
            ->columns([
                Tables\Columns\ImageColumn::make('foto')->label('Foto')->height(56),
                Tables\Columns\TextColumn::make('waktu')
                    ->label('Waktu (WITA)')
                    ->dateTime('d/m/Y H:i', self::ZONA)
                    ->sortable(),
                Tables\Columns\TextColumn::make('lokasi')->searchable(),
                Tables\Columns\TextColumn::make('pembuat.nama')->label('Pimpinan')->searchable(),
                Tables\Columns\TextColumn::make('anggota')
                    ->label('Anggota')
                    ->getStateUsing(fn(SafetyMeeting $r) => count($r->anggota ?? []) . ' orang'),
                Tables\Columns\TextColumn::make('pembahasan')->limit(40)->toggleable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('tanggal')
                    ->form([
                        Forms\Components\DatePicker::make('dari')->label('Dari tanggal'),
                        Forms\Components\DatePicker::make('sampai')->label('Sampai tanggal'),
                    ])
                    ->query(function ($query, array $data) {
                        // Tanggal dibandingkan dalam WITA, sedangkan waktu tersimpan UTC.
                        return $query
                            ->when($data['dari'] ?? null, fn($q, $d) => $q->where('waktu', '>=', \Carbon\Carbon::parse($d, self::ZONA)->startOfDay()->utc()))
                            ->when($data['sampai'] ?? null, fn($q, $d) => $q->where('waktu', '<=', \Carbon\Carbon::parse($d, self::ZONA)->endOfDay()->utc()));
                    }),
                Tables\Filters\SelectFilter::make('lokasi')
                    ->options(fn() => SafetyMeeting::query()->distinct()->orderBy('lokasi')->pluck('lokasi', 'lokasi')->all()),
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

    public static function canCreate(): bool
    {
        // Dibuat dari aplikasi (butuh foto asli dari kamera).
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSafetyMeetings::route('/'),
            'edit' => Pages\EditSafetyMeeting::route('/{record}/edit'),
        ];
    }
}
