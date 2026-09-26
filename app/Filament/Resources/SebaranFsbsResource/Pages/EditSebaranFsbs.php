<?php

namespace App\Filament\Resources\SebaranFsbsResource\Pages;

use App\Filament\Resources\SebaranFsbsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSebaranFsbs extends EditRecord
{
    protected static string $resource = SebaranFsbsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
