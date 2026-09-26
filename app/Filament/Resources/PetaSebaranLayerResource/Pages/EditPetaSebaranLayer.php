<?php

namespace App\Filament\Resources\PetaSebaranLayerResource\Pages;

use App\Filament\Resources\PetaSebaranLayerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPetaSebaranLayer extends EditRecord
{
    protected static string $resource = PetaSebaranLayerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
