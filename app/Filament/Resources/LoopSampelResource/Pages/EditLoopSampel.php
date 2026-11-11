<?php

namespace App\Filament\Resources\LoopSampelResource\Pages;

use App\Filament\Resources\LoopSampelResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLoopSampel extends EditRecord
{
    protected static string $resource = LoopSampelResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
