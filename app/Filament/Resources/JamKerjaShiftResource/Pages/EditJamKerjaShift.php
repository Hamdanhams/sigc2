<?php

namespace App\Filament\Resources\JamKerjaShiftResource\Pages;

use App\Filament\Resources\JamKerjaShiftResource;
use Filament\Resources\Pages\EditRecord;

class EditJamKerjaShift extends EditRecord
{
    protected static string $resource = JamKerjaShiftResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
