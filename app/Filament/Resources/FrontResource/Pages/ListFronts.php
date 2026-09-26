<?php

namespace App\Filament\Resources\FrontResource\Pages;

use App\Filament\Resources\FrontResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFronts extends ListRecords
{
    protected static string $resource = FrontResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
