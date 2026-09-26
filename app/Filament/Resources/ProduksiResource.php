<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProduksiResource\Pages;
use App\Filament\Resources\ProduksiResource\RelationManagers;
use App\Models\Produksi;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms;
use Filament\Forms\Form;
use App\Services\BlockModelLookupService;
use App\Services\RunningNumberService;
use Illuminate\Support\Str;
use App\Services\ProduksiPdfService;
use App\Models\UserPegawai;
use App\Exports\ProduksiExport;
use Maatwebsite\Excel\Facades\Excel;


class ProduksiResource extends Resource
{
    protected static ?string $model = Produksi::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Header Laporan')
                    ->schema([
                        Forms\Components\DatePicker::make('tanggal')
                            ->required(),
                        Forms\Components\Select::make('shift')
                            ->options(['1' => 'Shift 1', '2' => 'Shift 2', '3' => 'Shift 3'])
                            ->required(),
                        Forms\Components\TimePicker::make('jam_mulai'),
                        Forms\Components\TimePicker::make('jam_selesai'),
                        Forms\Components\Select::make('front_id')
                            ->label('Front')
                            ->relationship('front', 'nama_front')
                            ->required()
                            ->live(),
                        Forms\Components\Select::make('fleet')
                            ->options([
                                '1' => 'Fleet 1',
                                '2' => 'Fleet 2',
                                '3' => 'Fleet 3',
                            ]),
                        Forms\Components\Select::make('pic_1_id')
                            ->label('PIC 1')
                            ->relationship('pic1', 'nama')
                            ->required(),
                        Forms\Components\Select::make('pic_2_id')
                            ->label('PIC 2')
                            ->relationship('pic2', 'nama'),
                        Forms\Components\Select::make('user_pegawai_id')
                            ->label('User Pegawai')
                            ->relationship('userPegawai', 'nama'),
                        Forms\Components\FileUpload::make('dokumentasi_produksi')
                            ->multiple()
                            ->disk('cloudinary')
                            ->directory('produksi')
                            ->required(),
                        Forms\Components\FileUpload::make('dokumentasi_kendala')
                            ->multiple()
                            ->disk('cloudinary')
                            ->directory('kendala')
                            ->nullable(),
                        Forms\Components\Textarea::make('keterangan')
                            ->dehydrateStateUsing(fn($state) => $state ? Str::upper($state) : $state),
                        Forms\Components\Textarea::make('rencana_produksi_besok')
                            ->dehydrateStateUsing(fn($state) => $state ? Str::upper($state) : $state),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Detail Produksi')
                    ->schema([
                        Forms\Components\Repeater::make('details')
                            ->relationship('details')
                            ->label('')
                            ->addActionLabel('Tambah Detail Produksi')
                            ->addAction(function (\Filament\Forms\Components\Actions\Action $action) {
                                return $action->action(function (callable $set, callable $get, $livewire) {
                                    $items = $get('details') ?? [];
                                    $lastKey = array_key_last($items);
                                    $newItem = $lastKey !== null ? $items[$lastKey] : [];

                                    $huruf = $newItem['prediksi_kadar'] ?? null;
                                    $frontId = $livewire->data['front_id'] ?? null;
                                    $tanggal = $livewire->data['tanggal'] ?? null;

                                    $newItem['running_number'] = null;

                                    if ($huruf && $frontId && $tanggal) {
                                        $tahun = (int) date('Y', strtotime($tanggal));
                                        $newItem['running_number'] = app(RunningNumberService::class)
                                            ->generate($frontId, $huruf, $tahun, $items);
                                    }

                                    $newKey = (string) Str::uuid();
                                    $items[$newKey] = $newItem;

                                    $set('details', $items);
                                });
                            })
                            ->schema([
                                Forms\Components\TextInput::make('titik_produksi')
                                    ->required()
                                    ->live(onBlur: true)
                                    ->dehydrateStateUsing(fn($state) => $state ? Str::upper($state) : $state)
                                    ->afterStateUpdated(fn($set, $get, $livewire) => self::updateLookup($set, $get, $livewire)),
                                Forms\Components\TextInput::make('elevasi_atas')
                                    ->numeric()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn($set, $get, $livewire) => self::updateLookup($set, $get, $livewire)),
                                Forms\Components\Select::make('prediksi_kadar')
                                    ->label('Prediksi Kadar')
                                    ->options(['H' => 'Tinggi (H)', 'K' => 'Rendah (K)'])
                                    ->dehydrated(false)
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set, $livewire) {
                                        $frontId = $livewire->data['front_id'] ?? null;
                                        $tanggal = $livewire->data['tanggal'] ?? null;
                                        $items = $livewire->data['details'] ?? [];

                                        if ($state && $frontId && $tanggal) {
                                            $tahun = (int) date('Y', strtotime($tanggal));
                                            $set('running_number', app(RunningNumberService::class)
                                                ->generate($frontId, $state, $tahun, $items));
                                        }
                                    }),
                                Forms\Components\TextInput::make('running_number')
                                    ->dehydrated(),
                                Forms\Components\TextInput::make('tujuan_dumping')
                                    ->dehydrateStateUsing(fn($state) => $state ? Str::upper($state) : $state),
                                Forms\Components\TextInput::make('ni_bm')
                                    ->numeric()
                                    ->disabled(fn($state) => filled($state))
                                    ->dehydrated(),
                                Forms\Components\TextInput::make('fe_bm')
                                    ->numeric()
                                    ->disabled(fn($state) => filled($state))
                                    ->dehydrated(),
                                Forms\Components\TextInput::make('ritase')
                                    ->numeric(),
                                Forms\Components\TextInput::make('gridding')
                                    ->dehydrateStateUsing(fn($state) => $state ? Str::upper($state) : $state),
                            ])
                            ->columns(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    protected static function updateLookup(callable $set, callable $get, mixed $livewire): void
    {
        $front = $livewire->data['front_id'] ?? null;
        $frontModel = $front ? \App\Models\Front::find($front) : null;

        $titik = $get('titik_produksi');
        $elevasi = $get('elevasi_atas');

        if ($frontModel && $titik && $elevasi) {
            $hasil = app(BlockModelLookupService::class)->lookup(
                $frontModel->inisial,
                $titik,
                (string) $elevasi
            );

            $set('ni_bm', isset($hasil['ni_bm']) ? number_format($hasil['ni_bm'], 2, '.', '') : null);
            $set('fe_bm', isset($hasil['fe_bm']) ? number_format($hasil['fe_bm'], 2, '.', '') : null);
        }
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tanggal')->date(),
                Tables\Columns\TextColumn::make('shift'),
                Tables\Columns\TextColumn::make('front.nama_front')->label('Front'),
                Tables\Columns\TextColumn::make('fleet'),
                Tables\Columns\TextColumn::make('details_count')->counts('details')->label('Jml Detail'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('front_id')
                    ->label('Front')
                    ->relationship('front', 'nama_front'),

                Tables\Filters\Filter::make('tanggal')
                    ->form([
                        Forms\Components\DatePicker::make('dari_tanggal')
                            ->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('sampai_tanggal')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data) {
                        return $query
                            ->when(
                                $data['dari_tanggal'],
                                fn($query, $date) => $query->whereDate('tanggal', '>=', $date),
                            )
                            ->when(
                                $data['sampai_tanggal'],
                                fn($query, $date) => $query->whereDate('tanggal', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['dari_tanggal'] ?? null) {
                            $indicators[] = 'Dari: ' . \Carbon\Carbon::parse($data['dari_tanggal'])->format('d/m/Y');
                        }

                        if ($data['sampai_tanggal'] ?? null) {
                            $indicators[] = 'Sampai: ' . \Carbon\Carbon::parse($data['sampai_tanggal'])->format('d/m/Y');
                        }

                        return $indicators;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('exportPdf')
                    ->label('Export PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->form([
                        Forms\Components\Select::make('work_unit_head_id')
                            ->label('Grade Control Work Unit Head')
                            ->options(UserPegawai::pluck('nama', 'id'))
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        $record->load('details', 'front', 'pic1', 'userPegawai');
                        $pdf = app(ProduksiPdfService::class)->generate(collect([$record]), $data['work_unit_head_id']);

                        return response()->streamDownload(
                            fn() => print($pdf->output()),
                            'laporan_produksi_' . $record->id . '.pdf'
                        );
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),

                    Tables\Actions\BulkAction::make('exportExcel')
                        ->label('Export Excel')
                        ->icon('heroicon-o-table-cells')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $ids = $records->pluck('id')->toArray();
                            $filename = 'laporan_produksi_' . now()->format('Ymd_His') . '.xlsx';

                            return Excel::download(new ProduksiExport($ids), $filename);
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('exportPdfMulti')
                        ->label('Export PDF Gabungan')
                        ->icon('heroicon-o-document-arrow-down')
                        ->form([
                            Forms\Components\Select::make('work_unit_head_id')
                                ->label('Grade Control Work Unit Head')
                                ->options(UserPegawai::pluck('nama', 'id'))
                                ->required(),
                        ])
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data) {
                            $records->load('details', 'front', 'pic1', 'userPegawai');
                            $pdf = app(ProduksiPdfService::class)->generate($records, $data['work_unit_head_id']);

                            return response()->streamDownload(
                                fn() => print($pdf->output()),
                                'laporan_produksi_gabungan_' . now()->format('Ymd_His') . '.pdf'
                            );
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
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
            'index' => Pages\ListProduksis::route('/'),
            'create' => Pages\CreateProduksi::route('/create'),
            'edit' => Pages\EditProduksi::route('/{record}/edit'),
        ];
    }
}
