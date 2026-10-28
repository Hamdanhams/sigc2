<?php

namespace App\Filament\Resources\FsbsResource\Pages;

use App\Filament\Resources\FsbsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFsbs extends EditRecord
{
    use UnggahFotoFsbs;

    protected static string $resource = FsbsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->unggahFotoFsbs($data, $this->record->foto_material);
    }

    protected function afterSave(): void
    {
        $this->hapusFotoLamaFsbs();
    }
}
