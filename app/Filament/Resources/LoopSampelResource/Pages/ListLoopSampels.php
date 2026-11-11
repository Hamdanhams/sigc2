<?php

namespace App\Filament\Resources\LoopSampelResource\Pages;

use App\Filament\Resources\LoopSampelResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLoopSampels extends ListRecords
{
    protected static string $resource = LoopSampelResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
