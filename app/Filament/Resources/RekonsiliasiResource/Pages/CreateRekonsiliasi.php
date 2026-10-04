<?php

namespace App\Filament\Resources\RekonsiliasiResource\Pages;

use App\Filament\Resources\RekonsiliasiResource;
use App\Services\RekonsiliasiService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateRekonsiliasi extends CreateRecord
{
    protected static string $resource = RekonsiliasiResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $service = app(RekonsiliasiService::class);

        if (!$service->adaBlokTerisi($data)) {
            Notification::make()
                ->title('Isi minimal satu parameter (HGSO, LGSO, atau Waste)')
                ->danger()
                ->send();
            $this->halt();
        }

        return $service->simpanMinggu($data);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
