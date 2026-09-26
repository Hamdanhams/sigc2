<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FsbsResource\Pages;
use App\Models\Fsbs;
use App\Services\KodeSampelParserService;
use App\Services\BlockModelLookupService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use App\Services\CoordinateConversionService;
use Illuminate\Database\Eloquent\Collection;
use App\Services\FsbsPdfService;
use App\Models\UserPegawai;

class FsbsResource extends Resource
{
    protected static ?string $model = Fsbs::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('kode_sampel')
                    ->label('Kode Sampel')
                    ->required()
                    ->live(onBlur: true)
                    ->dehydrateStateUsing(fn($state) => $state ? Str::upper($state) : $state)
                    ->afterStateUpdated(function ($state, callable $set) {
                        if (!$state) return;

                        $hasil = app(KodeSampelParserService::class)->parse($state);

                        $set('front', $hasil['front']);
                        $set('titik_produksi', $hasil['titik_produksi']);
                        $set('elevasi', $hasil['elevasi']);
                        $set('huruf_running', $hasil['huruf_running']);

                        if ($hasil['front'] && $hasil['titik_produksi'] && $hasil['elevasi']) {
                            $lookup = app(BlockModelLookupService::class)->lookup(
                                $hasil['front'],
                                $hasil['titik_produksi'],
                                $hasil['elevasi']
                            );
                            $set('ni_bm', isset($lookup['ni_bm']) ? number_format($lookup['ni_bm'], 2, '.', '') : null);
                            $set('fe_bm', isset($lookup['fe_bm']) ? number_format($lookup['fe_bm'], 2, '.', '') : null);
                        }
                    }),
                Forms\Components\TextInput::make('front')
                    ->label('Front (hasil parsing)')
                    ->dehydrateStateUsing(fn($state) => $state ? Str::upper($state) : $state),
                Forms\Components\TextInput::make('titik_produksi')
                    ->dehydrateStateUsing(fn($state) => $state ? Str::upper($state) : $state),
                Forms\Components\TextInput::make('elevasi'),
                Forms\Components\TextInput::make('huruf_running')->label('Huruf Running'),
                Forms\Components\TextInput::make('koordinat_x')->numeric(),
                Forms\Components\TextInput::make('koordinat_y')->numeric(),
                Forms\Components\Select::make('personil_id')
                    ->label('PIC')
                    ->relationship('personil', 'nama')
                    ->required(),
                Forms\Components\FileUpload::make('foto_material')
                    ->image()
                    ->disk('cloudinary')
                    ->directory('fsbs'),
                Forms\Components\TextInput::make('increment')
                    ->dehydrateStateUsing(fn($state) => $state ? Str::upper($state) : $state),
                Forms\Components\TextInput::make('gridding')
                    ->dehydrateStateUsing(fn($state) => $state ? Str::upper($state) : $state),
                Forms\Components\Textarea::make('keterangan')
                    ->dehydrateStateUsing(fn($state) => $state ? Str::upper($state) : $state),

