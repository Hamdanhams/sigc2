<?php

namespace App\Filament\Resources\SebaranFsbsResource\Pages;

use App\Filament\Resources\SebaranFsbsResource;
use App\Imports\SebaranFsbsImport;
use App\Models\SebaranFsbs;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ListSebaranFsbs extends ListRecords
{
    protected static string $resource = SebaranFsbsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('import')
                ->label('Import Excel')
                ->icon('heroicon-o-arrow-up-tray')
                ->form([
                    Forms\Components\FileUpload::make('file')
                        ->label('File Excel')
                        ->acceptedFileTypes([
                            'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        ])
                        ->required()
                        ->disk('local')
                        ->directory('imports'),
                ])
                ->action(function (array $data) {
                    $path = Storage::disk('local')->path($data['file']);
                    Excel::import(new SebaranFsbsImport, $path);

                    Notification::make()
                        ->title('Import dimulai, diproses di background')
                        ->success()
                        ->send();
                }),

            Actions\Action::make('bersihkanDuplikat')
                ->label('Sisakan 2 Elevasi Terendah')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Bersihkan Data Elevasi Berlebih')
                ->modalDescription('Untuk setiap kombinasi Front + Titik Produksi, hanya 2 LEVEL elevasi terendah yang akan disisakan (semua baris pada level itu tetap disimpan). Sisanya akan DIHAPUS PERMANEN. Lanjutkan?')
                ->modalSubmitActionLabel('Ya, Bersihkan')
                ->action(function () {
                    $groups = SebaranFsbs::select('inisial_front', 'titik_produksi')
                        ->groupByRaw('inisial_front, titik_produksi')
                        ->get();

                    $deletedCount = 0;

                    foreach ($groups as $group) {
                        $rows = SebaranFsbs::where('inisial_front', $group->inisial_front)
                            ->where('titik_produksi', $group->titik_produksi)
                            ->get();

                        // Ambil 2 nilai elevasi terendah yang unik
                        $elevasiTerendah = $rows
                            ->pluck('elevasi')
                            ->unique()
                            ->sortBy(fn($e) => (float) $e)
                            ->take(2)
                            ->values()
                            ->toArray();

                        // Kalau cuma ada 1 atau 2 level elevasi berbeda, tidak ada yang perlu dihapus
                        $totalLevelElevasi = $rows->pluck('elevasi')->unique()->count();
                        if ($totalLevelElevasi <= 2) {
                            continue;
                        }

                        $toDelete = $rows->filter(fn($row) => !in_array($row->elevasi, $elevasiTerendah));

                        foreach ($toDelete as $row) {
                            $row->delete();
                            $deletedCount++;
                        }
                    }

                    Notification::make()
                        ->title($deletedCount > 0
                            ? "$deletedCount baris data berhasil dihapus"
                            : 'Tidak ada data yang perlu dibersihkan')
                        ->success()
                        ->send();
                }),

            Actions\CreateAction::make(),
        ];
    }
}
