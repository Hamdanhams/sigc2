<?php

namespace App\Filament\Resources\PetaLayerResource\Pages;

use App\Filament\Resources\PetaLayerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPetaLayers extends ListRecords
{
    protected static string $resource = PetaLayerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
