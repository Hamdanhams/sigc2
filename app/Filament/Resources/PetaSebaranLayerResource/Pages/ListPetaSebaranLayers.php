<?php

namespace App\Filament\Resources\PetaSebaranLayerResource\Pages;

use App\Filament\Resources\PetaSebaranLayerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPetaSebaranLayers extends ListRecords
{
    protected static string $resource = PetaSebaranLayerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
