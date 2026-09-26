<?php

namespace App\Filament\Resources\ProduksiResource\Pages;

use App\Filament\Resources\ProduksiResource;
use App\Models\Produksi;
use App\Models\Front;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Http;

class ListProduksis extends ListRecords
{
    protected static string $resource = ProduksiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('downloadDokumentasi')
                ->label('Download Dokumentasi')
                ->icon('heroicon-o-photo')
                ->form([
                    Forms\Components\Select::make('front_id')
                        ->label('Front')
                        ->options(fn() => Front::pluck('nama_front', 'id'))
                        ->required(),
                    Forms\Components\DatePicker::make('tanggal')
                        ->label('Tanggal')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $records = Produksi::where('front_id', $data['front_id'])
                        ->whereDate('tanggal', $data['tanggal'])
                        ->get();

                    if ($records->isEmpty()) {
                        Notification::make()
                            ->title('Tidak ada laporan produksi ditemukan untuk filter ini')
                            ->warning()
                            ->send();
                        return;
                    }

                    $tempDir = storage_path('app/temp_dokumentasi_produksi_' . uniqid());
                    mkdir($tempDir . '/Dokumentasi Produksi', 0755, true);
                    mkdir($tempDir . '/Dokumentasi Kendala', 0755, true);

                    $totalFile = 0;

                    foreach ($records as $produksi) {
                        $totalFile += $this->downloadKeFolder(
                            $produksi->dokumentasi_produksi ?? [],
                            $tempDir . '/Dokumentasi Produksi',
                            'produksi_' . $produksi->id
                        );

                        $totalFile += $this->downloadKeFolder(
                            $produksi->dokumentasi_kendala ?? [],
                            $tempDir . '/Dokumentasi Kendala',
                            'kendala_' . $produksi->id
                        );
                    }

                    if ($totalFile === 0) {
                        $this->hapusFolder($tempDir);

                        Notification::make()
                            ->title('Tidak ada dokumentasi ditemukan untuk filter ini')
                            ->warning()
                            ->send();
                        return;
                    }

                    $zipFileName = 'dokumentasi_produksi_' . now()->format('Ymd_His') . '.zip';
                    $zipPath = storage_path('app/' . $zipFileName);

                    $zip = new \ZipArchive();
                    $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

                    $files = new \RecursiveIteratorIterator(
                        new \RecursiveDirectoryIterator($tempDir, \RecursiveDirectoryIterator::SKIP_DOTS),
                        \RecursiveIteratorIterator::LEAVES_ONLY
                    );

                    foreach ($files as $file) {
                        if (!$file->isDir()) {
                            $filePath = $file->getRealPath();
                            $relativePath = str_replace('\\', '/', substr($filePath, strlen($tempDir) + 1));
                            $zip->addFile($filePath, $relativePath);
                        }
                    }

                    $zip->close();

                    $this->hapusFolder($tempDir);

                    return response()->download($zipPath, $zipFileName)->deleteFileAfterSend(true);
                }),

            Actions\CreateAction::make(),
        ];
    }

    protected function downloadKeFolder(array $urls, string $folder, string $prefix): int
    {
        $count = 0;

        foreach ($urls as $i => $url) {
            try {
                $response = Http::timeout(15)->get($url);

                if (!$response->successful()) continue;

                $extension = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
                $fileName = $prefix . '_' . ($i + 1) . '.' . $extension;

                file_put_contents($folder . '/' . $fileName, $response->body());
                $count++;
            } catch (\Exception $e) {
                continue;
            }
        }

        return $count;
    }

    protected function hapusFolder(string $dir): void
    {
        if (!is_dir($dir)) return;

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = "$dir/$file";
            is_dir($path) ? $this->hapusFolder($path) : @unlink($path);
        }
        @rmdir($dir);
    }
}
