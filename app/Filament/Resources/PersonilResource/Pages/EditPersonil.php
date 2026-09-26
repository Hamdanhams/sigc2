<?php

namespace App\Filament\Resources\PersonilResource\Pages;

use App\Filament\Resources\PersonilResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPersonil extends EditRecord
{
    protected static string $resource = PersonilResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
