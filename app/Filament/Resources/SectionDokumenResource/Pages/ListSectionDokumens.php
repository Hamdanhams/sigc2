<?php

namespace App\Filament\Resources\SectionDokumenResource\Pages;

use App\Filament\Resources\SectionDokumenResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSectionDokumens extends ListRecords
{
    protected static string $resource = SectionDokumenResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
