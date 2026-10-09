<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CutiResource\Pages;
use App\Models\Cuti;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Pengajuan cuti. Dibuat dari aplikasi; admin hanya melihat dan menghapus
 * (menghapus pengajuan yang sudah disetujui mengembalikan saldo otomatis).
 */
class CutiResource extends Resource
{
    protected static ?string $model = Cuti::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Cuti';

    protected static ?string $modelLabel = 'Pengajuan Cuti';

    protected static ?string $pluralModelLabel = 'Pengajuan Cuti';

    private const STATUS = [
        'menunggu' => 'Menunggu Pengawas Senior',
        'menunggu_wuh' => 'Menunggu Work Unit Head',
        'disetujui' => 'Disetujui',
        'ditolak' => 'Ditolak',
    ];

    public static function getNavigationBadge(): ?string
    {
        $n = Cuti::whereIn('status', ['menunggu', 'menunggu_wuh'])->count();
        return $n > 0 ? (string) $n : null;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->disabled()
            ->schema([
                Forms\Components\Select::make('personil_id')->relationship('personil', 'nama')->label('Personil'),
                Forms\Components\Select::make('status')->options(self::STATUS),
                Forms\Components\DatePicker::make('tanggal_mulai'),
                Forms\Components\DatePicker::make('tanggal_selesai'),
                Forms\Components\TextInput::make('jumlah_hari')->suffix('hari kerja'),
                Forms\Components\Textarea::make('alasan')->columnSpanFull(),
                Forms\Components\Textarea::make('catatan_penolakan')->columnSpanFull(),
                Forms\Components\Select::make('ditolak_oleh_jabatan')
                    ->options(['pengawas_senior' => 'Pengawas Senior', 'work_unit_head' => 'Work Unit Head']),
                Forms\Components\Select::make('disetujui_senior_oleh')->relationship('penyetujuSenior', 'nama')->label('Disetujui Pengawas Senior'),
                Forms\Components\DateTimePicker::make('disetujui_senior_at')->label('Waktu (Pengawas Senior)'),
                Forms\Components\Select::make('disetujui_wuh_oleh')->relationship('penyetujuWuh', 'nama')->label('Disetujui Work Unit Head'),
                Forms\Components\DateTimePicker::make('disetujui_wuh_at')->label('Waktu (Work Unit Head)'),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('personil.nama')->label('Personil')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('tanggal_mulai')->date('d/m/Y')->sortable(),
                Tables\Columns\TextColumn::make('tanggal_selesai')->date('d/m/Y'),
                Tables\Columns\TextColumn::make('jumlah_hari')->label('Hari kerja'),
                Tables\Columns\BadgeColumn::make('status')
                    ->formatStateUsing(fn($state) => self::STATUS[$state] ?? $state)
                    ->colors([
                        'warning' => 'menunggu',
                        'info' => 'menunggu_wuh',
                        'success' => 'disetujui',
                        'danger' => 'ditolak',
                    ]),
                Tables\Columns\TextColumn::make('ditolak_oleh_jabatan')
                    ->label('Ditolak oleh')
                    ->formatStateUsing(fn($state) => $state === 'work_unit_head' ? 'Work Unit Head' : ($state ? 'Pengawas Senior' : null))
                    ->placeholder('-')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')->label('Diajukan')->dateTime('d/m/Y H:i', 'Asia/Makassar')->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(self::STATUS),
                Tables\Filters\SelectFilter::make('personil_id')->label('Personil')->relationship('personil', 'nama')->searchable()->preload(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->modalDescription('Pengajuan akan dihapus. Kalau sudah disetujui, saldo cuti Personil dikembalikan otomatis.'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCutis::route('/'),
            'view' => Pages\ViewCuti::route('/{record}'),
        ];
    }
}
