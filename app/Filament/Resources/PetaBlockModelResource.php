<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PetaBlockModelResource\Pages;
use App\Jobs\ConvertPetaBlockModelJob;
use App\Models\PetaBlockModel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class PetaBlockModelResource extends Resource
{
    protected static ?string $model = PetaBlockModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-map';

    protected static ?string $modelLabel = 'Peta Block Model';

    protected static ?string $pluralModelLabel = 'Peta Block Model';

    protected static ?string $navigationLabel = 'Peta Block Model';

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
                Forms\Components\TextInput::make('rl')
                    ->label('RL (Request Level)')
                    ->numeric()
                    ->required()
                    ->helperText('Data sebaran FSBS yang diplot di peta ini = elevasi RL + ' . PetaBlockModel::SELISIH_ELEVASI . '. Contoh: RL 12 menampilkan elevasi 14.'),
                Forms\Components\TextInput::make('nama_bagian')
                    ->label('Bagian (opsional)')
                    ->maxLength(100)
                    ->helperText('Bila satu Front + RL dibagi beberapa peta, isi pembeda, mis. Utara / Tengah / Selatan.'),
                Forms\Components\FileUpload::make('file_geopdf')
                    ->label('File GeoPDF')
                    ->disk('local')
                    ->directory('geopdf_blockmodel')
                    ->acceptedFileTypes(['application/pdf'])
                    ->maxSize(512000)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('front.nama_front')->label('Front')->sortable(),
                Tables\Columns\TextColumn::make('rl')->label('RL')->sortable(),
                Tables\Columns\TextColumn::make('nama_bagian')->label('Bagian')->placeholder('-'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'secondary' => 'pending',
                        'warning' => 'processing',
                        'success' => 'selesai',
                        'danger' => 'gagal',
                    ]),
                Tables\Columns\TextColumn::make('pesan_error')->limit(40)->tooltip(fn($record) => $record->pesan_error),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('front_id')->label('Front')->relationship('front', 'nama_front'),
                Tables\Filters\SelectFilter::make('status')->options([
                    'pending' => 'pending',
                    'processing' => 'processing',
                    'selesai' => 'selesai',
                    'gagal' => 'gagal',
                ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('konversi')
                    ->label('Konversi')
                    ->icon('heroicon-o-arrow-path')
                    ->visible(fn($record) => in_array($record->status, ['pending', 'gagal']))
                    ->action(function ($record) {
                        ConvertPetaBlockModelJob::dispatch($record->id);
                        Notification::make()->title('Konversi dimasukkan ke antrean')->success()->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('konversi_terpilih')
                        ->label('Konversi yang dipilih')
                        ->icon('heroicon-o-arrow-path')
                        ->deselectRecordsAfterCompletion()
                        ->action(fn(Collection $records) => self::antrekan($records->whereIn('status', ['pending', 'gagal']))),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    /** Masukkan konversi peta ke antrean. Diproses satu per satu oleh worker queue. */
    public static function antrekan($records): int
    {
        $jumlah = 0;
        foreach ($records as $peta) {
            ConvertPetaBlockModelJob::dispatch($peta->id);
            $jumlah++;
        }

        Notification::make()
            ->title($jumlah ? "{$jumlah} peta dimasukkan ke antrean konversi" : 'Tidak ada peta yang perlu dikonversi')
            ->body($jumlah ? 'Jalankan di VPS: php artisan queue:work --stop-when-empty' : null)
            ->{$jumlah ? 'success' : 'warning'}()
            ->send();

        return $jumlah;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPetaBlockModels::route('/'),
            'create' => Pages\CreatePetaBlockModel::route('/create'),
            'edit' => Pages\EditPetaBlockModel::route('/{record}/edit'),
        ];
    }
}
