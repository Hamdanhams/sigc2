<?php

namespace App\Filament\Resources\PetaBlockModelResource\Pages;

use App\Filament\Resources\PetaBlockModelResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPetaBlockModel extends EditRecord
{
    protected static string $resource = PetaBlockModelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
