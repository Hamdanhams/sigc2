<?php

namespace App\Filament\Resources\RekonsiliasiResource\Pages;

use App\Filament\Resources\RekonsiliasiResource;
use App\Services\RekonsiliasiService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

/**
 * Edit satu MINGGU sekaligus (record yang dibuka hanya penunjuk minggunya).
 */
class EditRekonsiliasi extends EditRecord
{
    protected static string $resource = RekonsiliasiResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return app(RekonsiliasiService::class)->muatMinggu($this->record->minggu_ke);
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $service = app(RekonsiliasiService::class);

        if (!$service->adaBlokTerisi($data)) {
            Notification::make()
                ->title('Isi minimal satu parameter. Untuk menghapus seluruh minggu, pakai tombol Hapus.')
                ->danger()
                ->send();
            $this->halt();
        }

        return $service->simpanMinggu($data, $record->minggu_ke);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('Hapus minggu ini')
                ->modalDescription('Seluruh data HGSO, LGSO, dan Waste pada minggu ini akan dihapus.')
                ->using(function (Model $record) {
                    app(RekonsiliasiService::class)->hapusMinggu($record->minggu_ke);
                    return true;
                }),
        ];
    }

    protected function getRedirectUrl(): ?string
    {
        return $this->getResource()::getUrl('index');
    }
}
