<?php

namespace App\Filament\Resources\FrontResource\Pages;

use App\Filament\Resources\FrontResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFront extends EditRecord
{
    protected static string $resource = FrontResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
