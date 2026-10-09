<?php

namespace App\Filament\Resources\SafetyMeetingResource\Pages;

use App\Filament\Resources\SafetyMeetingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSafetyMeeting extends EditRecord
{
    protected static string $resource = SafetyMeetingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): ?string
    {
        return $this->getResource()::getUrl('index');
    }
}
