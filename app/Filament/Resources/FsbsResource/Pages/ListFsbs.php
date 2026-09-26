<?php

namespace App\Filament\Resources\FsbsResource\Pages;

use App\Filament\Resources\FsbsResource;
use App\Models\Fsbs;
use App\Models\Front;
use App\Models\UserPegawai;
use App\Services\FsbsPdfService;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Http;

class ListFsbs extends ListRecords
{
    protected static string $resource = FsbsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('exportPdf')
                ->label('Export PDF Pengantar Sample')
                ->icon('heroicon-o-document-arrow-down')
                ->form([
                    Forms\Components\Select::make('front_inisial')
                        ->label('Front')
                        ->options(fn() => Front::pluck('nama_front', 'inisial'))
                        ->required(),
                    Forms\Components\DatePicker::make('tanggal_mulai')
                        ->label('Dari Tanggal')
                        ->required(),
                    Forms\Components\DatePicker::make('tanggal_selesai')
                        ->label('Sampai Tanggal')
                        ->required(),
                    Forms\Components\Select::make('user_pegawai_id')
                        ->label('Pengawas PT. ANTAM')
                        ->options(UserPegawai::pluck('nama', 'id'))
                        ->required(),
                ])
                ->action(function (array $data) {
                    $pdf = app(FsbsPdfService::class)->generate(
                        $data['front_inisial'],
                        $data['tanggal_mulai'],
                        $data['tanggal_selesai'],
                        $data['user_pegawai_id']
                    );

                    return response()->streamDownload(
                        fn() => print($pdf->output()),
                        'pengantar_sample_fsbs_' . now()->format('Ymd_His') . '.pdf'
                    );
                }),

            Actions\Action::make('downloadDokumentasi')
                ->label('Download Dokumentasi')
                ->icon('heroicon-o-photo')
                ->form([
                    Forms\Components\Select::make('front_inisial')
                        ->label('Front')
                        ->options(fn() => Front::pluck('nama_front', 'inisial'))
                        ->required(),
                    Forms\Components\DatePicker::make('tanggal_mulai')
                        ->label('Dari Tanggal')
                        ->required(),
                    Forms\Components\DatePicker::make('tanggal_selesai')
                        ->label('Sampai Tanggal')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $records = Fsbs::where('front', $data['front_inisial'])
                        ->whereDate('created_at', '>=', $data['tanggal_mulai'])
                        ->whereDate('created_at', '<=', $data['tanggal_selesai'])
                        ->whereNotNull('foto_material')
                        ->get();

                    if ($records->isEmpty()) {
                        Notification::make()
                            ->title('Tidak ada dokumentasi ditemukan untuk filter ini')
                            ->warning()
                            ->send();
                        return;
                    }

                    $tempDir = storage_path('app/temp_dokumentasi_' . uniqid());
                    mkdir($tempDir, 0755, true);

                    $usedNames = [];

                    foreach ($records as $r) {
                        try {
                            $response = Http::timeout(15)->get($r->foto_material);

                            if (!$response->successful()) continue;

                            $extension = pathinfo(parse_url($r->foto_material, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
                            $baseName = $r->kode_sampel ?: 'sampel_' . $r->id;
                            $fileName = $baseName . '.' . $extension;

                            $counter = 1;
                            while (in_array($fileName, $usedNames)) {
                                $fileName = $baseName . '_' . $counter . '.' . $extension;
                                $counter++;
                            }
                            $usedNames[] = $fileName;

                            file_put_contents($tempDir . '/' . $fileName, $response->body());
                        } catch (\Exception $e) {
                            continue;
                        }
                    }

                    if (empty($usedNames)) {
                        array_map('unlink', glob($tempDir . '/*'));
                        rmdir($tempDir);

                        Notification::make()
                            ->title('Gagal mengunduh dokumentasi, coba lagi')
                            ->danger()
                            ->send();
                        return;
                    }

                    $zipFileName = 'dokumentasi_fsbs_' . now()->format('Ymd_His') . '.zip';
                    $zipPath = storage_path('app/' . $zipFileName);

                    $zip = new \ZipArchive();
                    $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

                    foreach (scandir($tempDir) as $file) {
                        if ($file === '.' || $file === '..') continue;
                        $zip->addFile($tempDir . '/' . $file, $file);
                    }
                    $zip->close();

                    array_map('unlink', glob($tempDir . '/*'));
                    rmdir($tempDir);

                    return response()->download($zipPath, $zipFileName)->deleteFileAfterSend(true);
                }),

            Actions\CreateAction::make(),
        ];
    }
}
