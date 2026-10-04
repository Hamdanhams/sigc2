<?php

namespace App\Filament\Resources\RekonsiliasiResource\Pages;

use App\Filament\Resources\RekonsiliasiResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRekonsiliasis extends ListRecords
{
    protected static string $resource = RekonsiliasiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
