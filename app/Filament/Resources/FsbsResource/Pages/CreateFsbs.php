<?php

namespace App\Filament\Resources\FsbsResource\Pages;

use App\Filament\Resources\FsbsResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFsbs extends CreateRecord
{
    use UnggahFotoFsbs;

    protected static string $resource = FsbsResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->unggahFotoFsbs($data);
    }
}
