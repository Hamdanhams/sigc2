<?php

namespace App\Filament\Resources\ProduksiResource\Pages;

use App\Filament\Resources\ProduksiResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProduksi extends CreateRecord
{
    use GabungDokumentasi;

    protected static string $resource = ProduksiResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->gabungDokumentasi($data);
    }
}
