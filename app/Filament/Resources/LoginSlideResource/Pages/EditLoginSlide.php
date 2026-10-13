<?php

namespace App\Filament\Resources\LoginSlideResource\Pages;

use App\Filament\Resources\LoginSlideResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLoginSlide extends EditRecord
{
    use UnggahGambarSlide;

    protected static string $resource = LoginSlideResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->unggahKeCloudinary($data, wajib: false);
    }
}
