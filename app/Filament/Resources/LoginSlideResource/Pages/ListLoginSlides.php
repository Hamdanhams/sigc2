<?php

namespace App\Filament\Resources\LoginSlideResource\Pages;

use App\Filament\Resources\LoginSlideResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLoginSlides extends ListRecords
{
    protected static string $resource = LoginSlideResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
