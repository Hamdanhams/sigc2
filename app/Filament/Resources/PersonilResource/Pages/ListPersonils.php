<?php

namespace App\Filament\Resources\PersonilResource\Pages;

use App\Filament\Resources\PersonilResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPersonils extends ListRecords
{
    protected static string $resource = PersonilResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
