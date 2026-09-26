<?php

namespace App\Filament\Resources\BlockModelResource\Pages;

use App\Filament\Resources\BlockModelResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBlockModel extends EditRecord
{
    protected static string $resource = BlockModelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