                Forms\Components\TextInput::make('ni_bm')
                    ->numeric()
                    ->disabled(fn($state) => filled($state))
                    ->dehydrated(),
                Forms\Components\TextInput::make('fe_bm')
                    ->numeric()
                    ->disabled(fn($state) => filled($state))
                    ->dehydrated(),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('kode_sampel')->searchable(),
                Tables\Columns\TextColumn::make('front'),
                Tables\Columns\TextColumn::make('titik_produksi'),
                Tables\Columns\TextColumn::make('elevasi'),
                Tables\Columns\TextColumn::make('personil.nama')->label('PIC'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('front')
                    ->label('Front')
                    ->options(fn() => \App\Models\Front::pluck('nama_front', 'inisial')),

                Tables\Filters\Filter::make('tanggal')
                    ->form([
                        Forms\Components\DatePicker::make('tanggal')
                            ->label('Tanggal'),
                    ])
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data) {
                        return $query->when(
                            $data['tanggal'],
                            fn($query, $date) => $query->whereDate('created_at', '=', $date),
                        );
                    })
                    ->indicateUsing(function (array $data): array {
                        if (!($data['tanggal'] ?? null)) return [];
                        return ['Tanggal: ' . \Carbon\Carbon::parse($data['tanggal'])->format('d/m/Y')];
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),

                    Tables\Actions\BulkAction::make('exportCsv')
                        ->label('Export CSV')
                        ->icon('heroicon-o-document-text')
                        ->action(function (Collection $records) {
                            $filename = 'fsbs_export_' . now()->format('Ymd_His') . '.csv';
                            $handle = fopen('php://temp', 'w+');

                            // BOM UTF-8, biar Excel baca karakter dengan benar
                            fwrite($handle, "\xEF\xBB\xBF");

                            fputcsv($handle, [
                                'kode_sampel',
                                'front',
                                'titik_produksi',
                                'elevasi',
                                'koordinat_x',
                                'koordinat_y',
                                'ni_bm',
                                'fe_bm',
                            ], ';');

                            $formatNumber = function ($value) {
                                if ($value === null) return '';
                                return str_replace('.', ',', (string) $value);
                            };

                            foreach ($records as $r) {
                                fputcsv($handle, [
                                    $r->kode_sampel,
                                    $r->front,
                                    $r->titik_produksi,
                                    $r->elevasi,
                                    $formatNumber($r->koordinat_x),
                                    $formatNumber($r->koordinat_y),
                                    $formatNumber($r->ni_bm),
                                    $formatNumber($r->fe_bm),
                                ], ';');
                            }

                            rewind($handle);
                            $content = stream_get_contents($handle);
                            fclose($handle);

                            return response()->streamDownload(function () use ($content) {
                                echo $content;
                            }, $filename, ['Content-Type' => 'text/csv']);
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('exportGpx')
                        ->label('Export GPX')
                        ->icon('heroicon-o-map')
                        ->action(function (Collection $records) {
                            $converter = app(CoordinateConversionService::class);
                            $filename = 'fsbs_export_' . now()->format('Ymd_His') . '.gpx';

                            $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><gpx version="1.1" creator="SIGC2"></gpx>');

                            foreach ($records as $r) {
                                if (!$r->koordinat_x || !$r->koordinat_y) continue;

                                $latLon = $converter->utmToLatLon((float) $r->koordinat_x, (float) $r->koordinat_y);

                                $wpt = $xml->addChild('wpt');
                                $wpt->addAttribute('lat', $latLon['lat']);
                                $wpt->addAttribute('lon', $latLon['lon']);
                                $wpt->addChild('name', htmlspecialchars($r->kode_sampel));
                                $wpt->addChild('desc', htmlspecialchars("Front: {$r->front}, Ni BM: {$r->ni_bm}, Fe BM: {$r->fe_bm}"));
                            }

                            $content = $xml->asXML();

                            return response()->streamDownload(function () use ($content) {
                                echo $content;
                            }, $filename, ['Content-Type' => 'application/gpx+xml']);
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('exportKml')
                        ->label('Export KML')
                        ->icon('heroicon-o-globe-alt')
                        ->action(function (Collection $records) {
                            $converter = app(CoordinateConversionService::class);
                            $filename = 'fsbs_export_' . now()->format('Ymd_His') . '.kml';

                            $placemarks = '';
                            foreach ($records as $r) {
                                if (!$r->koordinat_x || !$r->koordinat_y) continue;

                                $latLon = $converter->utmToLatLon((float) $r->koordinat_x, (float) $r->koordinat_y);

                                $deskripsi = "Front: {$r->front}<br/>"
                                    . "Titik Produksi: {$r->titik_produksi}<br/>"
                                    . "Elevasi: {$r->elevasi}<br/>"
                                    . "Ni BM: {$r->ni_bm}, Fe BM: {$r->fe_bm}<br/>";

                                if ($r->foto_material) {
                                    $fotoUrl = $r->foto_material;
                                    $deskripsi .= "<br/><img src=\"{$fotoUrl}\" width=\"300\"/>";
                                }

                                $placemarks .= '<Placemark>'
                                    . '<name>' . htmlspecialchars($r->kode_sampel) . '</name>'
                                    . '<description><![CDATA[' . $deskripsi . ']]></description>'
                                    . '<Point><coordinates>' . $latLon['lon'] . ',' . $latLon['lat'] . ',0</coordinates></Point>'
                                    . '</Placemark>';
                            }

                            $content = '<?xml version="1.0" encoding="UTF-8"?>'
                                . '<kml xmlns="http://www.opengis.net/kml/2.2"><Document>'
                                . $placemarks
                                . '</Document></kml>';

                            return response()->streamDownload(function () use ($content) {
                                echo $content;
                            }, $filename, ['Content-Type' => 'application/vnd.google-earth.kml+xml']);
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFsbs::route('/'),
            'create' => Pages\CreateFsbs::route('/create'),
            'edit' => Pages\EditFsbs::route('/{record}/edit'),
        ];
    }
}
