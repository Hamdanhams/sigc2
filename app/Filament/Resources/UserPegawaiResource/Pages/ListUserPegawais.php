<?php

namespace App\Filament\Resources\UserPegawaiResource\Pages;

use App\Filament\Resources\UserPegawaiResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUserPegawais extends ListRecords
{
    protected static string $resource = UserPegawaiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
