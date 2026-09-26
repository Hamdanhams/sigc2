<?php

namespace App\Filament\Resources\UserPegawaiResource\Pages;

use App\Filament\Resources\UserPegawaiResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUserPegawai extends EditRecord
{
    protected static string $resource = UserPegawaiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
