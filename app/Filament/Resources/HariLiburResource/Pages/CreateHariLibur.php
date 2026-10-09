<?php

namespace App\Filament\Resources\HariLiburResource\Pages;

use App\Filament\Resources\HariLiburResource;
use Filament\Resources\Pages\CreateRecord;

class CreateHariLibur extends CreateRecord
{
    protected static string $resource = HariLiburResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
