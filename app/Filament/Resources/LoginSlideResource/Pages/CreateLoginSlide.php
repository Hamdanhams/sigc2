<?php

namespace App\Filament\Resources\LoginSlideResource\Pages;

use App\Filament\Resources\LoginSlideResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLoginSlide extends CreateRecord
{
    use UnggahGambarSlide;

    protected static string $resource = LoginSlideResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->unggahKeCloudinary($data, wajib: true);
    }
}
