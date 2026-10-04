<?php

namespace App\Filament\Resources\RekonsiliasiResource\Pages;

use App\Filament\Resources\RekonsiliasiResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRekonsiliasi extends EditRecord
{
    protected static string $resource = RekonsiliasiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
