<?php

namespace App\Filament\Resources\SectionDokumenResource\Pages;

use App\Filament\Resources\SectionDokumenResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSectionDokumen extends EditRecord
{
    protected static string $resource = SectionDokumenResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
