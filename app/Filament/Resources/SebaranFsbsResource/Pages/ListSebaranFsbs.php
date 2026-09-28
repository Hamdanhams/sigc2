<?php

namespace App\Filament\Resources\SebaranFsbsResource\Pages;

use App\Filament\Resources\SebaranFsbsResource;
use App\Imports\SebaranFsbsImport;
use App\Models\SebaranFsbs;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\DB;
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
                    $deletedCount = DB::delete(<<<SQL
                        DELETE t1 FROM sebaran_fsbs t1
                        JOIN (
                            SELECT id
                            FROM (
                                SELECT id, DENSE_RANK() OVER (
                                    PARTITION BY inisial_front, titik_produksi
                                    ORDER BY CAST(elevasi AS DECIMAL(10,2)) ASC
                                ) AS rnk
                                FROM sebaran_fsbs
                            ) ranked
                            WHERE rnk > 2
                        ) t2 ON t1.id = t2.id
                    SQL);

                    Notification::make()
                        ->title($deletedCount > 0
                            ? "$deletedCount baris data berhasil dihapus"
                            : 'Tidak ada data yang perlu dibersihkan')
                        ->success()
                        ->send();
                }),

            Actions\Action::make('hapusSemua')
                ->label('Hapus Semua Data')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Hapus Semua Data Persebaran FSBS')
                ->modalDescription('SEMUA data pada menu ini akan dihapus permanen, tanpa terkecuali. Tindakan ini tidak bisa dibatalkan. Lanjutkan?')
                ->modalSubmitActionLabel('Ya, Hapus Semua')
                ->action(function () {
                    $count = SebaranFsbs::query()->count();
                    SebaranFsbs::query()->delete();

                    Notification::make()
                        ->title("$count baris data berhasil dihapus")
                        ->success()
                        ->send();
                }),

            Actions\CreateAction::make(),
        ];
    }
}
