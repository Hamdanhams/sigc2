<?php

namespace App\Filament\Resources\FsbsResource\Pages;

use App\Filament\Resources\FsbsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFsbs extends EditRecord
{
    protected static string $resource = FsbsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
